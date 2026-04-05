<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VerificacionCertificadoService;
use App\Models\HistoricoCierre;
use App\Models\AuditoriaVerificacion;
use Illuminate\Http\Request;
use Carbon\Carbon;

class VerificacionCertificadoController extends Controller
{
    protected $verificacionService;

    public function __construct(VerificacionCertificadoService $verificacionService)
    {
        $this->middleware(['auth', 'role:2,3']);
        $this->verificacionService = $verificacionService;
    }

    /**
     * Dashboard de verificación con estadísticas
     */
    public function dashboardVerificacion()
    {
        $estadisticas = $this->verificacionService->obtenerEstadisticasVerificacion();

        // Últimas validaciones realizadas
        $ultimas_validaciones = AuditoriaVerificacion::where('tipo_verificacion', 'VALIDACION_COMPLETA')
            ->with(['historico', 'usuario'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Certificados por estado de validación
        $por_estado = HistoricoCierre::select('estado_certificacion')
            ->whereNotNull('estado_certificacion')
            ->groupBy('estado_certificacion')
            ->selectRaw('COUNT(*) as total')
            ->selectRaw('SUM(monto_entregado) as monto_total')
            ->get()
            ->keyBy('estado_certificacion');

        // Auditorías por tipo (últimas 30 días)
        $hace_30_dias = Carbon::now()->subDays(30);
        $auditorias_por_tipo = AuditoriaVerificacion::where('created_at', '>=', $hace_30_dias)
            ->selectRaw('tipo_verificacion, COUNT(*) as total')
            ->groupBy('tipo_verificacion')
            ->get()
            ->keyBy('tipo_verificacion');

        return view('admin.certificacion.verificacion.dashboard', compact(
            'estadisticas',
            'ultimas_validaciones',
            'por_estado',
            'auditorias_por_tipo'
        ));
    }

    /**
     * Página de verificación de certificado individual
     */
    public function verificarCertificado($id)
    {
        $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id);

        // Obtener validación
        $validacion = $this->verificacionService->validarCertificado($id);
        
        // Obtener integridad
        $integridad = $this->verificacionService->verificarIntegridad($id);
        
        // Obtener auditoría
        $auditoria = $this->verificacionService->obtenerAuditoriaDetallada($id);

        return view('admin.certificacion.verificacion.formulario-verificacion', compact(
            'desembolso',
            'validacion',
            'integridad',
            'auditoria'
        ));
    }

    /**
     * Generar reporte de validación (POST)
     */
    public function generarReporteValidacion(Request $request, $id)
    {
        $desembolso = HistoricoCierre::findOrFail($id);

        $resultado = $this->verificacionService->generarReporteValidacion($id);

        if ($resultado['exito']) {
            return response()->json([
                'exito' => true,
                'mensaje' => 'Reporte de validación generado exitosamente',
                'ruta_descarga' => route('certificacion.verificacion.descargar-reporte', $id),
                'resultado_validacion' => $resultado['resultado_validacion'],
            ]);
        }

        return response()->json([
            'exito' => false,
            'mensaje' => $resultado['razon'],
        ], 500);
    }

    /**
     * Descargar reporte de validación (PDF)
     */
    public function descargarReporteValidacion($id)
    {
        $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id);

        $validacion = $this->verificacionService->validarCertificado($id);
        $auditoria = $this->verificacionService->obtenerAuditoriaDetallada($id);

        $datos = [
            'desembolso' => $desembolso,
            'validacion' => $validacion,
            'auditoria' => $auditoria,
            'fecha_reporte' => Carbon::now()->format('d/m/Y H:i:s'),
        ];

        $pdf = \Pdf::loadView('reportes.reporte-validacion-pdf', $datos);
        $fecha = Carbon::now()->format('Y-m-d');
        $nombre = "Validacion_{$desembolso->fk_folio}_{$fecha}.pdf";

        return $pdf->download($nombre);
    }

    /**
     * Página de auditoría detallada
     */
    public function auditoriaDetallada($id)
    {
        $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id);
        $auditoria = $this->verificacionService->obtenerAuditoriaDetallada($id);

