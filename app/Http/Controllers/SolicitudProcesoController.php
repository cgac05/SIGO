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
use Illuminate\Support\Facades\Auth;
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

        // Obtener filtros
        $folio = $request->query('folio');
        $estado = $request->query('estado');
        $apoyo = $request->query('apoyo');
        $beneficiario = $request->query('beneficiario');
        $tab = $request->query('tab', 'pendientes'); // Filtro de tab: pendientes o firmadas

        // Base query
        $solicitudesQuery = DB::table('Solicitudes')
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
                'Apoyos.monto_maximo',
                'Beneficiarios.nombre as beneficiario_nombre',
                'Beneficiarios.apellido_paterno',
                'Beneficiarios.apellido_materno',
                'Cat_EstadosSolicitud.nombre_estado as nombre_estado',
            ]);

        // Aplicar filtros
        if ($folio) {
            $solicitudesQuery->where('Solicitudes.folio', $folio);
        }
        if ($estado) {
            // Cuando se filtra por estado, mapear a los valores correctos de la BD
            $estadoMap = [
                'ANALISIS_ADMIN' => 3,      // Estado ID 3
                'DOCUMENTOS_VERIFICADOS' => 9, // Estado ID 9
                'APROBADA' => 4,             // Estado ID 4
                'RECHAZADA' => 5,            // Estado ID 5
            ];
            
            if (isset($estadoMap[$estado])) {
                $solicitudesQuery->where('Solicitudes.fk_id_estado', $estadoMap[$estado]);
            }
        }
        if ($apoyo) {
            $solicitudesQuery->where('Solicitudes.fk_id_apoyo', $apoyo);
        }
        if ($beneficiario) {
            $solicitudesQuery->where(DB::raw("CONCAT(Beneficiarios.nombre, ' ', Beneficiarios.apellido_paterno, ' ', Beneficiarios.apellido_materno)"), 'LIKE', "%$beneficiario%");
        }

        // Separar por tab - SOLO si no hay filtro de estado aplicado
        if (!$estado) {
            if ($tab === 'firmadas') {
                // Solo mostrar solicitudes con CUV (firmadas)
                $solicitudesQuery->whereNotNull('Solicitudes.cuv');
            } elseif ($tab === 'rechazadas') {
                // Solo mostrar solicitudes rechazadas (estado 5)
                $solicitudesQuery->where('Solicitudes.fk_id_estado', 5); // Estado 5 = RECHAZADA
            } else {
                // Mostrar pendientes (sin CUV y NO rechazadas)
                $solicitudesQuery->whereNull('Solicitudes.cuv')
                                ->where('Solicitudes.fk_id_estado', '!=', 5); // Excluir rechazadas
            }
        }

        // ✅ FILTRO CRÍTICO: Solo mostrar solicitudes si TODOS sus documentos están aprobados por admin
        // PERO: Solo aplicar este filtro cuando NO se está filtrando por estado específico
        // Cuando se filtra por "Aprobada" o "Rechazada", no aplicar restricción de documentos
        if (!$estado) {
            // Las solicitudes deben tener al menos 1 documento aprobado
            $solicitudesQuery->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('Documentos_Expediente')
                    ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
                    ->where('Documentos_Expediente.admin_status', 'aceptado');
            });

            // Y NO deben tener ningún documento pendiente/rechazado
            $solicitudesQuery->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('Documentos_Expediente')
                    ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
                    ->where('admin_status', '!=', 'aceptado')
                    ->whereNotNull('admin_status');
            });
        }

        // Obtener solicitudes paginadas
        $solicitudes = $solicitudesQuery->orderByDesc('Solicitudes.folio')->paginate(10);

        // Determinar el tab actual - si hay filtro de estado, usar ese
        $tabActualDisplay = $tab;
        if ($estado) {
            $tabMap = [
                'APROBADA' => 'aprobadas',
                'RECHAZADA' => 'rechazadas',
                'DOCUMENTOS_VERIFICADOS' => 'pendientes',
                'ANALISIS_ADMIN' => 'analisis',
            ];
            $tabActualDisplay = $tabMap[$estado] ?? $tab;
        }

        // Estadísticas
        $hoy = now()->startOfDay();
        
        // Contar solo solicitudes pendientes que tienen TODOS documentos aprobados (excluir rechazadas)
        $pendientesQuery = DB::table('Solicitudes')
            ->whereNull('Solicitudes.cuv')
            ->where('Solicitudes.fk_id_estado', '!=', 5) // Excluir rechazadas
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('Documentos_Expediente')
                    ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
                    ->where('Documentos_Expediente.admin_status', 'aceptado');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('Documentos_Expediente')
                    ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
                    ->where('admin_status', '!=', 'aceptado')
                    ->whereNotNull('admin_status');
            });
        
        $stats = [
            'pendientes' => $pendientesQuery->count(),
            'firmadas' => DB::table('Solicitudes')->whereNotNull('Solicitudes.cuv')->count(),
            'aprobadas_hoy' => DB::table('Solicitudes')
                ->where('Solicitudes.cuv', '!=', null)
                ->whereDate('Solicitudes.fecha_creacion', $hoy)
                ->count(),
            'rechazadas_hoy' => DB::table('Solicitudes')
                ->where('Solicitudes.fk_id_estado', 5) // Estado 5 = RECHAZADA
                ->whereDate('Solicitudes.fecha_actualizacion', $hoy) // Usar fecha_actualizacion (cuando se rechazó)
                ->count(),
        ];

        // Apoyos disponibles para filtro
        $apoyosDisponibles = DB::table('Apoyos')
            ->where('activo', 1)
            ->select('id_apoyo', 'nombre_apoyo')
            ->orderBy('nombre_apoyo')
            ->get();

        return view('solicitudes.proceso.index', [
            'solicitudes' => $solicitudes,
            'stats' => $stats,
            'apoyosDisponibles' => $apoyosDisponibles,
            'tabActual' => $tab,
            'tabActualDisplay' => $tabActualDisplay,
            'estadoFiltrado' => $estado,
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

    /**
     * ✅ VISTA DETALLADA - Información completa de una solicitud
     */
    public function show($folio, Request $request)
    {
        $user = Auth::user();
        $this->authorizePersonal($request, 2);

        // Obtener solicitud usando DB
        $solicitudDb = DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitudDb) {
            abort(404, 'Solicitud no encontrada');
        }

        // Obtener beneficiario como modelo Eloquent (para relaciones)
        $beneficiario = \App\Models\Beneficiario::where('curp', $solicitudDb->fk_curp)->first();

        if (!$beneficiario) {
            abort(404, 'Beneficiario no encontrado');
        }

        // Información del apoyo (con categoría)
        $apoyo = DB::table('Apoyos')
            ->leftJoin('presupuesto_categorias', 'Apoyos.id_categoria', '=', 'presupuesto_categorias.id_categoria')
            ->select(
                'Apoyos.id_apoyo',
                'Apoyos.nombre_apoyo',
                'Apoyos.monto_maximo',
                'Apoyos.cupo_limite',
                'Apoyos.id_categoria',
                'presupuesto_categorias.nombre as categoria_nombre'
            )
            ->where('Apoyos.id_apoyo', $solicitudDb->fk_id_apoyo)
            ->first();

        // Documentos asociados
        $documentos = DB::table('Documentos_Expediente')
            ->where('fk_folio', $solicitudDb->folio)
            ->get();

        // ========== VALIDACIÓN DE PRESUPUESTO ==========
        $presupuestoDisponible = $this->obtenerPresupuestoDisponibleSolicitud($solicitudDb->folio);
        $presupuestoCategoriaDisponible = $this->obtenerPresupuestoCategoriaDisponible($apoyo->id_categoria ?? 0);

        // Calcular total necesario (monto por beneficiario × cantidad máx beneficiarios)
        $totalNecesario = ($apoyo->monto_maximo ?? 0) * ($apoyo->cupo_limite ?? 0);

        // ========== CALCULAR DISPONIBLE EN APOYO (DINÁMICO) ==========
        // Obtener suma de montos ya aprobados y FIRMADOS para este apoyo
        // Solo contar solicitudes que tengan CUV (es decir, que fueron realmente firmadas)
        $montosAprobados = DB::table('presupuesto_apoyos')
            ->join('Solicitudes', 'presupuesto_apoyos.folio', '=', 'Solicitudes.folio')
            ->where('Solicitudes.fk_id_apoyo', $solicitudDb->fk_id_apoyo)
            ->where('Solicitudes.fk_id_estado', 4) // Estado 4 = APROBADA
            ->whereNotNull('Solicitudes.cuv') // Solo contar si tiene CUV (fue firmada)
            ->sum('presupuesto_apoyos.monto_solicitado') ?? 0;

        // Disponible en apoyo = Total necesario - Montos ya aprobados y firmados
        $disponibleEnApoyo = max(0, $totalNecesario - $montosAprobados);

        $puedeAprobarse = ($presupuestoDisponible >= ($apoyo->monto_maximo ?? 0)) 
                         && ($presupuestoCategoriaDisponible >= ($apoyo->monto_maximo ?? 0));

        // Verificar si la solicitud ya fue procesada (firmada o rechazada)
        $yaFirmada = !empty($solicitudDb->cuv); // CUV no NULL = ya firmada
        $yaRechazada = $solicitudDb->fk_id_estado == 5; // Estado 5 = RECHAZADA
        $procesada = $yaFirmada || $yaRechazada;

        // ========== HISTORIAL DE APOYOS PREVIOS ==========
        $historialApoyos = DB::table('Solicitudes')
            ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->where('Solicitudes.fk_curp', $beneficiario->curp)
            ->where('Solicitudes.folio', '!=', $solicitudDb->folio)
            ->where('Solicitudes.fk_id_estado', 4) // Solo aprobadas
            ->select(
                'Solicitudes.folio',
                'Apoyos.nombre_apoyo',
                'Solicitudes.monto_entregado as monto',
                'Solicitudes.fecha_creacion',
                'Solicitudes.cuv'
            )
            ->orderByDesc('Solicitudes.fecha_creacion')
            ->limit(10)
            ->get();

        // Estado actual
        $estadoActual = DB::table('Cat_EstadosSolicitud')
            ->where('id_estado', $solicitudDb->fk_id_estado)
            ->first();

        // Total de apoyos previos
        $totalApoyosPrevios = DB::table('Solicitudes')
            ->where('fk_curp', $beneficiario->curp)
            ->where('fk_id_estado', 4) // Aprobadas
            ->count();

        // Para compatibilidad con la vista, usar solicitud db
        $solicitud = $solicitudDb;

        return view('solicitudes.proceso.show', [
            'solicitud' => $solicitud,
            'beneficiario' => $beneficiario,
            'apoyo' => $apoyo,
            'documentos' => $documentos,
            'presupuestoDisponible' => $presupuestoDisponible,
            'presupuestoCategoriaDisponible' => $presupuestoCategoriaDisponible,
            'puedeAprobarse' => $puedeAprobarse,
            'totalNecesario' => $totalNecesario,
            'disponibleEnApoyo' => $disponibleEnApoyo,
            'historialApoyos' => $historialApoyos,
            'totalApoyosPrevios' => $totalApoyosPrevios,
            'estadoActual' => $estadoActual,
            'userRole' => $user->personal->fk_rol ?? null,
            'yaFirmada' => $yaFirmada,
            'yaRechazada' => $yaRechazada,
            'procesada' => $procesada,
        ]);
    }

    /**
     * ✅ FIRMAR SOLICITUD - Generar CUV y confirmar presupuesto
     */
    public function firmar($folio, Request $request)
    {
        // Usar authorizePersonal para verificación completa
        $user = $this->authorizePersonal($request, 2); // 2 = Directivo

        // Validar contraseña
        $request->validate([
            'password' => 'required|string',
        ]);

        if (!Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Contraseña incorrecta']);
        }

        // Obtener solicitud
        $solicitud = DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitud) {
            return back()->withErrors(['error' => 'Solicitud no encontrada']);
        }

        // Validar estado (debe ser 9 = DOCS_VERIFICADOS o 4 = Aprobado heredado)
        if (!in_array($solicitud->fk_id_estado, [4, 9])) {
            $estado = DB::table('Cat_EstadosSolicitud')
                ->where('id_estado', $solicitud->fk_id_estado)
                ->first(['nombre_estado']);
            
            $estadoNombre = $estado->nombre_estado ?? 'desconocido';
            return back()->withErrors(['error' => "Solicitud no está en estado para firmar (estado actual: {$estadoNombre})."]);
        }

        // Validar presupuesto
        $presupuestoDisponible = $this->obtenerPresupuestoDisponibleSolicitud($folio);
        $montoEntregado = $solicitud->monto_entregado ?? 0;

        if ($presupuestoDisponible < $montoEntregado) {
            return back()->withErrors(['error' => "Presupuesto insuficiente. Disponible: \${$presupuestoDisponible}, Requerido: \${$montoEntregado}"]);
        }

        // Iniciar transacción
        DB::beginTransaction();

        try {
            // Generar CUV único y legible
            // Formato: FOLIO-YYYYMMDD-HASH6 (ej: 1008-20260413-a1b2c3) - máximo 20 caracteres
            $fecha = now()->format('Ymd');
            $hashCorto = substr(hash('sha256', $folio . now()->timestamp . $user->id_usuario), 0, 6);
            $cuv = "{$folio}-{$fecha}-{$hashCorto}";

            // Actualizar solicitud con los datos críticos
            DB::table('Solicitudes')->where('folio', $folio)->update([
                'cuv' => $cuv,
                'fk_id_estado' => 4, // APROBADA (ID 4 en Cat_EstadosSolicitud)
                'presupuesto_confirmado' => 1,
            ]);

            // Registrar firma electrónica en tabla de auditoría (si existe)
            $tablaFirmas = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%firma%' AND TABLE_SCHEMA = 'BD_SIGO'");
            
            if (count($tablaFirmas) > 0) {
                // Tabla de firmas existe, registrar
                try {
                    DB::table('firmas_electronicas')->insertOrIgnore([
                        'folio' => $folio,
                        'cuv' => $cuv,
                        'usuario_id' => $user->id_usuario,
                        'fecha_firma' => now(),
                        'ip_address' => $request->ip(),
                        'user_agent' => $request->userAgent(),
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('No se pudo registrar firma electrónica: ' . $e->getMessage());
                }
            }

            // Log de firma
            \Log::info('Firma electronica registrada', [
                'folio' => $folio,
                'cuv' => $cuv,
                'usuario' => $user->email,
                'timestamp' => now(),
            ]);

            DB::commit();

            // Enviar notificación de aprobación
            if ($solicitud && $folio) {
                try {
                    $solicitudCompleta = DB::table('Solicitudes')
                        ->join('Beneficiarios', 'Solicitudes.fk_curp', '=', 'Beneficiarios.curp')
                        ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
                        ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
                        ->where('Solicitudes.folio', $folio)
                        ->select([
                            'Beneficiarios.nombre',
                            'Beneficiarios.curp',
                            'Usuarios.email',
                            'Apoyos.nombre_apoyo',
                            'Apoyos.monto_maximo',
                        ])
                        ->first();
                    
                    if ($solicitudCompleta) {
                        $beneficiario = (object)[
                            'nombre' => $solicitudCompleta->nombre,
                            'curp' => $solicitudCompleta->curp,
                            'email' => $solicitudCompleta->email,
                        ];
                        $apoyo = (object)[
                            'nombre_apoyo' => $solicitudCompleta->nombre_apoyo,
                        ];
                        \App\Services\NotificacionAprobacionService::enviarNotificacionAprobacion(
                            $folio,
                            $cuv,
                            $beneficiario,
                            $apoyo,
                            $solicitudCompleta->monto_maximo
                        );
                    }
                } catch (\Exception $e) {
                    \Log::warning('Error al enviar notificación de aprobación: ' . $e->getMessage());
                }
            }

            return redirect()->route('solicitudes.proceso.show', $folio)
                ->with('success', "Solicitud firmada exitosamente. CUV: {$cuv}");

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al firmar solicitud: ' . $e->getMessage(), [
                'folio' => $folio,
                'usuario' => $user->email,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->withErrors(['error' => 'Error al firmar: ' . $e->getMessage()]);
        }
    }

    /**
     * ⭐ HELPERS - Obtener presupuesto disponible para una solicitud
     */
    private function obtenerPresupuestoDisponibleSolicitud($folio)
    {
        // Buscar el registro de presupuesto para esta solicitud
        $presupuesto = DB::table('presupuesto_apoyos')
            ->where('folio', $folio)
            ->first();

        if (!$presupuesto) {
            // Si no existe registro de presupuesto, retornar 0
            return 0;
        }

        // Retornar monto_solicitado como el disponible guardado en el apoyo
        return ($presupuesto->monto_solicitado ?? 0);
    }

    /**
     * ⭐ Rechazar una solicitud
     */
    public function rechazar($folio, Request $request)
    {
        $user = $this->authorizePersonal($request, 2); // 2 = Directivo

        $request->validate([
            'password' => 'required|string',
            'motivo' => 'nullable|string|max:1000',
        ]);

        if (!Hash::check($request->input('password'), $user->password)) {
            return back()->withErrors(['password' => 'Contraseña incorrecta']);
        }

        // Obtener solicitud
        $solicitud = DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitud) {
            return back()->withErrors(['error' => 'Solicitud no encontrada']);
        }

        // Validar estado - Se puede rechazar en múltiples estados
        // Estados permitidos: 1 (Pendiente), 2 (Validado), 3 (Subsanación), 4 (Aprobado), 9 (DOCS_VERIFICADOS)
        $estadosPermitidos = [1, 2, 3, 4, 9];
        
        if (!in_array($solicitud->fk_id_estado, $estadosPermitidos)) {
            $estadoNombre = DB::table('Cat_EstadosSolicitud')
                ->where('id_estado', $solicitud->fk_id_estado)
                ->value('nombre_estado') ?? 'desconocido';
            
            return back()->withErrors(['error' => "La solicitud no puede ser rechazada en estado: {$estadoNombre}"]);
        }

        // Obtener datos del beneficiario y apoyo (con email desde tabla Usuarios)
        $beneficiario = DB::table('Beneficiarios')
            ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
            ->select('Beneficiarios.*', 'Usuarios.email')
            ->where('Beneficiarios.curp', $solicitud->fk_curp)
            ->first();

        $apoyo = DB::table('Apoyos')
            ->where('id_apoyo', $solicitud->fk_id_apoyo)
            ->first();

        DB::beginTransaction();

        try {
            // Construir nueva observación con timestamp
            $motivo = $request->input('motivo') ?? 'Sin motivo especificado';
            $timestamp = now()->format('Y-m-d H:i:s');
            $nuevaObservacion = "\n[RECHAZADA POR DIRECTIVO $timestamp] $motivo";
            $observacionesActuales = $solicitud->observaciones_internas ?? '';

            // Actualizar estado a rechazada (ID 5 generalmente es RECHAZADA)
            DB::table('Solicitudes')->where('folio', $folio)->update([
                'fk_id_estado' => 5, // RECHAZADA
                'observaciones_internas' => $observacionesActuales . $nuevaObservacion,
            ]);

            // Enviar correo de rechazo al beneficiario
            if ($beneficiario && $beneficiario->email) {
                \App\Services\NotificacionRechazoService::enviarNotificacionRechazo(
                    $folio,
                    $beneficiario,
                    $apoyo,
                    $request->input('motivo')
                );
            }

            // Log
            \Log::info('Solicitud rechazada', [
                'folio' => $folio,
                'directivo' => $user->email,
                'beneficiario' => $beneficiario->email ?? 'sin correo',
                'motivo' => $request->input('motivo') ?? 'sin motivo',
                'timestamp' => now(),
            ]);

            DB::commit();

            return redirect()->route('solicitudes.proceso.show', $folio)
                ->with('success', 'Solicitud rechazada. Se envió notificación al beneficiario.');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Error al rechazar solicitud: ' . $e->getMessage(), [
                'folio' => $folio,
                'usuario' => $user->email,
                'trace' => $e->getTraceAsString(),
            ]);
            
            return back()->withErrors(['error' => 'Error al rechazar: ' . $e->getMessage()]);
        }
    }

    /**
     * ⭐ HELPERS - Obtener presupuesto disponible por categoría
     */
    private function obtenerPresupuestoCategoriaDisponible($idCategoria)
    {
        $presupuesto = DB::table('presupuesto_categorias')
            ->where('id_categoria', $idCategoria)
            ->first();

        if (!$presupuesto) {
            return 0;
        }

        // disponible es el campo que tiene el saldo actual de presupuesto
        return max(0, $presupuesto->disponible ?? 0);
    }
}
