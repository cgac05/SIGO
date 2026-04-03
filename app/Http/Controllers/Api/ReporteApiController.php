<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportePresupuestarioService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReporteApiController extends Controller
{
    protected $reporteService;

    public function __construct(ReportePresupuestarioService $reporteService)
    {
        $this->reporteService = $reporteService;
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
}
