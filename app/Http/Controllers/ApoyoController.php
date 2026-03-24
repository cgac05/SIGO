<?php

namespace App\Http\Controllers;

use App\Models\Apoyo;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

/**
 * Controller para administrar los apoyos.
 *
 * Contiene métodos para listar los apoyos en HTML, devolver los apoyos en JSON
 * para recarga por AJAX y almacenar un nuevo apoyo junto con su registro
 * en `BD_Finanzas` o `BD_Inventario` según el tipo.
 *
 * Importante:
 * - Las operaciones de creación usan transacción para asegurar que
 *   si falla la inserción secundaria (finanzas/inventario) se haga rollback
 *   también del registro principal en `Apoyos`.
 */
class ApoyoController extends Controller
{
    private function getBaseMilestonesTemplate(): array
    {
        return [
            ['slug' => 'apertura_publicacion', 'titulo' => 'Apertura de la publicación'],
            ['slug' => 'recepcion_documentos', 'titulo' => 'Inicio y fin de recepción de documentos'],
            ['slug' => 'evaluacion_solicitudes', 'titulo' => 'Periodo de evaluación de solicitudes'],
            ['slug' => 'entrega_resultados', 'titulo' => 'Entrega de resultados'],
            ['slug' => 'cobro_apoyo', 'titulo' => 'Tiempo para cobrar el apoyo a los seleccionados'],
            ['slug' => 'cierre_apoyo', 'titulo' => 'Cierre del apoyo'],
        ];
    }

    private function sanitizeDescriptionHtml(?string $html): string
    {
        $raw = (string) ($html ?? '');
        if (trim($raw) === '') {
            return '';
        }

        $allowedTags = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><blockquote>';
        $clean = strip_tags($raw, $allowedTags);
        $clean = preg_replace('/ on[a-z]+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $clean) ?? '';
        $clean = preg_replace('/javascript\s*:/i', '', $clean) ?? '';

        return trim($clean);
    }

    private function syncApoyoMilestones(int $apoyoId, array $milestones): void
    {
        if (! Schema::hasTable('Hitos_Apoyo')) {
            return;
        }

        DB::table('Hitos_Apoyo')->where('fk_id_apoyo', $apoyoId)->delete();

        $baseTemplates = collect($this->getBaseMilestonesTemplate());
        $inputBases = collect($milestones)
            ->filter(function ($milestone) {
                return ! empty($milestone['es_base']) && ! empty($milestone['slug']);
            })
            ->keyBy('slug');

        $normalized = [];
        foreach ($baseTemplates as $base) {
            $incoming = $inputBases->get($base['slug'], []);
            $include = array_key_exists('incluir', $incoming)
                ? filter_var($incoming['incluir'], FILTER_VALIDATE_BOOLEAN)
                : ! empty($incoming);

            if (! $include) {
                continue;
            }

            $normalized[] = [
                'slug' => $base['slug'],
                'titulo' => $incoming['titulo'] ?? $base['titulo'],
                'fecha_inicio' => $incoming['fecha_inicio'] ?? null,
                'fecha_fin' => $incoming['fecha_fin'] ?? null,
                'es_base' => 1,
            ];
        }

        foreach ($milestones as $milestone) {
            if (! empty($milestone['es_base'])) {
                continue;
            }

            $include = ! array_key_exists('incluir', $milestone)
                || filter_var($milestone['incluir'], FILTER_VALIDATE_BOOLEAN);
            if (! $include) {
                continue;
            }

            $normalized[] = [
                'slug' => $milestone['slug'] ?? null,
                'titulo' => $milestone['titulo'] ?? null,
                'fecha_inicio' => $milestone['fecha_inicio'] ?? null,
                'fecha_fin' => $milestone['fecha_fin'] ?? null,
                'es_base' => 0,
            ];
        }

        $rows = [];
        $order = 1;
        foreach ($normalized as $milestone) {
            $title = trim((string) ($milestone['titulo'] ?? ''));
            if ($title === '') {
                continue;
            }

            $start = ! empty($milestone['fecha_inicio']) ? Carbon::parse($milestone['fecha_inicio'])->toDateString() : null;
            $end = ! empty($milestone['fecha_fin']) ? Carbon::parse($milestone['fecha_fin'])->toDateString() : null;

            $rows[] = [
                'fk_id_apoyo' => $apoyoId,
                'slug_hito' => trim((string) ($milestone['slug'] ?? '')) ?: null,
                'titulo_hito' => $title,
                'fecha_inicio' => $start,
                'fecha_fin' => $end,
                'orden' => $order++,
                'es_base' => ! empty($milestone['es_base']) ? 1 : 0,
                'activo' => 1,
                'fecha_creacion' => now(),
                'fecha_actualizacion' => null,
            ];
        }

        if (! empty($rows)) {
            DB::table('Hitos_Apoyo')->insert($rows);
        }
    }

    private function validateMilestonesDateRanges(array $milestones): void
    {
        $errors = [];

        foreach ($milestones as $index => $milestone) {
            $include = ! array_key_exists('incluir', $milestone)
                || filter_var($milestone['incluir'], FILTER_VALIDATE_BOOLEAN);

            if (! $include) {
                continue;
            }

            if (empty($milestone['fecha_inicio']) || empty($milestone['fecha_fin'])) {
                continue;
            }

            $start = Carbon::parse($milestone['fecha_inicio'])->toDateString();
            $end = Carbon::parse($milestone['fecha_fin'])->toDateString();

            if ($end < $start) {
                $errors["hitos.$index.fecha_fin"] = 'La fecha de fin no puede ser anterior a la fecha de inicio.';
            }
        }

        if (! empty($errors)) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }
    }

    private function isManagerUser($user): bool
    {
        return (bool) ($user && $user->personal && in_array((int) $user->personal->fk_rol, [1, 2], true));
    }

