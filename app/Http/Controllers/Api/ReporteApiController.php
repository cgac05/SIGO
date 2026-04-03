<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportePresupuestarioService;
use App\Services\ExportPresupuestacionService;
use App\Services\ExportPresupuestacionPdfService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ReporteApiController extends Controller
{
    protected $reporteService;
    protected $exportService;
    protected $exportPdfService;

    public function __construct(ReportePresupuestarioService $reporteService, ExportPresupuestacionService $exportService, ExportPresupuestacionPdfService $exportPdfService)
    {
        $this->reporteService = $reporteService;
        $this->exportService = $exportService;
        $this->exportPdfService = $exportPdfService;
    }

    /**
     * GET /api/reporte/resumen-alertas
     */
    public function resumenAlertas()
    {
        try {
            $data = $this->reporteService->obtenerResumenAlertas();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/tendencia-mensual/{año}
     */
    public function tendenciaMensual($año)
    {
        try {
            $data = $this->reporteService->generarTrendenciaMensual($año);
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/estadisticas-apoyos
     */
    public function estadisticasApoyo()
    {
        try {
            $data = $this->reporteService->estadisticasApoyo();
            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/mensual?mes=4&año=2026
     */
    public function reporteMensual(Request $request)
    {
        $mes = $request->query('mes', now()->month);
        $año = $request->query('año', now()->year);

        try {
            $data = $this->reporteService->generarReporteMensual($mes, $año);
            
            if (isset($data['error'])) {
                return response()->json([
                    'success' => false,
                    'error' => $data['error']
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $data
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/exportar/dashboard-excel
     */
    public function exportarDashboardExcel()
    {
        try {
            $spreadsheet = $this->exportService->exportarDashboardExcel();
            $writer = new Xlsx($spreadsheet);
            
            $filename = 'Dashboard-Presupuestacion-' . date('Y-m-d-His') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/exportar/reportes-excel?mes=4&año=2026
     */
    public function exportarReportesExcel(Request $request)
    {
        try {
            $mes = $request->query('mes', now()->month);
            $año = $request->query('año', now()->year);

            $spreadsheet = $this->exportService->exportarReportesMensualExcel($mes, $año);
            $writer = new Xlsx($spreadsheet);
            
            $filename = 'Reportes-Presupuestacion-' . date('Y-m-d') . '.xlsx';
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . $filename . '"');
            header('Cache-Control: max-age=0');
            
            $writer->save('php://output');
            exit;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/exportar/dashboard-pdf
     */
    public function exportarDashboardPdf()
    {
        try {
            $pdf = $this->exportPdfService->exportarDashboardPdf();
            return $pdf->download('Dashboard-Presupuestacion-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/exportar/reportes-pdf?mes=4&año=2026
     */
    public function exportarReportesPdf(Request $request)
    {
        try {
            $mes = $request->query('mes', now()->month);
            $año = $request->query('año', now()->year);

            $pdf = $this->exportPdfService->exportarReportesPdf($mes, $año);
            return $pdf->download('Reportes-Presupuestacion-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * GET /api/reporte/exportar/categoria-pdf/{id}
     */
    public function exportarCategoriaPdf($id)
    {
        try {
            $pdf = $this->exportPdfService->exportarCategoriaPdf($id);
            return $pdf->download('Categoria-Presupuestacion-' . date('Y-m-d') . '.pdf');
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
