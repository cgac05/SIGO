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

        $apoyos = $apoyos->map(function ($apoyo) use ($requisitos) {
            if (! empty($apoyo->fechaInicio)) {
                $apoyo->fechaInicio = Carbon::parse($apoyo->fechaInicio)->toDateString();
            }

            if (! empty($apoyo->fechafin)) {
                $apoyo->fechafin = Carbon::parse($apoyo->fechafin)->toDateString();
            }

            $apoyo->requisitos = $requisitos->where('fk_id_apoyo', $apoyo->id_apoyo)->values();
            $apoyo->foto_url = $this->resolveApoyoImageUrl($apoyo->foto_ruta);

            return $apoyo;
        });

        \Log::info('ApoyoController@index - Apoyos cargados', [
            'count' => $apoyos->count(),
            'apoyos_ids' => $apoyos->pluck('id_apoyo')->toArray(),
        ]);

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

        return view('apoyos.create', compact('tiposDocumentos'));
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
        $this->ensureManagerAccess();

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
            'monto_maximo' => 'nullable|numeric',
            'descripcion' => 'nullable|string',
            'monto_inicial_asignado' => 'nullable|numeric|required_if:tipo_apoyo,Económico',
            'stock_inicial' => 'nullable|integer|required_if:tipo_apoyo,Especie',
            'cupo_limite' => 'nullable|integer|min:1',
            'activo' => 'nullable|boolean',
            'fechaInicio' => 'required|date',
            'fechafin' => 'required|date|after_or_equal:fechaInicio',
            'foto_ruta' => 'nullable|image|max:5120',
            'documentos_requeridos' => 'nullable|array',
            'documentos_requeridos.*' => 'integer|exists:Cat_TiposDocumento,id_tipo_doc',
        ]);

        DB::beginTransaction();

        try {
            $fotoRuta = null;

            if ($request->hasFile('foto_ruta')) {
                $fotoRuta = 'storage/' . $request->file('foto_ruta')->store('apoyos', 'public');
            }

            $activoRaw = $request->input('activo');

            if (is_array($activoRaw)) {
                $activoRaw = end($activoRaw);
            }

            $payload = [
                'nombre_apoyo' => $data['nombre_apoyo'],
                'anio_fiscal' => (int) now()->format('Y'),
                'tipo_apoyo' => $data['tipo_apoyo'],
                'monto_maximo' => $data['monto_maximo'] ?? ($data['monto_inicial_asignado'] ?? 0),
                'cupo_limite' => $data['cupo_limite'] ?? null,
                'activo' => filter_var($activoRaw, FILTER_VALIDATE_BOOLEAN) ? 1 : 0,
                'fecha_inicio' => Carbon::parse($data['fechaInicio']),
                'fecha_fin' => Carbon::parse($data['fechafin']),
            ];

            if (Schema::hasColumn('Apoyos', 'foto_ruta')) {
                $payload['foto_ruta'] = $fotoRuta;
            }

            if (Schema::hasColumn('Apoyos', 'descripcion')) {
                $payload['descripcion'] = $data['descripcion'] ?? null;
            }

            $apoyo = Apoyo::create($payload);

            if ($data['tipo_apoyo'] === 'Económico') {
                DB::table('BD_Finanzas')->insert([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'monto_asignado' => $data['monto_inicial_asignado'],
                    'monto_ejercido' => 0,
                ]);
            } else {
                DB::table('BD_Inventario')->insert([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'stock_actual' => $data['stock_inicial'],
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

            DB::commit();

            return response()->json(['success' => true, 'message' => 'Apoyo registrado correctamente.']);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['success' => false, 'message' => 'Error: ' . $e->getMessage()], 500);
        }
    }

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

        return view('apoyos.edit', compact('apoyo', 'tiposDocumentos', 'requisitosActuales', 'montoInicialAsignado', 'stockInicial'));
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
        ]);

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
                $payload['descripcion'] = $data['descripcion'] ?? null;
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