    private function canManageComment($comment, $user): bool
    {
        if (! $comment || ! $user) {
            return false;
        }

        return (int) $comment->fk_id_usuario === (int) $user->id_usuario || $this->isManagerUser($user);
    }

    private function getApoyoCommentsTree(int $apoyoId, $user): array
    {
        if (! Schema::hasTable('Comentarios_Apoyo') || ! Schema::hasTable('Reacciones_ComentarioApoyo')) {
            return [];
        }

        $userId = (int) $user->id_usuario;

        $rows = DB::table('Comentarios_Apoyo as c')
            ->leftJoin('Usuarios as u', 'u.id_usuario', '=', 'c.fk_id_usuario')
            ->leftJoin('Personal as p', 'p.fk_id_usuario', '=', 'u.id_usuario')
            ->leftJoin('Beneficiarios as b', 'b.fk_id_usuario', '=', 'u.id_usuario')
            ->where('c.fk_id_apoyo', $apoyoId)
            ->orderBy('c.fecha_creacion', 'asc')
            ->select([
                'c.id_comentario',
                'c.fk_id_apoyo',
                'c.fk_id_usuario',
                'c.fk_id_comentario_padre',
                'c.contenido',
                'c.editado',
                'c.fecha_creacion',
                'c.fecha_actualizacion',
                'u.email as autor_email',
                DB::raw("COALESCE(NULLIF(LTRIM(RTRIM(CONCAT(COALESCE(p.nombre,''), ' ', COALESCE(p.apellido_paterno,''), ' ', COALESCE(p.apellido_materno,'')))),''), NULLIF(LTRIM(RTRIM(CONCAT(COALESCE(b.nombre,''), ' ', COALESCE(b.apellido_paterno,''), ' ', COALESCE(b.apellido_materno,'')))),''), u.email) as autor_nombre"),
                DB::raw('CASE WHEN p.fk_id_usuario IS NOT NULL THEN 1 ELSE 0 END as autor_verificado'),
            ])
            ->get();

        $likesCount = DB::table('Reacciones_ComentarioApoyo')
            ->where('tipo_reaccion', 'like')
            ->whereIn('fk_id_comentario', $rows->pluck('id_comentario')->all())
            ->select('fk_id_comentario', DB::raw('COUNT(*) as total'))
            ->groupBy('fk_id_comentario')
            ->pluck('total', 'fk_id_comentario');

        $myLikeIds = DB::table('Reacciones_ComentarioApoyo')
            ->where('tipo_reaccion', 'like')
            ->where('fk_id_usuario', $userId)
            ->whereIn('fk_id_comentario', $rows->pluck('id_comentario')->all())
            ->pluck('fk_id_comentario')
            ->all();

        $myLikesMap = array_flip(array_map('intval', $myLikeIds));

        $mapped = [];
        foreach ($rows as $row) {
            $row->likes_count = (int) ($likesCount[$row->id_comentario] ?? 0);
            $row->liked_by_me = isset($myLikesMap[(int) $row->id_comentario]);
            $row->can_manage = $this->canManageComment($row, $user);
            $row->autor_verificado = (bool) $row->autor_verificado;
            $row->editado = (bool) $row->editado;
            $row->replies = [];
            $mapped[(int) $row->id_comentario] = $row;
        }

        $tree = [];
        foreach ($mapped as $comment) {
            $parentId = $comment->fk_id_comentario_padre ? (int) $comment->fk_id_comentario_padre : null;

            if ($parentId && isset($mapped[$parentId])) {
                $mapped[$parentId]->replies[] = $comment;
            } else {
                $tree[] = $comment;
            }
        }

        return array_values($tree);
    }

    private function normalizeStorageRelativePath(?string $path): ?string
    {
        if (! $path) {
            return null;
        }

        $normalized = ltrim(str_replace('\\', '/', trim($path)), '/');

        if (str_starts_with($normalized, 'storage/')) {
            $normalized = substr($normalized, 8);
        }

        return $normalized !== '' ? $normalized : null;
    }

    private function resolveApoyoImageUrl(?string $path): ?string
    {
        $relativePath = $this->normalizeStorageRelativePath($path);

        if (! $relativePath) {
            return null;
        }

        return route('apoyos.image', ['path' => $relativePath]);
    }

    private function ensureManagerAccess(): void
    {
        $user = Auth::user()?->loadMissing('personal');

        $isManager = $user
            && $user->personal
            && in_array((int) $user->personal->fk_rol, [1, 2], true);

        abort_unless($isManager, 403, 'No cuentas con permisos para gestionar apoyos.');
    }