        return view('admin.certificacion.verificacion.auditoria-detallada', compact(
            'desembolso',
            'auditoria'
        ));
    }

    /**
     * Reporte de cumplimiento LGPDP
     */
    public function reporteCumplimiento($id)
    {
        $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id);

        $cumplimiento = $this->verificacionService->generarReporteCumplimiento($id);

        return view('admin.certificacion.verificacion.reporte-cumplimiento', compact(
            'desembolso',
            'cumplimiento'
        ));
    }

    /**
     * Descargar reporte de cumplimiento (PDF)
     */
    public function descargarReporteCumplimiento($id)
    {
        $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id);
        $cumplimiento = $this->verificacionService->generarReporteCumplimiento($id);

        $datos = [
            'desembolso' => $desembolso,
            'cumplimiento' => $cumplimiento,
            'fecha_reporte' => Carbon::now()->format('d/m/Y H:i:s'),
        ];

        $pdf = \Pdf::loadView('reportes.reporte-cumplimiento-pdf', $datos);
        $fecha = Carbon::now()->format('Y-m-d');
        $nombre = "Cumplimiento_LGPDP_{$desembolso->fk_folio}_{$fecha}.pdf";

        return $pdf->download($nombre);
    }

    /**
     * API: Validar múltiples certificados
     */
    public function apiValidarMultiples(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:historicos_cierres,id_historico',
        ]);

        $resultados = [];
        foreach ($request->ids as $id) {
            $validacion = $this->verificacionService->validarCertificado($id);
            $resultados[] = [
                'id' => $id,
                'valido' => $validacion['resultado_general'],
                'tipo' => $validacion['tipo_resultado'],
            ];
        }

        return response()->json([
            'exito' => true,
            'total' => count($resultados),
            'validados' => count(array_filter($resultados, fn($r) => $r['valido'])),
            'resultados' => $resultados,
        ]);
    }

    /**
     * API: Obtener estadísticas de verificación
     */
    public function apiObtenerEstadisticas()
    {
        $estadisticas = $this->verificacionService->obtenerEstadisticasVerificacion();

        return response()->json([
            'exito' => true,
            'datos' => $estadisticas,
        ]);
    }

    /**
     * Formulario de búsqueda y validación en lote
     */
    public function formularioValidacionLote()
    {
        // Obtener apoyos disponibles para filtro
        $apoyos = \App\Models\Apoyos::where('activo', 1)
            ->select('id_apoyo', 'nombre_apoyo')
            ->distinct()
            ->orderBy('nombre_apoyo')
            ->get();

        return view('admin.certificacion.verificacion.formulario-lote', compact('apoyos'));
    }

    /**
     * Procesar validación en lote
     */
    public function procesarValidacionLote(Request $request)
    {
        $request->validate([
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date|after_or_equal:fecha_inicio',
            'apoyo_id' => 'nullable|integer|exists:apoyos,id_apoyo',
            'estado_certificacion' => 'nullable|string|in:CERTIFICADO,VALIDADO',
        ]);

        $query = HistoricoCierre::with(['solicitud', 'usuario']);

        if ($request->fecha_inicio) {
            $query->where('fecha_entrega', '>=', $request->fecha_inicio);
        }

        if ($request->fecha_fin) {
            $fecha_fin = Carbon::parse($request->fecha_fin)->endOfDay();
            $query->where('fecha_entrega', '<=', $fecha_fin);
        }

        if ($request->apoyo_id) {
            $query->whereHas('solicitud', function ($q) use ($request) {
                $q->where('fk_id_apoyo', $request->apoyo_id);
            });
        }

        if ($request->estado_certificacion) {
            $query->where('estado_certificacion', $request->estado_certificacion);
        }

        $certificados = $query->paginate(50);

        $resultados_validacion = [];
        foreach ($certificados as $cert) {
            $validacion = $this->verificacionService->validarCertificado($cert->id_historico);
            $resultados_validacion[$cert->id_historico] = $validacion['tipo_resultado'];
        }

        return view('admin.certificacion.verificacion.resultados-validacion-lote', compact(
            'certificados',
            'resultados_validacion'
        ));
    }

    /**
     * Descargar reporte de validación en lote (ZIP)
     */
    public function descargarValidacionLote(Request $request)
    {
        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'integer|exists:historicos_cierres,id_historico',
        ]);

        $zip = new \ZipArchive();
        $zip_file = public_path('reportes/validaciones/Validaciones_Lote_' . Carbon::now()->format('Y-m-d-His') . '.zip');

        if ($zip->open($zip_file, \ZipArchive::CREATE) === true) {
            foreach ($request->ids as $id) {
                $resultado = $this->verificacionService->generarReporteValidacion($id);
                if ($resultado['exito']) {
                    $pdf_path = public_path("reportes/validaciones/{$resultado['nombre_archivo']}");
                    if (file_exists($pdf_path)) {
                        $zip->addFile($pdf_path, $resultado['nombre_archivo']);
                    }
                }
            }
            $zip->close();

            return response()->download($zip_file)->deleteFileAfterSend(true);
        }

        return redirect()->back()->with('error', 'Error al crear archivo ZIP');
    }
}
