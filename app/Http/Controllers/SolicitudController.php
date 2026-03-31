<?php

namespace App\Http\Controllers;

use App\Rules\Recaptcha;
use App\Services\PresupuestoService;
use App\Services\FolioService;
use Carbon\Carbon;
use Illuminate\Http\Request;
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

        if (! $user->isBeneficiario() || ! $curpBeneficiario) {
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

        return view('solicitudes.create', compact('apoyo', 'requisitos', 'solicitudActiva', 'estadoPresupuesto'));
    }

    public function guardar(Request $request)
    {
        $user = $request->user()->loadMissing('beneficiario');
        $curpBeneficiario = $user->beneficiario?->curp;
        $captchaRules = app()->environment('testing') ? ['nullable'] : ['required', new Recaptcha];

        if (! $user->isBeneficiario() || ! $curpBeneficiario) {
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

            // Registrar en auditoría que el folio institucional fue usado
            DB::table('auditoria_folios')
                ->where('folio_completo', $folioInstitucional)
                ->update(['fk_folio_solicitud' => $folio]);

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
                        'ruta_archivo' => $rutaArchivo,
                        'origen_archivo' => 'local',
                        'estado_validacion' => 'Pendiente',
                        'version' => 1,
                        'fecha_carga' => DB::raw('GETDATE()')
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
                        'fecha_carga' => DB::raw('GETDATE()')
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
}