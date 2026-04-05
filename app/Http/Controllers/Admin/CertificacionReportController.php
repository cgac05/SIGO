<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\HistoricoCierre;
use App\Services\CertificacionDigitalService;
use App\Services\ReporteCertificacionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class CertificacionReportController extends Controller
{
    protected $reportService;
    protected $certService;

    public function __construct(ReporteCertificacionService $reportService, CertificacionDigitalService $certService)
    {
        $this->reportService = $reportService;
        $this->certService = $certService;
    }

    /**
     * GET /admin/certificacion/reportes
     * Dashboard de reportes y exportación
     */
    public function index()
    {
        $estadisticas = $this->certService->obtenerEstadisticas();
        $total_certificados = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')->count();

        return view('admin.certificacion.reportes.index', [
            'estadisticas' => $estadisticas,
            'total_certificados' => $total_certificados,
        ]);
    }

    /**
     * GET /admin/certificacion/{id}/pdf
     * Descargar PDF individual de certificado
     */
    public function descargarPDF($id)
    {
        $resultado = $this->reportService->generarPDFCertificado($id);

        if (!$resultado['exito']) {
            return redirect()->back()->with('error', $resultado['razon']);
        }

        $ruta_completa = storage_path('app/' . $resultado['ruta_pdf']);
        if (!file_exists($ruta_completa)) {
            return redirect()->back()->with('error', 'Archivo no encontrado');
        }

        return response()->download($ruta_completa, $resultado['nombre_archivo']);
    }

    /**
     * GET /admin/certificacion/excel/exportar
     * Generar y descargar Excel de certificados
     */
    public function exportarExcel(Request $request)
    {
        $filtros = [
            'estado' => $request->query('estado'),
            'fecha_inicio' => $request->query('fecha_inicio'),
            'fecha_fin' => $request->query('fecha_fin'),
            'apoyo_id' => $request->query('apoyo_id'),
        ];

        $resultado = $this->reportService->generarExcelCertificados($filtros);

        if (!$resultado['exito']) {
            return redirect()->back()->with('error', $resultado['razon']);
        }

        $ruta_completa = storage_path('app/' . $resultado['ruta_excel']);
        
        return response()->download($ruta_completa, $resultado['nombre_archivo'])->deleteFileAfterSend(false);
    }

    /**
     * POST /admin/certificacion/zip/exportar
     * Generar ZIP con múltiples PDFs
     */
    public function exportarZIP(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:Historico_Cierre,id_historico',
        ]);

        $ids = $request->input('ids', []);

        $resultado = $this->reportService->generarZIPMultiplePDFs($ids);

        if (!$resultado['exito']) {
            return redirect()->back()->with('error', $resultado['razon']);
        }

        $ruta_completa = storage_path('app/' . $resultado['ruta_zip']);
        
        return response()->download($ruta_completa, $resultado['nombre_archivo'])->deleteFileAfterSend(false);
    }

    /**
     * GET /admin/certificacion/estadisticas/pdf
     * Descargar reporte de estadísticas en PDF
     */
    public function descargarReporteEstadisticas()
    {
        try {
            $estadisticas = $this->certService->obtenerEstadisticas();
            $certificados = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
                ->with('solicitud', 'usuario')
                ->get()
                ->groupBy('estado_certificacion')
                ->map(function ($certs) {
                    return [
                        'total' => $certs->count(),
                        'monto' => $certs->sum('monto_entregado'),
                    ];
                });

            $resultado = $this->reportService->generarReporteEstadisticasPDF($estadisticas, $certificados);

            if (!$resultado['exito']) {
                return redirect()->back()->with('error', $resultado['razon']);
            }

            $ruta_completa = storage_path('app/' . $resultado['ruta_pdf']);
            
            return response()->download($ruta_completa, $resultado['nombre_archivo']);
        } catch (\Exception $e) {
            Log::error('Error descargando reporte: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error generando reporte');
        }
    }

    /**
     * GET /admin/certificacion/{id}/cadena-custodia/pdf
     * Descargar reporte de cadena de custodia
     */
    public function descargarCadenaCustodiaPDF($id)
    {
        $resultado = $this->reportService->generarReporteCadenaCustodiaPDF($id);

        if (!$resultado['exito']) {
            return redirect()->back()->with('error', $resultado['razon']);
        }

        $ruta_completa = storage_path('app/' . $resultado['ruta_pdf']);
        
        return response()->download($ruta_completa, $resultado['nombre_archivo']);
    }

    // ============ VISTAS DE REPORTES ============

    /**
     * GET /admin/certificacion/reportes/certificados
     * Formulario para generar reporte de certificados
     */
    public function formRepCertificados()
    {
        return view('admin.certificacion.reportes.form-certificados');
    }

    /**
     * POST /admin/certificacion/reportes/certificados
     * Generar y descargar reporte de certificados
     */
    public function generarRepCertificados(Request $request)
    {
        $request->validate([
            'estado' => 'nullable|string|in:CERTIFICADO,VALIDADO',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $filtros = [
            'estado' => $request->estado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ];

        return to_route('certificacion.reportes.excel')
            ->with($filtros);
    }

    /**
     * GET /admin/certificacion/reportes/masivo
     * Página para exportación masiva
     */
    public function formExportacionMasiva()
    {
        $certificados = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
            ->with('solicitud.beneficiario')
            ->paginate(20);

        return view('admin.certificacion.reportes.exportacion-masiva', [
            'certificados' => $certificados,
        ]);
    }

    /**
     * GET /admin/certificacion/reportes/dashboard
     * Dashboard mejorado de reportes
     */
    public function dashboardReportes()
    {
        $estadisticas = $this->certService->obtenerEstadisticas();

        // Certificados por estado
        $por_estado = HistoricoCierre::selectRaw('estado_certificacion, COUNT(*) as total, SUM(monto_entregado) as monto_total')
            ->where('estado_certificacion', '!=', 'PENDIENTE')
            ->groupBy('estado_certificacion')
            ->get();

        // Últimos 10 certificados
        $ultimos_certificados = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
            ->with('solicitud.beneficiario', 'usuario')
            ->orderBy('fecha_certificacion', 'desc')
            ->limit(10)
            ->get();

        // Certificados por mes
        $por_mes = HistoricoCierre::selectRaw('DATE_FORMAT(fecha_certificacion, "%Y-%m") as mes, COUNT(*) as total')
            ->where('estado_certificacion', '!=', 'PENDIENTE')
            ->groupBy('mes')
            ->orderBy('mes', 'desc')
            ->limit(12)
            ->get();

        return view('admin.certificacion.reportes.dashboard', [
            'estadisticas' => $estadisticas,
            'por_estado' => $por_estado,
            'ultimos_certificados' => $ultimos_certificados,
            'por_mes' => $por_mes,
        ]);
    }

    // ============ APIs para reportes ============

    /**
     * POST /api/certificacion/reportes/excel
     * API para generar Excel sin recargar página
     */
    public function apiGenerarExcel(Request $request)
    {
        $request->validate([
            'estado' => 'nullable|string|in:CERTIFICADO,VALIDADO',
            'fecha_inicio' => 'nullable|date',
            'fecha_fin' => 'nullable|date',
        ]);

        $filtros = [
            'estado' => $request->estado,
            'fecha_inicio' => $request->fecha_inicio,
            'fecha_fin' => $request->fecha_fin,
        ];

        $resultado = $this->reportService->generarExcelCertificados($filtros);

        return response()->json($resultado);
    }

    /**
     * POST /api/certificacion/reportes/zip
     * API para generar ZIP sin recargar página
     */
    public function apiGenerarZIP(Request $request)
    {
        $request->validate([
            'ids' => 'nullable|array',
            'ids.*' => 'integer|exists:Historico_Cierre,id_historico',
        ]);

        $resultado = $this->reportService->generarZIPMultiplePDFs($request->input('ids', []));

        return response()->json($resultado);
    }

    /**
     * GET /api/certificacion/reportes/estadisticas
     * API para obtener estadísticas
     */
    public function apiObtenerEstadisticas()
    {
        $estadisticas = $this->certService->obtenerEstadisticas();

        $por_estado = HistoricoCierre::selectRaw('estado_certificacion, COUNT(*) as total, SUM(monto_entregado) as monto_total')
            ->where('estado_certificacion', '!=', 'PENDIENTE')
            ->groupBy('estado_certificacion')
            ->get();

        return response()->json([
            'estadisticas_globales' => $estadisticas,
            'por_estado' => $por_estado,
        ]);
    }
}
