<?php

namespace App\Http\Controllers;

use App\Events\NotificacionGenerada;
use App\Events\SolicitudRechazada;
use App\Events\HitoCambiado;
use App\Jobs\CopiarDocumentoExpedienteJob;
use App\Services\FirmaElectronicaService;
use App\Services\PresupuetaryIntegrationService;
use App\Services\PresupuestaryControlService;
use App\Services\InventarioValidationService;
use App\Services\SolicitudWorkflowService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class SolicitudProcesoController extends Controller
{
    public function __construct(
        private readonly SolicitudWorkflowService $workflow,
        private readonly PresupuetaryIntegrationService $presupuetoIntegration,
        private readonly FirmaElectronicaService $firmaService,
        private readonly PresupuestaryControlService $presupuestaryControl,
        private readonly InventarioValidationService $inventarioValidation
    ) {
    }

    public function index(Request $request)
    {
        $this->authorizePersonal($request);

        $solicitudes = DB::table('Solicitudes')
            ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
            ->leftJoin('Cat_EstadosSolicitud', 'Solicitudes.fk_id_estado', '=', 'Cat_EstadosSolicitud.id_estado')
            ->select([
                'Solicitudes.folio',
                'Solicitudes.fk_id_apoyo',
                'Solicitudes.fk_curp',
                'Solicitudes.fk_id_estado',
                'Solicitudes.permite_correcciones',
                'Solicitudes.cuv',
                'Solicitudes.folio_institucional',
                'Solicitudes.fecha_creacion',
                'Solicitudes.presupuesto_confirmado',
                'Solicitudes.monto_entregado',
                'Apoyos.nombre_apoyo',
                'Beneficiarios.nombre',
                'Beneficiarios.apellido_paterno',
                'Beneficiarios.apellido_materno',
                'Cat_EstadosSolicitud.nombre_estado as estado',
            ])
            ->orderByDesc('Solicitudes.folio')
            ->limit(30)
            ->get();

        $solicitudesConTimeline = $solicitudes->map(function ($solicitud) {
            $timelineResult = $this->workflow->getTimelineByFolio((int) $solicitud->folio);
            $solicitud->timeline = $timelineResult['timeline'];
            $solicitud->hito_actual = $timelineResult['hito_actual'];
            return $solicitud;
        });

        return view('solicitudes.proceso', [
            'solicitudes' => $solicitudesConTimeline,
        ]);
    }

    public function timeline(Request $request, int $folio)
    {
        $this->authorizePersonal($request);

        return response()->json($this->workflow->getTimelineByFolio($folio));
    }

    public function revisarDocumento(Request $request)
    {
        $user = $this->authorizePersonal($request);

        try {
            $data = $request->validate([
                'id_documento' => ['required', 'integer', 'exists:Documentos_Expediente,id_doc'],
                'accion' => ['required', 'in:aprobar,observar,rechazar'],
                'observaciones' => ['nullable', 'string'],
                'permite_correcciones' => ['nullable', 'boolean'],
                'webview_link' => ['nullable', 'string', 'max:500'],
                'official_file_id' => ['nullable', 'string', 'max:200'],
                'source_file_id' => ['nullable', 'string', 'max:200'],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            $message = 'Errores de validación: ' . implode(', ', collect($e->errors())->flatten()->all());
            if ($request->expectsJson()) {
                return response()->json(['exito' => false, 'mensaje' => $message], 422);
            }
            return back()->withErrors($e->errors());
        }

        $documento = DB::table('Documentos_Expediente')->where('id_doc', $data['id_documento'])->first();
        if (! $documento) {
            $mensaje = 'No se encontro el documento.';
            if ($request->expectsJson()) return response()->json(['exito' => false, 'mensaje' => $mensaje], 404);
            return back()->with('error', $mensaje);
        }

        $this->workflow->assertHitoActual((int) $documento->fk_folio, 'ANALISIS_ADMIN');

        $estado = match ($data['accion']) {
            'aprobar' => 'Correcto',
            'observar' => 'Pendiente',
            'rechazar' => 'Incorrecto',
            default => 'Pendiente',
        };

        DB::beginTransaction();

        try {
            DB::table('Documentos_Expediente')
                ->where('id_doc', $data['id_documento'])
                ->update([
                    'estado_validacion' => $estado,
                    'observaciones_revision' => $data['observaciones'] ?? null,
                    'webview_link' => $data['webview_link'] ?? $documento->webview_link,
                    'official_file_id' => $data['official_file_id'] ?? $documento->official_file_id,
                    'source_file_id' => $data['source_file_id'] ?? $documento->source_file_id,
                    'revisado_por' => $user->id_usuario,
                    'fecha_revision' => now(),
                ]);

            if ($data['accion'] === 'rechazar') {
                $permiteCorrecciones = filter_var($data['permite_correcciones'] ?? true, FILTER_VALIDATE_BOOLEAN);
                DB::table('Solicitudes')
                    ->where('folio', $documento->fk_folio)
                    ->update([
                        'permite_correcciones' => $permiteCorrecciones ? 1 : 0,
                        'fk_id_estado' => $permiteCorrecciones ? 2 : 4,
                        'fecha_actualizacion' => now(),
                        'observaciones_internas' => $data['observaciones'] ?? null,
                    ]);
            }

            if ($data['accion'] === 'aprobar') {
                DB::table('Solicitudes')
                    ->where('folio', $documento->fk_folio)
                    ->update([
                        'fk_id_estado' => 2,
                        'fecha_actualizacion' => now(),
                    ]);

                // 🧪 COMENTADO PARA PRUEBAS: Job requiere tabla 'jobs' que no existe
                // CopiarDocumentoExpedienteJob::dispatch((int) $documento->id_doc);
                
                // Verificar si TODOS los documentos están aprobados
                $todosAprobados = $this->verificarTodosDocumentosAprobados((int) $documento->fk_folio);
                if ($todosAprobados) {
                    // Completar Fase 1
                    DB::table('Solicitudes')
                        ->where('folio', $documento->fk_folio)
                        ->update(['presupuesto_confirmado' => 1]);
                }
            }

            if ($data['accion'] === 'observar') {
                $this->crearNotificacionBeneficiario((int) $documento->fk_folio, 'Tu documento fue observado por el area administrativa.', 'documento_observado');
            }

            DB::commit();

            // Verificar estado final para respuesta
            $todosAprobados = $this->verificarTodosDocumentosAprobados((int) $documento->fk_folio);
            $mensaje = 'Documento actualizado correctamente.';
            
            if ($request->expectsJson()) {
                return response()->json([
                    'exito' => true,
                    'mensaje' => $mensaje,
                    'fase1_completada' => $todosAprobados,
                    'accion' => $data['accion'],
                ]);
            }
            
            return back()->with('status', $mensaje);
        } catch (\Throwable $e) {
            DB::rollBack();
            $mensaje = 'No fue posible actualizar el documento: ' . $e->getMessage();
            if ($request->expectsJson()) {
                return response()->json(['exito' => false, 'mensaje' => $mensaje], 422);
            }
            return back()->with('error', $mensaje);
        }
    }

    /**
     * Verificar si todos los documentos de una solicitud están aprobados
     */
    private function verificarTodosDocumentosAprobados(int $folio): bool
    {
        $totalDocumentos = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->count();

        $documentosAprobados = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where('estado_validacion', 'Correcto')
            ->count();

        return $totalDocumentos > 0 && $totalDocumentos === $documentosAprobados;
    }

    public function firmaDirectiva(Request $request)
    {
        $user = $this->authorizePersonal($request, 2);

        $data = $request->validate([
            'folio' => ['required', 'integer', 'exists:Solicitudes,folio'],
            'password' => ['required', 'string'],
        ]);

        $folio = (int) $data['folio'];

        // ⚠️ PRE-VALIDATION: Validar presupuesto ANTES de firma
        // Esto previene que se firme una solicitud sin presupuesto disponible
        try {
            $validacionPresupuesto = $this->presupuestaryControl->validarPresupuestoParaSolicitud($folio);
            if (!$validacionPresupuesto['valido']) {
                return back()->with('error', 'Presupuesto insuficiente: ' . $validacionPresupuesto['mensaje']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error validando presupuesto: ' . $e->getMessage());
        }

        // ⚠️ PRE-VALIDATION: Validar inventario para apoyos tipo Especie
        // Esto previene que se apruebe una solicitud sin inventario suficiente
        try {
            $validacionInventario = $this->inventarioValidation->validarInventarioParaSolicitud($folio);
            if (!$validacionInventario['valido']) {
                return back()->with('error', 'Inventario insuficiente: ' . $validacionInventario['razon']);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Error validando inventario: ' . $e->getMessage());
        }

        // Usar FirmaElectronicaService para manejar la firma
        $resultado = $this->firmaService->firmarSolicitud(
            $folio,
            $user,  // Ya está como App\Models\User
            $data['password']
        );

        if (!$resultado['exitoso']) {
            return back()->with('error', $resultado['mensaje']);
        }

        // ✅ TRANSACCIÓN: Asignar presupuesto tras firma exitosa
        // Esta es la operación IRREVERSIBLE que marca presupuesto como confirmado
        try {
            $this->presupuestaryControl->asignarPresupuestoSolicitud($folio, $user->id_usuario);
            
            // 📦 REGISTRO: Registrar SALIDA de inventario tras presupuesto asignado
            // Esto decrementa stock_actual en BD_Inventario
            $resultadoMovimiento = $this->inventarioValidation->registrarSalidaInventario($folio, $user->id_usuario);
            if (!$resultadoMovimiento['exito']) {
                \Log::warning("Movimiento inventario falló para folio {$folio}: " . $resultadoMovimiento['mensaje']);
                // No revertir firma, pero notificar admin
                event(new NotificacionGenerada(
                    'admin',
                    'Error registro inventario',
                    "Solicitud {$folio} aprobada pero registro inventario falló: " . $resultadoMovimiento['mensaje']
                ));
            }
        } catch (\Exception $e) {
            // Log error pero no revertir firma (ya está en BD)
            \Log::error("Error asignando presupuesto para solicitud {$folio}: " . $e->getMessage());
            // Notificar admin
            event(new NotificacionGenerada(
                'admin',
                'Error presupuesto en firma',
                "Solicitud {$folio} firmada pero presupuesto falló: " . $e->getMessage()
            ));
        }

        // Notificar beneficiario de aprobación
        $this->crearNotificacionBeneficiario(
            $folio,
            'Tu apoyo fue autorizado por dirección. CUV: ' . $resultado['firma']['cuv'],
            'apoyo_autorizado'
        );

        return back()->with('status', $resultado['mensaje']);
    }

    /**
     * Rechazar solicitud con firma digital
     */
    public function rechazarSolicitud(Request $request)
    {
        $user = $this->authorizePersonal($request, 2);

        $data = $request->validate([
            'folio' => ['required', 'integer', 'exists:Solicitudes,folio'],
            'password' => ['required', 'string'],
            'motivo' => ['required', 'string', 'max:500'],
        ], [
            'motivo.required' => 'El motivo del rechazo es obligatorio.',
        ]);

        $folio = (int) $data['folio'];

        // Usar FirmaElectronicaService para manejar el rechazo
        $resultado = $this->firmaService->rechazarSolicitud(
            $folio,
            $user,
            $data['password'],
            $data['motivo']
        );

        if (!$resultado['exitoso']) {
            return back()->with('error', $resultado['mensaje']);
        }

        // ✅ LIBERACIÓN DE PRESUPUESTO: Al rechazar, liberar presupuesto reservado
        // Solo se libera si la solicitud fue previamente confirmada
        try {
            $this->presupuestaryControl->liberarPresupuestoSolicitud($folio);
        } catch (\Exception $e) {
            // Log error pero no revertir rechazo (ya está en BD)
            \Log::warning("Error liberando presupuesto para solicitud rechazada {$folio}: " . $e->getMessage());
        }

        // 🔔 Disparar evento de solicitud rechazada
        $solicitud = \App\Models\Solicitud::where('folio', $folio)->firstOrFail();
        event(new SolicitudRechazada(
            solicitud: $solicitud,
            motivo: $data['motivo']
        ));

        // Notificar beneficiario de rechazo
        $this->crearNotificacionBeneficiario(
            $folio,
            'Tu solicitud fue rechazada. Motivo: ' . $data['motivo'],
            'apoyo_rechazado'
        );

        return back()->with('status', $resultado['mensaje']);
    }

    /**
     * Verificar integridad de firma electrónica
     */
    public function verificarFirma(Request $request, string $cuv)
    {
        $resultado = $this->firmaService->verificarFirma($cuv);

        return response()->json($resultado);
    }

    public function cierreFinanciero(Request $request)
    {
        $user = $this->authorizePersonal($request);

        $data = $request->validate([
            'folio' => ['required', 'integer', 'exists:Solicitudes,folio'],
            'monto_entregado' => ['required', 'numeric', 'min:0'],
            'fecha_entrega_recurso' => ['required', 'date'],
            'ruta_pdf_final' => ['nullable', 'string', 'max:500'],
        ]);

        $this->workflow->assertHitoActual((int) $data['folio'], 'CIERRE');

        DB::beginTransaction();

        try {
            $solicitud = DB::table('Solicitudes')->where('folio', $data['folio'])->first();
            if (! $solicitud) {
                return back()->with('error', 'No se encontro la solicitud.');
            }

            $folioInstitucional = $solicitud->folio_institucional ?: $this->workflow->generarFolioInstitucional();

            DB::table('Solicitudes')
                ->where('folio', $data['folio'])
                ->update([
                    'monto_entregado' => $data['monto_entregado'],
                    'fecha_entrega_recurso' => $data['fecha_entrega_recurso'],
                    'fecha_cierre_financiero' => now(),
                    'folio_institucional' => $folioInstitucional,
                    'fecha_actualizacion' => now(),
                ]);

            DB::table('Seguimiento_Solicitud')
                ->where('fk_folio', $data['folio'])
                ->update([
                    'estado_proceso' => 'CERRADO',
                    'fecha_cierre' => now(),
                    'fecha_actualizacion' => now(),
                ]);

            $snapshot = $this->buildSnapshot((int) $data['folio']);

            DB::table('Historico_Cierre')->insert([
                'fk_folio' => $data['folio'],
                'fk_id_usuario_cierre' => $user->id_usuario,
                'snapshot_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'monto_entregado' => $data['monto_entregado'],
                'fecha_entrega' => $data['fecha_entrega_recurso'],
                'folio_institucional' => $folioInstitucional,
                'ruta_pdf_final' => $data['ruta_pdf_final'] ?? null,
                'fecha_creacion' => now(),
            ]);

            DB::commit();

            return back()->with('status', 'Cierre financiero completado. Folio institucional: ' . $folioInstitucional);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->with('error', 'No fue posible cerrar la solicitud: ' . $e->getMessage());
        }
    }

    public function exportPadron(Request $request)
    {
        $this->authorizePersonal($request);

        $format = strtolower((string) $request->query('format', 'csv'));
        $rows = DB::table('Solicitudes')
            ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
            ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->whereNotNull('Solicitudes.fecha_cierre_financiero')
            ->select([
                'Solicitudes.folio',
                'Solicitudes.folio_institucional',
                'Solicitudes.cuv',
                'Apoyos.nombre_apoyo',
                'Beneficiarios.curp',
                'Beneficiarios.nombre',
                'Beneficiarios.apellido_paterno',
                'Beneficiarios.apellido_materno',
                'Solicitudes.monto_entregado',
                'Solicitudes.fecha_entrega_recurso',
            ])
            ->orderByDesc('Solicitudes.folio')
            ->get();

        $headers = [
            'folio',
            'folio_institucional',
            'cuv',
            'apoyo',
            'curp',
            'nombre',
            'monto_entregado',
            'fecha_entrega_recurso',
        ];

        $filename = 'padron_beneficiarios_' . now()->format('Ymd_His') . ($format === 'xls' ? '.xls' : '.csv');

        $contentType = $format === 'xls'
            ? 'application/vnd.ms-excel; charset=UTF-8'
            : 'text/csv; charset=UTF-8';

        $separator = $format === 'xls' ? "\t" : ',';

        return response()->streamDownload(function () use ($rows, $headers, $separator) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");

            fputcsv($output, $headers, $separator);

            foreach ($rows as $row) {
                fputcsv($output, [
                    $row->folio,
                    $row->folio_institucional,
                    $row->cuv,
                    $row->nombre_apoyo,
                    $row->curp,
                    trim($row->nombre . ' ' . $row->apellido_paterno . ' ' . $row->apellido_materno),
                    $row->monto_entregado,
                    $row->fecha_entrega_recurso,
                ], $separator);
            }

            fclose($output);
        }, $filename, [
            'Content-Type' => $contentType,
        ]);
    }

    public function validarPublico(Request $request)
    {
        $cuv = strtoupper(trim((string) $request->input('cuv', $request->query('cuv', ''))));
        $resultado = null;

        if ($cuv !== '') {
            $resultado = DB::table('Solicitudes')
                ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
                ->leftJoin('Historico_Cierre', 'Solicitudes.folio', '=', 'Historico_Cierre.fk_folio')
                ->where('Solicitudes.cuv', $cuv)
                ->select([
                    'Solicitudes.folio',
                    'Solicitudes.cuv',
                    'Solicitudes.folio_institucional',
                    'Solicitudes.monto_entregado',
                    'Solicitudes.fecha_entrega_recurso',
                    'Solicitudes.fecha_cierre_financiero',
                    'Apoyos.nombre_apoyo',
                    'Historico_Cierre.fecha_creacion as fecha_snapshot',
                ])
                ->first();
        }

        return view('solicitudes.validar-publico', [
            'cuv' => $cuv,
            'resultado' => $resultado,
        ]);
    }

    private function authorizePersonal(Request $request, ?int $requiredRole = null): object
    {
        $user = $request->user()?->loadMissing('personal');

        if (! $user || ! $user->isPersonal()) {
            abort(403, 'Solo personal autorizado puede acceder a este modulo.');
        }

        if ($requiredRole !== null && (int) ($user->personal->fk_rol ?? 0) !== $requiredRole) {
            abort(403, 'El usuario no cuenta con el rol requerido para esta accion.');
        }

        return $user;
    }

    private function crearNotificacionBeneficiario(int $folio, string $mensaje, string $evento): void
    {
        $solicitud = DB::table('Solicitudes')->where('folio', $folio)->first();
        if (! $solicitud) {
            return;
        }

        $beneficiario = DB::table('Beneficiarios')->where('curp', $solicitud->fk_curp)->first();
        if (! $beneficiario || ! $beneficiario->fk_id_usuario) {
            return;
        }

        DB::table('Notificaciones')->insert([
            'id_beneficiario' => $beneficiario->id_beneficiario,
            'tipo' => $evento,
            'titulo' => '✅ Apoyo Autorizado',
            'mensaje' => $mensaje,
            'datos' => json_encode(['folio' => $folio, 'evento' => $evento]),
            'leida' => 0,
        ]);

        $emailDestino = DB::table('Usuarios')
            ->where('id_usuario', $beneficiario->fk_id_usuario)
            ->value('email');

        if (! empty($emailDestino)) {
            try {
                Mail::raw($mensaje, function ($mail) use ($emailDestino, $evento) {
                    $mail->to($emailDestino)
                        ->subject('SIGO - Notificacion: ' . strtoupper(str_replace('_', ' ', $evento)));
                });
            } catch (\Throwable) {
                // Evita interrumpir el proceso cuando no hay SMTP configurado.
            }
        }

        try {
            event(new NotificacionGenerada((int) $beneficiario->fk_id_usuario, $mensaje, $evento, [
                'folio' => $folio,
            ]));
        } catch (\Throwable) {
            // Evita interrumpir el proceso cuando broadcasting no este configurado.
        }
    }

    private function buildSnapshot(int $folio): array
    {
        $solicitud = DB::table('Solicitudes')->where('folio', $folio)->first();

        $apoyo = $solicitud
            ? DB::table('Apoyos')->where('id_apoyo', $solicitud->fk_id_apoyo)->first()
            : null;

        $beneficiario = $solicitud
            ? DB::table('Beneficiarios')->where('curp', $solicitud->fk_curp)->first()
            : null;

        $documentos = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->orderBy('id_doc')
            ->get();

        $seguimiento = DB::table('Seguimiento_Solicitud')->where('fk_folio', $folio)->first();

        return [
            'solicitud' => $solicitud,
            'apoyo' => $apoyo,
            'beneficiario' => $beneficiario,
            'documentos' => $documentos,
            'seguimiento' => $seguimiento,
            'timestamp_snapshot' => now()->toIso8601String(),
        ];
    }
}