    /**
     * Mostrar la vista HTML con la tabla de Apoyos.
     *
     * Devuelve la vista `apoyos.index` con la colección de apoyos.
     */
    public function index()
    {
<<<<<<< HEAD
        $user = Auth::user()->loadMissing(['personal', 'beneficiario']);
        $isBeneficiario = $user->isBeneficiario();

        $apoyosQuery = DB::table('Apoyos')
            ->select([
                'id_apoyo',
                'nombre_apoyo',
                'tipo_apoyo',
                'monto_maximo',
                'activo',
                'anio_fiscal',
                'cupo_limite',
                'fecha_inicio as fechaInicio',
                'fecha_fin as fechafin',
                'foto_ruta',
                'descripcion',
            ]);

        if ($isBeneficiario) {
            $hoy = now()->toDateString();

            $apoyosQuery
                ->where('activo', 1)
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_inicio')
                        ->orWhereDate('fecha_inicio', '<=', $hoy);
                })
                ->where(function ($query) use ($hoy) {
                    $query->whereNull('fecha_fin')
                        ->orWhereDate('fecha_fin', '>=', $hoy);
                });
        }

        $apoyos = $apoyosQuery
            ->orderBy('id_apoyo', 'desc')
            ->get();

        $requisitos = DB::table('Requisitos_Apoyo')
            ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
            ->select([
                'Requisitos_Apoyo.fk_id_apoyo',
                'Requisitos_Apoyo.fk_id_tipo_doc',
                'Requisitos_Apoyo.es_obligatorio',
                'Cat_TiposDocumento.nombre_documento',
                'Cat_TiposDocumento.tipo_archivo_permitido',
                'Cat_TiposDocumento.validar_tipo_archivo',
            ])
            ->get();

        $hitosByApoyo = collect();
        if (Schema::hasTable('Hitos_Apoyo') && $apoyos->isNotEmpty()) {
            $hitosByApoyo = DB::table('Hitos_Apoyo')
                ->whereIn('fk_id_apoyo', $apoyos->pluck('id_apoyo')->all())
                ->where('activo', 1)
                ->orderBy('orden')
                ->orderBy('id_hito')
                ->get()
                ->groupBy('fk_id_apoyo');
        }

        $solicitudesActivasByApoyo = collect();
        if ($isBeneficiario && $user->beneficiario?->curp) {
            $solicitudesActivasByApoyo = DB::table('Solicitudes')
                ->join('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
                ->where('Solicitudes.fk_curp', $user->beneficiario->curp)
                ->select([
                    'Solicitudes.fk_id_apoyo',
                    'Solicitudes.folio',
                    'Cat_EstadosSolicitud.nombre_estado as estado',
                    'Solicitudes.fecha_creacion',
                ])
                ->orderByDesc('Solicitudes.fecha_creacion')
                ->get()
                ->groupBy('fk_id_apoyo')
                ->map(function ($rows) {
                    return $rows->first();
                });
        }

        $apoyos = $apoyos->map(function ($apoyo) use ($requisitos, $hitosByApoyo, $solicitudesActivasByApoyo) {
            if (! empty($apoyo->fechaInicio)) {
                $apoyo->fechaInicio = Carbon::parse($apoyo->fechaInicio)->toDateString();
            }

            if (! empty($apoyo->fechafin)) {
                $apoyo->fechafin = Carbon::parse($apoyo->fechafin)->toDateString();
            }

            $apoyo->requisitos = $requisitos->where('fk_id_apoyo', $apoyo->id_apoyo)->values();
            $apoyo->foto_url = $this->resolveApoyoImageUrl($apoyo->foto_ruta);
            $apoyo->descripcion_html = $this->sanitizeDescriptionHtml($apoyo->descripcion ?? '');
            $apoyo->hitos = ($hitosByApoyo->get($apoyo->id_apoyo) ?? collect())->values();
            $apoyo->solicitud_activa = $solicitudesActivasByApoyo->get($apoyo->id_apoyo);

            return $apoyo;
        });

        \Log::info('ApoyoController@index - Apoyos cargados', [
            'count' => $apoyos->count(),
            'apoyos_ids' => $apoyos->pluck('id_apoyo')->toArray(),
        ]);

=======
        $apoyos = DB::table('Apoyos')->orderBy('id_apoyo', 'desc')->get();
        // Cargar catálogos necesarios para el formulario
>>>>>>> Pantalla-Home
        $tiposDocumentos = DB::table('Cat_TiposDocumento')->select('id_tipo_doc', 'nombre_documento')->orderBy('nombre_documento')->get();

        $misSolicitudes = collect();
        if ($isBeneficiario && $user->beneficiario?->curp) {
            $misSolicitudes = DB::table('Solicitudes')
                ->leftJoin('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
                ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
                ->where('Solicitudes.fk_curp', $user->beneficiario->curp)
                ->orderByDesc('Solicitudes.folio')
                ->select([
                    'Solicitudes.folio',
                    'Cat_EstadosSolicitud.nombre_estado as estado',
                    'Solicitudes.fecha_creacion',
                    'Apoyos.nombre_apoyo',
                ])
                ->limit(10)
                ->get();
        }

        $solicitudesRecientes = collect();
        if ($user->personal && in_array((int) $user->personal->fk_rol, [1, 2], true)) {
            $solicitudesRecientes = DB::table('Solicitudes')
                ->leftJoin('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
                ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
                ->orderByDesc('Solicitudes.folio')
                ->select([
                    'Solicitudes.folio',
                    'Solicitudes.fk_curp',
                    'Cat_EstadosSolicitud.nombre_estado as estado',
                    'Solicitudes.fecha_creacion',
                    'Apoyos.nombre_apoyo',
                ])
                ->limit(12)
                ->get();
        }

        return view('apoyos.index', compact('apoyos', 'tiposDocumentos', 'user', 'misSolicitudes', 'solicitudesRecientes'));
    }

    /**
     * Muestra el formulario completo de creación de apoyo (página dedicada).
     */
    public function create()
    {
        $this->ensureManagerAccess();

        $query = DB::table('Cat_TiposDocumento')
            ->select('id_tipo_doc', 'nombre_documento')
            ->orderBy('nombre_documento');

        if (Schema::hasColumn('Cat_TiposDocumento', 'tipo_archivo_permitido')) {
            $query->addSelect('tipo_archivo_permitido');
        }

        if (Schema::hasColumn('Cat_TiposDocumento', 'validar_tipo_archivo')) {
            $query->addSelect('validar_tipo_archivo');
        }

        $tiposDocumentos = $query->get();
        $milestonesBase = $this->getBaseMilestonesTemplate();

        return view('apoyos.create', compact('tiposDocumentos', 'milestonesBase'));
    }

    /**
     * Crea un nuevo tipo de documento para usarlo inmediatamente
     * en el checklist de documentos requeridos.
     */
    public function storeTipoDocumento(Request $request)
    {
        $this->ensureManagerAccess();

        $data = $request->validate([
            'nombre_documento' => 'required|string|max:120',
            'tipo_archivo_permitido' => 'required|in:pdf,image,word,excel,zip,any',
            'validar_tipo_archivo' => 'nullable|boolean',
        ]);

        $nombre = trim($data['nombre_documento']);

        $exists = DB::table('Cat_TiposDocumento')
            ->whereRaw('LOWER(nombre_documento) = ?', [mb_strtolower($nombre)])
            ->first();

        if ($exists) {
            return response()->json([
                'success' => false,
                'message' => 'Ese tipo de documento ya existe.',
                'documento' => [
                    'id_tipo_doc' => $exists->id_tipo_doc,
                    'nombre_documento' => $exists->nombre_documento,
                    'tipo_archivo_permitido' => $exists->tipo_archivo_permitido ?? 'pdf',
                    'validar_tipo_archivo' => (bool) ($exists->validar_tipo_archivo ?? 1),
                ],
            ], 422);
        }

        $insertData = [
            'nombre_documento' => $nombre,
        ];

        if (Schema::hasColumn('Cat_TiposDocumento', 'tipo_archivo_permitido')) {
            $insertData['tipo_archivo_permitido'] = $data['tipo_archivo_permitido'];
        }

        if (Schema::hasColumn('Cat_TiposDocumento', 'validar_tipo_archivo')) {
            $insertData['validar_tipo_archivo'] = filter_var($request->input('validar_tipo_archivo', true), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        $id = DB::table('Cat_TiposDocumento')->insertGetId($insertData);

        return response()->json([
            'success' => true,
            'message' => 'Documento agregado al catálogo.',
            'documento' => [
                'id_tipo_doc' => $id,
                'nombre_documento' => $nombre,
                'tipo_archivo_permitido' => $data['tipo_archivo_permitido'],
                'validar_tipo_archivo' => filter_var($request->input('validar_tipo_archivo', true), FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }

    /**
     * Actualiza la configuración de tipo de archivo y validación manual
     * de un tipo de documento existente.
     */
    public function updateTipoDocumento(Request $request, int $id)
    {
        $this->ensureManagerAccess();

        $data = $request->validate([
            'tipo_archivo_permitido' => 'required|in:pdf,image,word,excel,zip,any',
            'validar_tipo_archivo' => 'nullable|boolean',
        ]);

        $doc = DB::table('Cat_TiposDocumento')->where('id_tipo_doc', $id)->first();
        if (! $doc) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró el tipo de documento.',
            ], 404);
        }

        $payload = [];
        if (Schema::hasColumn('Cat_TiposDocumento', 'tipo_archivo_permitido')) {
            $payload['tipo_archivo_permitido'] = $data['tipo_archivo_permitido'];
        }
        if (Schema::hasColumn('Cat_TiposDocumento', 'validar_tipo_archivo')) {
            $payload['validar_tipo_archivo'] = filter_var($request->input('validar_tipo_archivo', true), FILTER_VALIDATE_BOOLEAN) ? 1 : 0;
        }

        if (! empty($payload)) {
            DB::table('Cat_TiposDocumento')->where('id_tipo_doc', $id)->update($payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración del documento actualizada.',
            'documento' => [
                'id_tipo_doc' => $id,
                'nombre_documento' => $doc->nombre_documento,
                'tipo_archivo_permitido' => $data['tipo_archivo_permitido'],
                'validar_tipo_archivo' => filter_var($request->input('validar_tipo_archivo', true), FILTER_VALIDATE_BOOLEAN),
            ],
        ]);
    }

    /**
     * Verifica si el cupo_limite propuesto para un apoyo de tipo Especie
     * es consistente con el stock disponible en BD_Inventario.
     *
     * POST /apoyos/check-inventario
     * Body: { fk_id_apoyo?: int, stock_inicial: int, cupo_limite: int }
     * Retorna: { ok: bool, stock_actual: int, deficit: int }
     */
    public function checkInventario(Request $request)
    {
        $this->ensureManagerAccess();

        $request->validate([
            'stock_inicial' => 'required|integer|min:0',
            'cupo_limite'   => 'required|integer|min:1',
        ]);

        $stock    = (int) $request->stock_inicial;
        $cupo     = (int) $request->cupo_limite;
        $deficit  = max(0, $cupo - $stock);

        return response()->json([
            'ok'          => $deficit === 0,
            'stock_actual' => $stock,
            'cupo_limite'  => $cupo,
            'deficit'      => $deficit,
        ]);
    }

    /**
     * Un directivo autenticado aumenta el stock_inicial propuesto para que
     * cubra el cupo solicitado.  No persiste aún: sólo valida credenciales
     * y devuelve el nuevo stock aprobado.
     *
     * POST /apoyos/aprobar-inventario
     * Body: { email, password, stock_solicitado }
     */
    public function aprobarInventario(Request $request)
    {
        $this->ensureManagerAccess();

        $request->validate([
            'email'            => 'required|email',
            'password'         => 'required|string',
            'stock_solicitado' => 'required|integer|min:1',
        ]);

        $directivo = \App\Models\User::with('personal')
            ->where('email', $request->email)
            ->where('activo', 1)
            ->first();

        if (! $directivo || ! Hash::check($request->password, $directivo->password_hash)) {
            return response()->json(['ok' => false, 'message' => 'Credenciales incorrectas.'], 401);
        }

        // Verificar que sea Personal con rol Directivo (id_rol = 2)
        if (! $directivo->isPersonal() || optional($directivo->personal)->fk_rol !== 2) {
            return response()->json(['ok' => false, 'message' => 'El usuario no tiene rol de Directivo.'], 403);
        }

        return response()->json([
            'ok'               => true,
            'stock_aprobado'   => (int) $request->stock_solicitado,
            'aprobado_por'     => $directivo->display_name,
            'message'          => 'Stock aprobado correctamente.',
        ]);
    }

    /**
     * Devuelve la lista de apoyos en JSON (para recarga por AJAX).
     *
     * Ruta: GET /apoyos/list
     * Respuesta: JSON array con objetos que contienen `id_apoyo`, `nombre_apoyo`,
     * `tipo_apoyo`, `monto_maximo` y `activo`.
     */
    public function list()
    {
<<<<<<< HEAD
        $this->ensureManagerAccess();

=======
        // Esta ruta devuelve sólo los campos necesarios para la tabla en el cliente
        // y es consumida por AJAX (`reloadApoyos()` en la vista). Mantenerla
        // liviana evita tráfico innecesario.
>>>>>>> Pantalla-Home
        $apoyos = DB::table('Apoyos')
            ->select([
                'id_apoyo',
                'nombre_apoyo',
                'tipo_apoyo',
                'monto_maximo',
                'activo',
                'anio_fiscal',
                'cupo_limite',
                'fecha_inicio as fechaInicio',
                'fecha_fin as fechafin',
                'foto_ruta',
                'descripcion',
            ])
            ->orderBy('id_apoyo', 'desc')
            ->get();

        return response()->json($apoyos);
    }

    /**
     * Almacenar nuevo apoyo y su registro en inventario/finanzas.
     *
     * Validaciones principales:
     * - `nombre_apoyo`: requerido, string, max 100
     * - `tipo_apoyo`: requerido, valores permitidos: 'Económico' o 'Especie'
     * - `monto_inicial_asignado`: requerido sólo si es 'Económico'
     * - `stock_inicial`: requerido sólo si es 'Especie'
     *
     * Flujo:
     * 1. Validar entrada.
     * 2. Ejecutar una transacción DB:
     *    - Insertar en `Apoyos` y obtener `id_apoyo`.
     *    - Insertar en `BD_Finanzas` o `BD_Inventario` según `tipo_apoyo`.
     * 3. Si todo OK, commit; si falla, rollback y devolver error.
     *
     * Respuesta:
     * - Si la petición es AJAX/JSON devuelve JSON con `success` y `message`.
     * - Si no, redirige a la lista con mensaje en sesión.
     */
    public function store(Request $request)
    {
        $this->ensureManagerAccess();

        $data = $request->validate([
            'nombre_apoyo' => 'required|string|max:100',
            'tipo_apoyo' => 'required|in:Económico,Especie',
            'monto_maximo' => 'nullable|numeric|min:0',
            'descripcion' => 'required|string',
            'monto_inicial_asignado' => 'nullable|required_if:tipo_apoyo,Económico|numeric|min:0',
            'stock_inicial' => 'nullable|required_if:tipo_apoyo,Especie|integer|min:0',
            'activo' => 'nullable|boolean',
            'fechaInicio' => 'required|date',
            'fechafin' => 'required|date|after_or_equal:fechaInicio',
            'foto_ruta' => 'nullable|image|max:5120',
            'documentos_requeridos' => 'nullable|array',
            'documentos_requeridos.*' => 'integer|exists:Cat_TiposDocumento,id_tipo_doc',
<<<<<<< HEAD
            'hitos' => 'nullable|array',
            'hitos.*.titulo' => 'nullable|string|max:150',
            'hitos.*.fecha_inicio' => 'nullable|date',
            'hitos.*.fecha_fin' => 'nullable|date',
            'hitos.*.slug' => 'nullable|string|max:80',
            'hitos.*.es_base' => 'nullable|boolean',
            'hitos.*.incluir' => 'nullable|boolean',
=======
        ], [
            'fechafin.after_or_equal' => 'La fecha final debe ser mayor o igual a la fecha inicial.',
>>>>>>> Pantalla-Home
        ]);

        $this->validateMilestonesDateRanges($data['hitos'] ?? []);

        DB::beginTransaction();

        try {
            $fotoRuta = null;
            if ($request->hasFile('foto_ruta')) {
                $fotoRuta = 'storage/' . $request->file('foto_ruta')->store('apoyos', 'public');
            }

            $activoRaw = $request->input('activo', true);
            if (is_array($activoRaw)) {
                $activoRaw = end($activoRaw);
            }
            $activo = filter_var($activoRaw, FILTER_VALIDATE_BOOLEAN) ? 1 : 0;

            $fechaInicio = Carbon::parse($data['fechaInicio']);
            $fechaFin = Carbon::parse($data['fechafin']);

            $apoyo = Apoyo::create([
                'nombre_apoyo' => $data['nombre_apoyo'],
                'anio_fiscal' => (int) $fechaInicio->format('Y'),
                'tipo_apoyo' => $data['tipo_apoyo'],
                'monto_maximo' => $data['monto_maximo'] ?? ($data['monto_inicial_asignado'] ?? 0),
<<<<<<< HEAD
                'cupo_limite' => $data['cupo_limite'] ?? null,
                'activo' => filter_var($activoRaw, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'fecha_inicio' => Carbon::parse($data['fechaInicio']),
                'fecha_fin' => Carbon::parse($data['fechafin']),
            ];

            if (Schema::hasColumn('Apoyos', 'foto_ruta')) {
                $payload['foto_ruta'] = $fotoRuta;
            }

            if (Schema::hasColumn('Apoyos', 'descripcion')) {
                $payload['descripcion'] = $this->sanitizeDescriptionHtml($data['descripcion'] ?? null);
            }

            $apoyo = Apoyo::create($payload);
=======
                'cupo_limite' => $data['tipo_apoyo'] === 'Especie' ? ($data['stock_inicial'] ?? null) : null,
                'activo' => $activo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'foto_ruta' => $fotoRuta,
                'descripcion' => $data['descripcion'],
            ]);
>>>>>>> Pantalla-Home

            if ($data['tipo_apoyo'] === 'Económico') {
                DB::table('BD_Finanzas')->insert([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'monto_asignado' => $data['monto_inicial_asignado'] ?? 0,
                    'monto_ejercido' => 0,
                ]);
            } else {
                DB::table('BD_Inventario')->insert([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'stock_actual' => $data['stock_inicial'] ?? 0,
                ]);
            }

            if (! empty($data['documentos_requeridos'])) {
                foreach ($data['documentos_requeridos'] as $docId) {
                    DB::table('Requisitos_Apoyo')->insert([
                        'fk_id_apoyo' => $apoyo->id_apoyo,
                        'fk_id_tipo_doc' => $docId,
                        'es_obligatorio' => 1,
                    ]);
                }
            }

<<<<<<< HEAD
            $this->syncApoyoMilestones((int) $apoyo->id_apoyo, $data['hitos'] ?? []);
=======
            // Crear hitos base del apoyo para habilitar el workflow institucional.
            if (Schema::hasTable('Hitos_Apoyo')) {
                $hitosBase = ['PUBLICACION', 'RECEPCION', 'ANALISIS_ADMIN', 'RESULTADOS', 'CIERRE'];
                foreach ($hitosBase as $index => $clave) {
                    DB::table('Hitos_Apoyo')->insert([
                        'fk_id_apoyo' => $apoyo->id_apoyo,
                        'clave_hito' => $clave,
                        'nombre_hito' => str_replace('_', ' ', $clave),
                        'orden_hito' => $index + 1,
                        'fecha_inicio' => $fechaInicio,
                        'fecha_fin' => $fechaFin,
                        'activo' => 1,
                        'fecha_creacion' => now(),
                        'fecha_actualizacion' => now(),
                    ]);
                }
            }
>>>>>>> Pantalla-Home

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Apoyo registrado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }
<<<<<<< HEAD

    /**
     * Mostrar el formulario de edición de un apoyo.
     *
     * GET /apoyos/{id}/edit
     */
    public function edit($id)
    {
        $this->ensureManagerAccess();

        $apoyo = Apoyo::findOrFail($id);
        $apoyo->foto_url = $this->resolveApoyoImageUrl($apoyo->foto_ruta);

        $tiposDocumentos = DB::table('Cat_TiposDocumento')
            ->select('id_tipo_doc', 'nombre_documento', 'tipo_archivo_permitido', 'validar_tipo_archivo')
            ->orderBy('nombre_documento')
            ->get();

        $requisitosActuales = DB::table('Requisitos_Apoyo')
            ->where('fk_id_apoyo', $id)
            ->pluck('fk_id_tipo_doc')
            ->toArray();

        $montoInicialAsignado = DB::table('BD_Finanzas')
            ->where('fk_id_apoyo', $id)
            ->value('monto_asignado');

        $stockInicial = DB::table('BD_Inventario')
            ->where('fk_id_apoyo', $id)
            ->value('stock_actual');

        $milestonesBase = $this->getBaseMilestonesTemplate();
        $existingMilestones = collect();
        if (Schema::hasTable('Hitos_Apoyo')) {
            $existingMilestones = DB::table('Hitos_Apoyo')
                ->where('fk_id_apoyo', $id)
                ->where('activo', 1)
                ->orderBy('orden')
                ->orderBy('id_hito')
                ->get();
        }

        return view('apoyos.edit', compact('apoyo', 'tiposDocumentos', 'requisitosActuales', 'montoInicialAsignado', 'stockInicial', 'milestonesBase', 'existingMilestones'));
    }

    /**
     * Actualizar un apoyo existente.
     *
     * POST /apoyos/{id}
     */
    public function update(Request $request, $id)
    {
        $this->ensureManagerAccess();

        $apoyo = Apoyo::findOrFail($id);

        $data = $request->validate([
            'nombre_apoyo' => 'required|string|max:100',
            'tipo_apoyo' => 'required|in:Económico,Especie',
            'monto_maximo' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
            'monto_inicial_asignado' => 'nullable|numeric|required_if:tipo_apoyo,Económico',
            'stock_inicial' => 'nullable|integer|required_if:tipo_apoyo,Especie',
            'cupo_limite' => 'nullable|integer|min:1',
            'activo' => 'nullable|boolean',
            'fechaInicio' => 'required|date_format:Y-m-d',
            'fechafin' => 'required|date_format:Y-m-d|after_or_equal:fechaInicio',
            'foto_ruta' => 'nullable|image|max:5120',
            'documentos_requeridos' => 'nullable|array',
            'documentos_requeridos.*' => 'integer|exists:Cat_TiposDocumento,id_tipo_doc',
            'documentos_requeridos_present' => 'nullable|boolean',
            'hitos' => 'nullable|array',
            'hitos.*.titulo' => 'nullable|string|max:150',
            'hitos.*.fecha_inicio' => 'nullable|date',
            'hitos.*.fecha_fin' => 'nullable|date',
            'hitos.*.slug' => 'nullable|string|max:80',
            'hitos.*.es_base' => 'nullable|boolean',
            'hitos.*.incluir' => 'nullable|boolean',
        ]);

        $this->validateMilestonesDateRanges($data['hitos'] ?? []);

        DB::beginTransaction();

        try {
            $payload = [
                'nombre_apoyo' => $data['nombre_apoyo'],
                'tipo_apoyo' => $data['tipo_apoyo'],
                'monto_maximo' => $data['monto_maximo'] ?? ($data['monto_inicial_asignado'] ?? 0),
                'cupo_limite' => $data['cupo_limite'] ?? null,
                'activo' => filter_var($request->input('activo'), FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'fecha_inicio' => Carbon::parse($data['fechaInicio']),
                'fecha_fin' => Carbon::parse($data['fechafin']),
            ];

            if ($request->hasFile('foto_ruta')) {
                // Eliminar imagen anterior si existe
                $oldPath = $this->normalizeStorageRelativePath($apoyo->foto_ruta);
                if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                    Storage::disk('public')->delete($oldPath);
                }
                $payload['foto_ruta'] = 'storage/' . $request->file('foto_ruta')->store('apoyos', 'public');
            }

            if (Schema::hasColumn('Apoyos', 'descripcion')) {
                $payload['descripcion'] = $this->sanitizeDescriptionHtml($data['descripcion'] ?? null);
            }

            $apoyo->update($payload);

            // Actualizar BD_Finanzas o BD_Inventario
            if ($data['tipo_apoyo'] === 'Económico') {
                $finanzas = DB::table('BD_Finanzas')->where('fk_id_apoyo', $id)->first();
                if ($finanzas) {
                    DB::table('BD_Finanzas')
                        ->where('fk_id_apoyo', $id)
                        ->update(['monto_asignado' => $data['monto_inicial_asignado'] ?? 0]);
                } else {
                    DB::table('BD_Finanzas')->insert([
                        'fk_id_apoyo' => $id,
                        'monto_asignado' => $data['monto_inicial_asignado'] ?? 0,
                        'monto_ejercido' => 0,
                    ]);
                }
            } else {
                $inventario = DB::table('BD_Inventario')->where('fk_id_apoyo', $id)->first();
                if ($inventario) {
                    DB::table('BD_Inventario')
                        ->where('fk_id_apoyo', $id)
                        ->update(['stock_actual' => $data['stock_inicial'] ?? 0]);
                } else {
                    DB::table('BD_Inventario')->insert([
                        'fk_id_apoyo' => $id,
                        'stock_actual' => $data['stock_inicial'] ?? 0,
                    ]);
                }
            }

            // Actualizar requisitos (permitir limpiar todos si no se marca ninguno)
            if ($request->boolean('documentos_requeridos_present')) {
                DB::table('Requisitos_Apoyo')->where('fk_id_apoyo', $id)->delete();
                foreach ($data['documentos_requeridos'] ?? [] as $docId) {
                    DB::table('Requisitos_Apoyo')->insert([
                        'fk_id_apoyo' => $id,
                        'fk_id_tipo_doc' => $docId,
                        'es_obligatorio' => 1,
                    ]);
                }
            }

            $this->syncApoyoMilestones((int) $id, $data['hitos'] ?? []);

            DB::commit();

            // Retornar el apoyo actualizado como JSON
            $apoyoActualizado = DB::table('Apoyos')
                ->where('id_apoyo', $id)
                ->first();

            if ($apoyoActualizado) {
                $apoyoActualizado->foto_url = $this->resolveApoyoImageUrl($apoyoActualizado->foto_ruta ?? null);
            }

            return response()->json([
                'success' => true,
                'message' => 'Apoyo actualizado correctamente.',
                'apoyo' => $apoyoActualizado,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar un apoyo.
     *
     * DELETE /apoyos/{id}
     */
    public function destroy($id)
    {
        $this->ensureManagerAccess();

        DB::beginTransaction();

        try {
            $apoyo = Apoyo::findOrFail($id);

            // Eliminar imagen si existe
            $oldPath = $this->normalizeStorageRelativePath($apoyo->foto_ruta);
            if ($oldPath && Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }

            // Eliminar en cascada (BD_Finanzas, BD_Inventario, Requisitos_Apoyo)
            $apoyo->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Apoyo eliminado correctamente.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function comments($id)
    {
        $user = Auth::user()->loadMissing(['personal', 'beneficiario']);
        $apoyo = Apoyo::findOrFail($id);
        $apoyo->foto_url = $this->resolveApoyoImageUrl($apoyo->foto_ruta);
        $apoyo->descripcion_html = $this->sanitizeDescriptionHtml($apoyo->descripcion ?? '');

        $requisitos = DB::table('Requisitos_Apoyo')
            ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
            ->where('Requisitos_Apoyo.fk_id_apoyo', $apoyo->id_apoyo)
            ->select([
                'Requisitos_Apoyo.fk_id_tipo_doc',
                'Requisitos_Apoyo.es_obligatorio',
                'Cat_TiposDocumento.nombre_documento',
                'Cat_TiposDocumento.tipo_archivo_permitido',
            ])
            ->orderBy('Cat_TiposDocumento.nombre_documento')
            ->get();

        $hitos = collect();
        if (Schema::hasTable('Hitos_Apoyo')) {
            $hitos = DB::table('Hitos_Apoyo')
                ->where('fk_id_apoyo', $apoyo->id_apoyo)
                ->where('activo', 1)
                ->orderBy('orden')
                ->orderBy('id_hito')
                ->get();
        }

        $solicitudActiva = null;
        if ($user->isBeneficiario() && $user->beneficiario?->curp) {
            $solicitudActiva = DB::table('Solicitudes')
                ->join('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
                ->where('Solicitudes.fk_curp', $user->beneficiario->curp)
                ->where('Solicitudes.fk_id_apoyo', $apoyo->id_apoyo)
                ->whereNotIn('Cat_EstadosSolicitud.nombre_estado', ['Rechazada'])
                ->orderByDesc('Solicitudes.fecha_creacion')
                ->select([
                    'Solicitudes.folio',
                    'Cat_EstadosSolicitud.nombre_estado as estado',
                    'Solicitudes.fecha_creacion',
                ])
                ->first();
        }

        $comments = $this->getApoyoCommentsTree((int) $apoyo->id_apoyo, $user);

        if (request()->expectsJson() || request()->ajax() || request()->boolean('json')) {
            return response()->json([
                'success' => true,
                'apoyo' => $apoyo,
                'comments' => $comments,
            ]);
        }

        return view('apoyos.comments', compact('apoyo', 'user', 'comments', 'requisitos', 'hitos', 'solicitudActiva'));
    }

    public function storeComment(Request $request, $id)
    {
        if (! Schema::hasTable('Comentarios_Apoyo') || ! Schema::hasTable('Reacciones_ComentarioApoyo')) {
            return response()->json([
                'success' => false,
                'message' => 'Las tablas de comentarios aun no estan creadas. Ejecuta la migracion pendiente.',
            ], 503);
        }

        $user = Auth::user()->loadMissing('personal');
        $apoyo = Apoyo::findOrFail($id);

        $data = $request->validate([
            'contenido' => 'required|string|max:1200',
            'parent_id' => 'nullable|integer|exists:Comentarios_Apoyo,id_comentario',
        ]);

        $parentId = $data['parent_id'] ?? null;
        if ($parentId) {
            $parent = DB::table('Comentarios_Apoyo')
                ->where('id_comentario', $parentId)
                ->where('fk_id_apoyo', $apoyo->id_apoyo)
                ->first();

            if (! $parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'El comentario padre no pertenece a este apoyo.',
                ], 422);
            }
        }

        DB::table('Comentarios_Apoyo')->insert([
            'fk_id_apoyo' => $apoyo->id_apoyo,
            'fk_id_usuario' => $user->id_usuario,
            'fk_id_comentario_padre' => $parentId,
            'contenido' => trim($data['contenido']),
            'editado' => 0,
            'fecha_creacion' => now(),
            'fecha_actualizacion' => null,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Comentario publicado.',
            'comments' => $this->getApoyoCommentsTree((int) $apoyo->id_apoyo, $user),
        ]);
    }

    public function updateComment(Request $request, $id, $commentId)
    {
        if (! Schema::hasTable('Comentarios_Apoyo') || ! Schema::hasTable('Reacciones_ComentarioApoyo')) {
            return response()->json([
                'success' => false,
                'message' => 'Las tablas de comentarios aun no estan creadas. Ejecuta la migracion pendiente.',
            ], 503);
        }

        $user = Auth::user()->loadMissing('personal');
        $apoyo = Apoyo::findOrFail($id);

        $data = $request->validate([
            'contenido' => 'required|string|max:1200',
        ]);

        $comment = DB::table('Comentarios_Apoyo')
            ->where('id_comentario', $commentId)
            ->where('fk_id_apoyo', $apoyo->id_apoyo)
            ->first();

        if (! $comment) {
            return response()->json(['success' => false, 'message' => 'Comentario no encontrado.'], 404);
        }

        abort_unless($this->canManageComment($comment, $user), 403, 'No tienes permiso para editar este comentario.');

        DB::table('Comentarios_Apoyo')
            ->where('id_comentario', $commentId)
            ->update([
                'contenido' => trim($data['contenido']),
                'editado' => 1,
                'fecha_actualizacion' => now(),
            ]);

        return response()->json([
            'success' => true,
            'message' => 'Comentario actualizado.',
            'comments' => $this->getApoyoCommentsTree((int) $apoyo->id_apoyo, $user),
        ]);
    }

    public function destroyComment($id, $commentId)
    {
        if (! Schema::hasTable('Comentarios_Apoyo') || ! Schema::hasTable('Reacciones_ComentarioApoyo')) {
            return response()->json([
                'success' => false,
                'message' => 'Las tablas de comentarios aun no estan creadas. Ejecuta la migracion pendiente.',
            ], 503);
        }

        $user = Auth::user()->loadMissing('personal');
        $apoyo = Apoyo::findOrFail($id);

        $comment = DB::table('Comentarios_Apoyo')
            ->where('id_comentario', $commentId)
            ->where('fk_id_apoyo', $apoyo->id_apoyo)
            ->first();

        if (! $comment) {
            return response()->json(['success' => false, 'message' => 'Comentario no encontrado.'], 404);
        }

        abort_unless($this->canManageComment($comment, $user), 403, 'No tienes permiso para eliminar este comentario.');

        DB::table('Comentarios_Apoyo')
            ->where('id_comentario', $commentId)
            ->delete();

        return response()->json([
            'success' => true,
            'message' => 'Comentario eliminado.',
            'comments' => $this->getApoyoCommentsTree((int) $apoyo->id_apoyo, $user),
        ]);
    }

    public function toggleCommentLike($id, $commentId)
    {
        if (! Schema::hasTable('Comentarios_Apoyo') || ! Schema::hasTable('Reacciones_ComentarioApoyo')) {
            return response()->json([
                'success' => false,
                'message' => 'Las tablas de comentarios aun no estan creadas. Ejecuta la migracion pendiente.',
            ], 503);
        }

        $user = Auth::user()->loadMissing('personal');
        $apoyo = Apoyo::findOrFail($id);

        $comment = DB::table('Comentarios_Apoyo')
            ->where('id_comentario', $commentId)
            ->where('fk_id_apoyo', $apoyo->id_apoyo)
            ->first();

        if (! $comment) {
            return response()->json(['success' => false, 'message' => 'Comentario no encontrado.'], 404);
        }

        $likeExists = DB::table('Reacciones_ComentarioApoyo')
            ->where('fk_id_comentario', $commentId)
            ->where('fk_id_usuario', $user->id_usuario)
            ->where('tipo_reaccion', 'like')
            ->exists();

        if ($likeExists) {
            DB::table('Reacciones_ComentarioApoyo')
                ->where('fk_id_comentario', $commentId)
                ->where('fk_id_usuario', $user->id_usuario)
                ->where('tipo_reaccion', 'like')
                ->delete();
        } else {
            DB::table('Reacciones_ComentarioApoyo')->insert([
                'fk_id_comentario' => $commentId,
                'fk_id_usuario' => $user->id_usuario,
                'tipo_reaccion' => 'like',
                'fecha_creacion' => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => $likeExists ? 'Like eliminado.' : 'Like agregado.',
            'comments' => $this->getApoyoCommentsTree((int) $apoyo->id_apoyo, $user),
        ]);
    }

    public function image(string $path)
    {
        $relativePath = $this->normalizeStorageRelativePath($path);

        if (! $relativePath || str_contains($relativePath, '..')) {
            abort(404);
        }

        if (! Storage::disk('public')->exists($relativePath)) {
            abort(404);
        }

        return response()->file(Storage::disk('public')->path($relativePath));
    }
}
=======
    }
>>>>>>> Pantalla-Home
