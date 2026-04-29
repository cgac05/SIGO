<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Rules\Recaptcha;
use App\Services\PresupuestoService;
use App\Services\FolioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SolicitudController extends Controller
{
    protected PresupuestoService $presupuestoService;
    protected FolioService $folioService;

    public function __construct(PresupuestoService $presupuestoService, FolioService $folioService)
    {
        $this->presupuestoService = $presupuestoService;
        $this->folioService = $folioService;
    }

    public function create(Request $request, int $id)
    {
        $user = $request->user()->loadMissing('beneficiario');
        $curpBeneficiario = $user->beneficiario?->curp;
        $esPersonal = $user->personal && in_array((int) $user->personal->fk_rol, [1, 2], true);
        $soloRechazados = $request->boolean('solo_rechazados');
        $folioRechazado = (int) $request->query('folio', 0);
        $solicitudReenvio = null;
        $documentosRechazados = collect();

        if ((! $user->isBeneficiario() || ! $curpBeneficiario) && ! ($soloRechazados && $esPersonal)) {
            return redirect()->route('apoyos.index')->with('error', 'Debes iniciar sesión como beneficiario para registrar una solicitud.');
        }

        $apoyo = DB::table('Apoyos')
            ->where('id_apoyo', $id)
            ->first();

        if (! $apoyo) {
            return redirect()->route('apoyos.index')->with('error', 'El apoyo seleccionado no existe.');
        }

        // Cargar modelo de apoyo para acceder a relaciones de presupuesto
        $apoyoModel = \App\Models\Apoyo::with(['categoria'])->find($id);

        // Obtener información de presupuesto disponible
        $estadoPresupuesto = $this->presupuestoService->obtenerEstadoDetalladoApoyo($apoyoModel);

        $requisitos = DB::table('Requisitos_Apoyo')
            ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
            ->where('Requisitos_Apoyo.fk_id_apoyo', $id)
            ->select([
                'Requisitos_Apoyo.fk_id_tipo_doc',
                'Requisitos_Apoyo.es_obligatorio',
                'Cat_TiposDocumento.nombre_documento',
                'Cat_TiposDocumento.tipo_archivo_permitido',
                'Cat_TiposDocumento.validar_tipo_archivo',
            ])
            ->orderBy('Cat_TiposDocumento.nombre_documento')
            ->get();

        if ($soloRechazados && $folioRechazado > 0) {
            $solicitudReenvioQuery = DB::table('Solicitudes')
                ->join('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
                ->where('Solicitudes.folio', $folioRechazado)
                ->where('Solicitudes.fk_id_apoyo', $id)
                ->select([
                    'Solicitudes.folio',
                    'Cat_EstadosSolicitud.nombre_estado as estado',
                    'Solicitudes.fecha_creacion',
                ]);

            if ($curpBeneficiario) {
                $solicitudReenvioQuery->where('Solicitudes.fk_curp', $curpBeneficiario);
            }

            $solicitudReenvio = $solicitudReenvioQuery->first();

            if ($solicitudReenvio) {
                $documentosRechazados = DB::table('Documentos_Expediente')
                    ->join('Cat_TiposDocumento', 'Documentos_Expediente.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                    ->where('Documentos_Expediente.fk_folio', $folioRechazado)
                    ->where(function ($query) {
                        $query->where('Documentos_Expediente.admin_status', 'rechazado')
                            ->orWhere('Documentos_Expediente.estado_validacion', 'RECHAZADO')
                            ->orWhere('Documentos_Expediente.estado_validacion', 'Incorrecto');
                    })
                    ->select([
                        'Documentos_Expediente.fk_id_tipo_doc',
                        'Cat_TiposDocumento.nombre_documento',
                        'Cat_TiposDocumento.tipo_archivo_permitido',
                    ])
                    ->orderBy('Cat_TiposDocumento.nombre_documento')
                    ->get();

                $tiposRechazados = $documentosRechazados->pluck('fk_id_tipo_doc')->map(fn ($tipoId) => (int) $tipoId)->all();
                if (! empty($tiposRechazados)) {
                    $requisitos = $requisitos->whereIn('fk_id_tipo_doc', $tiposRechazados)->values();
                } else {
                    $soloRechazados = false;
                    $folioRechazado = 0;
                }
            } else {
                $soloRechazados = false;
                $folioRechazado = 0;
            }
        }
        $solicitudActiva = DB::table('Solicitudes')
            ->join('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->where('Solicitudes.fk_curp', $curpBeneficiario)
            ->where('Solicitudes.fk_id_apoyo', $id)
            ->whereNotIn('Cat_EstadosSolicitud.nombre_estado', ['Rechazada'])
            ->orderByDesc('Solicitudes.fecha_creacion')
            ->select([
                'Solicitudes.folio',
                'Cat_EstadosSolicitud.nombre_estado as estado',
                'Solicitudes.fecha_creacion',
            ])
            ->first();

        return view('solicitudes.create', compact('apoyo', 'requisitos', 'solicitudActiva', 'estadoPresupuesto', 'soloRechazados', 'folioRechazado', 'solicitudReenvio', 'documentosRechazados'));
    }

    public function historial(Request $request)
    {
        $user = $request->user()->loadMissing('beneficiario');
        $curpBeneficiario = $user->beneficiario?->curp;

        $estadoFiltro = strtolower(trim((string) $request->query('estado', 'total')));
        if (! in_array($estadoFiltro, ['total', 'aprobada', 'proceso', 'rechazada'], true)) {
            $estadoFiltro = 'total';
        }

        if (! $user->isBeneficiario() || ! $curpBeneficiario) {
            abort(403, 'Debes iniciar sesion como beneficiario para ver tus solicitudes.');
        }

        $solicitudes = DB::table('Solicitudes')
            ->leftJoin('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->where('Solicitudes.fk_curp', $curpBeneficiario)
            ->orderByDesc('Solicitudes.fecha_creacion')
            ->select([
                'Solicitudes.folio',
                'Solicitudes.fk_curp',
                'Solicitudes.fk_id_apoyo',
                'Solicitudes.fk_id_estado',
                'Solicitudes.fecha_creacion',
                'Solicitudes.fecha_actualizacion',
                'Solicitudes.presupuesto_confirmado',
                'Solicitudes.fecha_confirmacion_presupuesto',
                'Solicitudes.cuv',
                'Solicitudes.folio_institucional',
                'Solicitudes.monto_entregado',
                'Solicitudes.fecha_entrega_recurso',
                'Solicitudes.fecha_cierre_financiero',
                'Apoyos.nombre_apoyo',
                'Apoyos.tipo_apoyo',
                'Apoyos.descripcion as apoyo_descripcion',
                'Apoyos.monto_maximo',
                'Apoyos.fecha_inicio as apoyo_fecha_inicio',
                'Apoyos.fecha_fin as apoyo_fecha_fin',
                'Cat_EstadosSolicitud.nombre_estado as estado_nombre',
            ])
            ->get();

        $foliosSolicitudes = $solicitudes->pluck('folio')->all();
        $documentosPorFolio = collect();

        if (! empty($foliosSolicitudes)) {
            $documentosPorFolio = DB::table('Documentos_Expediente')
                ->select([
                    'fk_folio',
                    DB::raw('COUNT(*) as total_documentos'),
                    DB::raw("SUM(CASE WHEN admin_status = 'aceptado' THEN 1 ELSE 0 END) as aceptados"),
                    DB::raw("SUM(CASE WHEN admin_status = 'rechazado' THEN 1 ELSE 0 END) as rechazados"),
                    DB::raw("SUM(CASE WHEN admin_status = 'pendiente' OR admin_status IS NULL THEN 1 ELSE 0 END) as pendientes"),
                ])
                ->whereIn('fk_folio', $foliosSolicitudes)
                ->groupBy('fk_folio')
                ->get()
                ->keyBy('fk_folio');
        }

        $solicitudes = $solicitudes->map(function ($solicitud) use ($documentosPorFolio) {
                $documentos = $documentosPorFolio->get($solicitud->folio);

                $solicitud->fecha_creacion = ! empty($solicitud->fecha_creacion) ? Carbon::parse($solicitud->fecha_creacion) : null;
                $solicitud->fecha_actualizacion = ! empty($solicitud->fecha_actualizacion) ? Carbon::parse($solicitud->fecha_actualizacion) : null;
                $solicitud->fecha_confirmacion_presupuesto = ! empty($solicitud->fecha_confirmacion_presupuesto) ? Carbon::parse($solicitud->fecha_confirmacion_presupuesto) : null;
                $solicitud->fecha_entrega_recurso = ! empty($solicitud->fecha_entrega_recurso) ? Carbon::parse($solicitud->fecha_entrega_recurso) : null;
                $solicitud->fecha_cierre_financiero = ! empty($solicitud->fecha_cierre_financiero) ? Carbon::parse($solicitud->fecha_cierre_financiero) : null;
                $solicitud->apoyo_fecha_inicio = ! empty($solicitud->apoyo_fecha_inicio) ? Carbon::parse($solicitud->apoyo_fecha_inicio) : null;
                $solicitud->apoyo_fecha_fin = ! empty($solicitud->apoyo_fecha_fin) ? Carbon::parse($solicitud->apoyo_fecha_fin) : null;

                [$faseDocumentalEtiqueta, $faseDocumentalClasses, $faseDocumentalIcon] = $this->determinarFaseDocumental(
                    (int) ($documentos->total_documentos ?? 0),
                    (int) ($documentos->aceptados ?? 0),
                    (int) ($documentos->rechazados ?? 0),
                    (int) ($documentos->pendientes ?? 0),
                    ! empty($solicitud->cuv)
                );

                $solicitud->fase_documental_etiqueta = $faseDocumentalEtiqueta;
                $solicitud->fase_documental_classes = $faseDocumentalClasses;
                $solicitud->fase_documental_icon = $faseDocumentalIcon;

                $estadoNombreNormalizado = mb_strtolower(trim((string) ($solicitud->estado_nombre ?? '')));
                $esRechazada = str_contains($estadoNombreNormalizado, 'rechaz')
                    || str_contains($estadoNombreNormalizado, 'incorrect')
                    || str_contains($estadoNombreNormalizado, 'cancel')
                    || str_contains($estadoNombreNormalizado, 'deneg');

                // El CUV solo existe después de la firma del directivo; antes de eso sigue en proceso.
                $esAprobada = ! empty($solicitud->cuv);

                $solicitud->estado_clave = $esRechazada ? 'rechazada' : ($esAprobada ? 'aprobada' : 'proceso');
                $solicitud->estado_etiqueta = $esRechazada
                    ? 'Rechazada'
                    : ($esAprobada ? 'Aprobada' : 'En proceso');
                $solicitud->ultima_actualizacion = $solicitud->fecha_actualizacion ?: $solicitud->fecha_creacion;
                $solicitud->ultima_actualizacion_formatted = $solicitud->ultima_actualizacion?->format('d/m/Y H:i') ?? '—';
                $solicitud->fecha_solicitud_formatted = $solicitud->fecha_creacion?->format('d/m/Y H:i') ?? '—';
                $solicitud->fecha_confirmacion_formatted = $solicitud->fecha_confirmacion_presupuesto?->format('d/m/Y H:i') ?? 'Pendiente';
                $solicitud->fecha_entrega_recurso_formatted = $solicitud->fecha_entrega_recurso?->format('d/m/Y H:i') ?? 'Pendiente';
                $solicitud->fecha_cierre_financiero_formatted = $solicitud->fecha_cierre_financiero?->format('d/m/Y H:i') ?? 'Pendiente';
                $solicitud->apoyo_vigencia_formatted = sprintf(
                    '%s al %s',
                    $solicitud->apoyo_fecha_inicio?->format('d/m/Y') ?? '—',
                    $solicitud->apoyo_fecha_fin?->format('d/m/Y') ?? '—',
                );
                $solicitud->monto_maximo_formatted = isset($solicitud->monto_maximo) && $solicitud->monto_maximo !== null
                    ? '$' . number_format((float) $solicitud->monto_maximo, 2, '.', ',') . ' MXN'
                    : '—';
                $solicitud->monto_entregado_formatted = isset($solicitud->monto_entregado) && $solicitud->monto_entregado !== null
                    ? '$' . number_format((float) $solicitud->monto_entregado, 2, '.', ',') . ' MXN'
                    : '—';
                $solicitud->descripcion_resumida = trim(strip_tags((string) ($solicitud->apoyo_descripcion ?? '')));
                $solicitud->descripcion_resumida = $solicitud->descripcion_resumida !== ''
                    ? Str::limit($solicitud->descripcion_resumida, 220)
                    : '';

                $solicitud->card_classes = $esAprobada
                    ? 'border-emerald-200 bg-emerald-50/80 ring-1 ring-emerald-200/60'
                    : ($esRechazada
                        ? 'border-rose-200 bg-rose-50/80 ring-1 ring-rose-200/60'
                        : 'border-amber-200 bg-amber-50/80 ring-1 ring-amber-200/60');
                $solicitud->accent_classes = $esAprobada
                    ? 'bg-emerald-500'
                    : ($esRechazada ? 'bg-rose-500' : 'bg-amber-500');
                $solicitud->badge_classes = $esAprobada
                    ? 'bg-emerald-100 text-emerald-800 ring-emerald-200'
                    : ($esRechazada ? 'bg-rose-100 text-rose-800 ring-rose-200' : 'bg-amber-100 text-amber-800 ring-amber-200');
                $solicitud->badge_icon = $esAprobada ? '✓' : ($esRechazada ? '✗' : '⏳');

                return $solicitud;
            });

        $solicitudesVisibles = $estadoFiltro === 'total'
            ? $solicitudes
            : $solicitudes->where('estado_clave', $estadoFiltro)->values();

        $resumen = [
            'total' => $solicitudes->count(),
            'aprobadas' => $solicitudes->where('estado_clave', 'aprobada')->count(),
            'proceso' => $solicitudes->where('estado_clave', 'proceso')->count(),
            'rechazadas' => $solicitudes->where('estado_clave', 'rechazada')->count(),
        ];

        $estadoFiltroEtiqueta = match ($estadoFiltro) {
            'aprobada' => 'Aprobadas',
            'proceso' => 'En proceso',
            'rechazada' => 'Rechazadas',
            default => 'Todas las solicitudes',
        };

        return view('solicitudes.historial', [
            'user' => $user,
            'solicitudes' => $solicitudesVisibles,
            'resumen' => $resumen,
            'estadoFiltro' => $estadoFiltro,
            'estadoFiltroEtiqueta' => $estadoFiltroEtiqueta,
        ]);
    }

    public function guardar(Request $request)
    {
        $user = $request->user()->loadMissing('beneficiario');
        $curpBeneficiario = $user->beneficiario?->curp;
        $esPersonal = $user->personal && in_array((int) $user->personal->fk_rol, [1, 2], true);
        $captchaRules = app()->environment('testing') ? ['nullable'] : ['required', new Recaptcha];
        $soloRechazados = $request->boolean('solo_rechazados');
        $folioRechazado = (int) $request->input('folio_rechazado', 0);

        if ((! $user->isBeneficiario() || ! $curpBeneficiario) && ! ($soloRechazados && $esPersonal)) {
            return redirect()->back()->with('error', 'Debes iniciar sesion como beneficiario para registrar una solicitud.');
        }

        $request->validate([
            'apoyo' => ['required'],
            'g-recaptcha-response' => $captchaRules,
        ], [
            'g-recaptcha-response.required' => 'El token de seguridad es obligatorio.',
        ]);

        // Validar que el apoyo exista y esté activo
        $apoyo = DB::table('Apoyos')->where('id_apoyo', $request->apoyo)->first();
        if (! $apoyo || (int) ($apoyo->activo ?? 0) !== 1) {
            return redirect()->back()->with('error', 'El apoyo seleccionado no se encuentra disponible.');
        }

        if ($soloRechazados) {
            if ($folioRechazado <= 0) {
                return redirect()->back()->with('error', 'No se pudo identificar la solicitud para volver a cargar documentos rechazados.');
            }

            $solicitudReenvioQuery = DB::table('Solicitudes')
                ->where('folio', $folioRechazado)
                ->where('fk_id_apoyo', $request->apoyo);

            if ($curpBeneficiario) {
                $solicitudReenvioQuery->where('fk_curp', $curpBeneficiario);
            }

            $solicitudReenvio = $solicitudReenvioQuery->first();

            if (! $solicitudReenvio) {
                return redirect()->back()->with('error', 'No se encontró la solicitud indicada para reenvío de documentos.');
            }

            $requisitos = DB::table('Requisitos_Apoyo')
                ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                ->where('Requisitos_Apoyo.fk_id_apoyo', $request->apoyo)
                ->select([
                    'Requisitos_Apoyo.fk_id_tipo_doc',
                    'Requisitos_Apoyo.es_obligatorio',
                    'Cat_TiposDocumento.nombre_documento',
                    'Cat_TiposDocumento.tipo_archivo_permitido',
                    'Cat_TiposDocumento.validar_tipo_archivo',
                ])
                ->get();

            $tiposRechazados = DB::table('Documentos_Expediente')
                ->where('fk_folio', $folioRechazado)
                ->where(function ($query) {
                    $query->where('admin_status', 'rechazado')
                        ->orWhere('estado_validacion', 'RECHAZADO')
                        ->orWhere('estado_validacion', 'Incorrecto');
                })
                ->pluck('fk_id_tipo_doc')
                ->map(fn ($tipoId) => (int) $tipoId)
                ->all();

            if (empty($tiposRechazados)) {
                return redirect()->back()->with('error', 'No hay documentos rechazados para volver a cargar.');
            }

            $requisitos = $requisitos->whereIn('fk_id_tipo_doc', $tiposRechazados)->values();

            DB::beginTransaction();

            try {
                foreach ($requisitos as $req) {
                    $nombreInput = 'documento_' . $req->fk_id_tipo_doc;
                    $gdriveIdInput = 'gdrive_' . $req->fk_id_tipo_doc . '_id';
                    $gdriveNameInput = 'gdrive_' . $req->fk_id_tipo_doc . '_name';

                    $archivo = $request->file($nombreInput);
                    $gdriveFileId = $request->input($gdriveIdInput);
                    $gdriveFileName = $request->input($gdriveNameInput);

                    $tieneArchivoLocal = ! ! $archivo;
                    $tieneGdrive = ! ! $gdriveFileId;

                    if (! $tieneArchivoLocal && ! $tieneGdrive) {
                        throw new \RuntimeException('Falta volver a cargar el documento rechazado: ' . $req->nombre_documento . '.');
                    }

                    if ($tieneArchivoLocal) {
                        $debeValidarTipo = ! isset($req->validar_tipo_archivo) || (bool) $req->validar_tipo_archivo;
                        if ($debeValidarTipo) {
                            $tipo = $req->tipo_archivo_permitido ?? 'pdf';
                            $mimes = match ($tipo) {
                                'image' => 'jpg,jpeg,png,webp',
                                'word' => 'doc,docx',
                                'excel' => 'xls,xlsx,csv',
                                'zip' => 'zip,rar,7z',
                                'any' => null,
                                default => 'pdf',
                            };

                            if ($mimes) {
                                validator([
                                    $nombreInput => $archivo,
                                ], [
                                    $nombreInput => 'file|mimes:' . $mimes . '|max:10240',
                                ], [
                                    $nombreInput . '.mimes' => 'El archivo para "' . $req->nombre_documento . '" no coincide con el tipo permitido (' . strtoupper($tipo) . ').',
                                    $nombreInput . '.max' => 'El archivo para "' . $req->nombre_documento . '" excede el tamaño máximo permitido (10 MB).',
                                ])->validate();
                            }
                        }

                        $rutaDocumento = $archivo->store('solicitudes', 'public');
                        $origenDocumento = 'local';
                        $googleFileId = null;
                        $googleFileName = null;
                    } else {
                        $rutaDocumento = "google_drive/{$gdriveFileId}";
                        $origenDocumento = 'google_drive';
                        $googleFileId = $gdriveFileId;
                        $googleFileName = $gdriveFileName;
                    }

                    $documentoExistente = Documento::query()
                        ->where('fk_folio', $folioRechazado)
                        ->where('fk_id_tipo_doc', $req->fk_id_tipo_doc)
                        ->where(function ($query) {
                            $query->where('admin_status', 'rechazado')
                                ->orWhere('estado_validacion', 'RECHAZADO')
                                ->orWhere('estado_validacion', 'Incorrecto');
                        })
                        ->orderByDesc('id_doc')
                        ->first();

                    $payloadDocumento = [
                        'fk_folio' => $folioRechazado,
                        'fk_id_tipo_doc' => $req->fk_id_tipo_doc,
                        'ruta_archivo' => $rutaDocumento,
                        'origen_archivo' => $origenDocumento,
                        'google_file_id' => $googleFileId,
                        'google_file_name' => $googleFileName,
                        'estado_validacion' => 'Pendiente',
                        'admin_status' => 'pendiente',
                        'admin_observations' => null,
                        'verification_token' => null,
                        'id_admin' => null,
                        'fecha_verificacion' => null,
                        'version' => $documentoExistente ? ((int) ($documentoExistente->version ?? 1) + 1) : 1,
                        'fecha_carga' => now(),
                    ];

                    if ($documentoExistente) {
                        DB::table('Documentos_Expediente')
                            ->where('id_doc', $documentoExistente->id_doc)
                            ->update($payloadDocumento);
                    } else {
                        Documento::create($payloadDocumento);
                    }
                }

                DB::commit();

                return redirect()->route('apoyos.comments', [
                    'id' => $request->apoyo,
                    'origen' => 'solicitud',
                    'folio' => $folioRechazado,
                ])->with('success', 'Documentos rechazados reenviados correctamente.');
            } catch (\Exception $e) {
                DB::rollBack();

                return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
            }
        }

        // NUEVO: Validar que hay presupuesto disponible en la categoría
        $apoyoModel = \App\Models\Apoyo::find($request->apoyo);
        $estadoPresupuesto = $this->presupuestoService->obtenerEstadoDetalladoApoyo($apoyoModel);
        
        if ($estadoPresupuesto['estado'] === 'AGOTADO') {
            return redirect()->back()->with('error', 
                'No hay presupuesto disponible para el apoyo: ' . $estadoPresupuesto['disponible_formato'] . ' disponible.');
        }

        // Validar que no haya solicitud activa previa para este apoyo
        $solicitudActiva = DB::table('Solicitudes')
            ->join('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->where('Solicitudes.fk_curp', $curpBeneficiario)
            ->where('Solicitudes.fk_id_apoyo', $request->apoyo)
            ->whereNotIn('Cat_EstadosSolicitud.nombre_estado', ['Rechazada'])
            ->exists();

        if ($solicitudActiva) {
            return redirect()->back()->with('error', 'Ya tienes una solicitud en proceso para este apoyo.');
        }

        // Validar fechas de inicio y fin del apoyo
        $hoy = now();
        if (! empty($apoyo->fecha_inicio) && $hoy->lt(Carbon::parse($apoyo->fecha_inicio))) {
            return redirect()->back()->with('error', 'El periodo de recepcion aun no inicia para este apoyo.');
        }

        if (! empty($apoyo->fecha_fin) && $hoy->gt(Carbon::parse($apoyo->fecha_fin))) {
            return redirect()->back()->with('error', 'El periodo de recepcion ya finalizo para este apoyo.');
        }

        // Validar que estemos en el hito de recepción
        if (Schema::hasTable('Hitos_Apoyo')) {
            $hitos = DB::table('Hitos_Apoyo')
                ->where('fk_id_apoyo', $request->apoyo)
                ->where('activo', 1)
                ->orderBy('orden_hito')
                ->get();

            if ($hitos->isNotEmpty()) {
                $hitoActual = $hitos->first();
                foreach ($hitos as $hito) {
                    $inicio = $hito->fecha_inicio ? Carbon::parse($hito->fecha_inicio) : null;
                    $fin = $hito->fecha_fin ? Carbon::parse($hito->fecha_fin) : null;
                    if ($inicio && $fin && $hoy->betweenIncluded($inicio, $fin)) {
                        $hitoActual = $hito;
                        break;
                    }
                }

                if (!str_contains(strtoupper((string) $hitoActual->clave_hito), 'RECEPCION')) {
                    return redirect()->back()->with('error', 'No se pueden registrar solicitudes fuera del hito de recepcion.');
                }
            }
        }

        DB::beginTransaction();

        try {
            // Generar folio institucional con dígito verificador
            $folioInstitucional = $this->folioService->generarFolioInstitucional($user->id_usuario);

            $payloadSolicitud = [
                'fk_curp' => $curpBeneficiario,
                'fk_id_apoyo' => $request->apoyo,
                'fk_id_estado' => 1,
                'folio_institucional' => $folioInstitucional,
                'fecha_creacion' => now(),
            ];

            if (Schema::hasColumn('Solicitudes', 'permite_correcciones')) {
                $payloadSolicitud['permite_correcciones'] = 1;
            }

            $folio = DB::table('Solicitudes')->insertGetId($payloadSolicitud, 'folio');

            // Registrar en auditoría que el folio institucional fue usado (si tabla existe)
            if (\Illuminate\Support\Facades\Schema::hasTable('auditoria_folios')) {
                try {
                    DB::table('auditoria_folios')
                        ->where('folio_completo', $folioInstitucional)
                        ->update(['fk_folio_solicitud' => $folio]);
                } catch (\Exception $e) {
                    \Log::warning('No se pudo actualizar auditoría_folios', [
                        'folio' => $folioInstitucional,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            // NUEVO: Reservar presupuesto para la solicitud
            $solicitud = \App\Models\Solicitud::find($folio);
            if ($solicitud) {
                // Obtener monto máximo del apoyo para reservar
                $montoMaximo = (float) ($apoyoModel->monto_maximo ?? 0);
                
                if ($montoMaximo > 0) {
                    // Intentar reservar presupuesto
                    if (!$this->presupuestoService->reservarPresupuesto($solicitud, $montoMaximo, $user->id_usuario ?? null)) {
                        // Si falla la reserva, cancelar la solicitud
                        throw new \RuntimeException('No fue posible reservar presupuesto para la solicitud. Presupuesto insuficiente en la categoría.');
                    }
                }
            }

            $requisitos = DB::table('Requisitos_Apoyo')
                ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                ->where('fk_id_apoyo', $request->apoyo)
                ->select([
                    'Requisitos_Apoyo.fk_id_tipo_doc',
                    'Requisitos_Apoyo.es_obligatorio',
                    'Cat_TiposDocumento.nombre_documento',
                    'Cat_TiposDocumento.tipo_archivo_permitido',
                    'Cat_TiposDocumento.validar_tipo_archivo',
                ])
                ->get();

            foreach ($requisitos as $req) {
                $nombreInput = 'documento_' . $req->fk_id_tipo_doc;
                $gdriveIdInput = 'gdrive_' . $req->fk_id_tipo_doc . '_id';
                $gdriveNameInput = 'gdrive_' . $req->fk_id_tipo_doc . '_name';

                $archivo = $request->file($nombreInput);
                $gdriveFileId = $request->input($gdriveIdInput);
                $gdriveFileName = $request->input($gdriveNameInput);

                // Determinar si se adjuntó desde local o Google Drive
                $tieneArchivoLocal = ! ! $archivo;
                $tieneGdrive = ! ! $gdriveFileId;

                if (! $tieneArchivoLocal && ! $tieneGdrive) {
                    if ((int) $req->es_obligatorio === 1) {
                        throw new \RuntimeException('Falta adjuntar el documento obligatorio: ' . $req->nombre_documento . '.');
                    }
                    continue;
                }

                // Validar tipo de archivo si viene desde local
                if ($tieneArchivoLocal) {
                    $debeValidarTipo = ! isset($req->validar_tipo_archivo) || (bool) $req->validar_tipo_archivo;
                    if ($debeValidarTipo) {
                        $tipo = $req->tipo_archivo_permitido ?? 'pdf';
                        $mimes = match ($tipo) {
                            'image' => 'jpg,jpeg,png,webp',
                            'word' => 'doc,docx',
                            'excel' => 'xls,xlsx,csv',
                            'zip' => 'zip,rar,7z',
                            'any' => null,
                            default => 'pdf',
                        };

                        if ($mimes) {
                            validator([
                                $nombreInput => $archivo,
                            ], [
                                $nombreInput => 'file|mimes:' . $mimes . '|max:10240',
                            ], [
                                $nombreInput . '.mimes' => 'El archivo para "' . $req->nombre_documento . '" no coincide con el tipo permitido (' . strtoupper($tipo) . ').',
                                $nombreInput . '.max' => 'El archivo para "' . $req->nombre_documento . '" excede el tamaño máximo permitido (10 MB).',
                            ])->validate();
                        }
                    }

                    $rutaArchivo = $archivo->store('solicitudes', 'public');

                    DB::table('Documentos_Expediente')->insert([
                        'fk_folio' => $folio,
                        'fk_id_tipo_doc' => $req->fk_id_tipo_doc,
                        'ruta_archivo' => str_replace('storage/', '', $rutaArchivo),
                        'origen_archivo' => 'local',
                        'estado_validacion' => 'Pendiente',
                        'version' => 1,
                        'fecha_carga' => DB::raw('CURRENT_TIMESTAMP')
                    ]);
                } else {
                    // Guardar referencia de Google Drive
                    DB::table('Documentos_Expediente')->insert([
                        'fk_folio' => $folio,
                        'fk_id_tipo_doc' => $req->fk_id_tipo_doc,
                        'ruta_archivo' => "google_drive/{$gdriveFileId}",
                        'origen_archivo' => 'google_drive',
                        'google_file_id' => $gdriveFileId,
                        'google_file_name' => $gdriveFileName,
                        'estado_validacion' => 'Pendiente',
                        'version' => 1,
                        'fecha_carga' => DB::raw('CURRENT_TIMESTAMP')
                    ]);
                }
            }

            if (Schema::hasTable('Seguimiento_Solicitud')) {
                DB::table('Seguimiento_Solicitud')->insert([
                    'fk_folio' => $folio,
                    'estado_proceso' => 'EN_PROCESO',
                    'fecha_creacion' => DB::raw('GETDATE()'),
                    'fecha_actualizacion' => DB::raw('GETDATE()'),
                ]);
            }

            DB::commit();

            return redirect()->back()->with('exito', true);
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Error al guardar: ' . $e->getMessage());
        }
    }

    private function determinarFaseDocumental(int $totalDocumentos, int $aceptados, int $rechazados, int $pendientes, bool $firmada): array
    {
        if ($totalDocumentos <= 0) {
            return ['Sin documentos', 'bg-slate-100 text-slate-700 ring-slate-200', '○'];
        }

        if ($rechazados > 0) {
            return ['Subsanación requerida', 'bg-rose-100 text-rose-800 ring-rose-200', '✗'];
        }

        if ($aceptados === $totalDocumentos) {
            return $firmada
                ? ['Aprobada por directivo', 'bg-emerald-100 text-emerald-800 ring-emerald-200', '✓']
                : ['En validación del directivo', 'bg-blue-100 text-blue-800 ring-blue-200', '⏳'];
        }

        if ($pendientes === $totalDocumentos) {
            return ['En revisión documental', 'bg-amber-100 text-amber-800 ring-amber-200', '⏳'];
        }

        return ['En revisión documental', 'bg-amber-100 text-amber-800 ring-amber-200', '⏳'];
    }
}