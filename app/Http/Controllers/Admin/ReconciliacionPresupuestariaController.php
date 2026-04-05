<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReconciliacionPresupuestariaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ReconciliacionPresupuestariaController extends Controller
{
    protected $reconciliacionService;

    public function __construct(ReconciliacionPresupuestariaService $reconciliacionService)
    {
        $this->reconciliacionService = $reconciliacionService;
    }

    /**
     * Dashboard de reconciliación presupuestaria
     * GET /admin/reconciliacion
     */
    public function index()
    {
        try {
            $ejecucionGlobal = $this->reconciliacionService->obtenerEjecucionGlobal();
            $ejecucionPorCategoria = $this->reconciliacionService->obtenerEjecucionPorCategoria();
            $ejecucionPorApoyo = $this->reconciliacionService->obtenerEjecucionPorApoyo();
            $alertas = $this->reconciliacionService->obtenerAlertas();
            $discrepancias = $this->reconciliacionService->validarDiscrepancias();

            return view('admin.reconciliacion.index', compact(
                'ejecucionGlobal',
                'ejecucionPorCategoria',
                'ejecucionPorApoyo',
                'alertas',
                'discrepancias'
            ));

        } catch (\Exception $e) {
            Log::error("Error en dashboard de reconciliación", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al cargar dashboard de reconciliación');
        }
    }

    /**
     * Reporte detallado de categorías
     * GET /admin/reconciliacion/categorias
     */
    public function reporteCategorias()
    {
        try {
            $categorias = $this->reconciliacionService->obtenerEjecucionPorCategoria();

            // Calcular totales
            $totales = [
                'total_presupuesto' => array_sum(array_column($categorias, 'presupuesto_asignado')),
                'total_ejercido' => array_sum(array_column($categorias, 'monto_ejercido')),
                'total_disponible' => array_sum(array_column($categorias, 'disponible')),
                'total_sobreejercicio' => array_sum(array_column($categorias, 'sobreejercicio')),
            ];

            $totales['porcentaje_global'] = ($totales['total_presupuesto'] > 0)
                ? round(($totales['total_ejercido'] / $totales['total_presupuesto']) * 100, 2)
                : 0;

            return view('admin.reconciliacion.categorias', compact('categorias', 'totales'));

        } catch (\Exception $e) {
            Log::error("Error en reporte de categorías", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar reporte de categorías');
        }
    }

    /**
     * Reporte detallado de apoyos
     * GET /admin/reconciliacion/apoyos
     */
    public function reporteApoyos()
    {
        try {
            $apoyos = $this->reconciliacionService->obtenerEjecucionPorApoyo();

            // Calcular totales
            $totales = [
                'total_presupuesto' => array_sum(array_column($apoyos, 'presupuesto_asignado')),
                'total_ejercido' => array_sum(array_column($apoyos, 'monto_ejercido')),
                'total_beneficiarios' => array_sum(array_column($apoyos, 'beneficiarios_pagados')),
                'total_sobreejercicio' => array_sum(array_column($apoyos, 'sobreejercicio')),
            ];

            $totales['porcentaje_global'] = ($totales['total_presupuesto'] > 0)
                ? round(($totales['total_ejercido'] / $totales['total_presupuesto']) * 100, 2)
                : 0;

            return view('admin.reconciliacion.apoyos', compact('apoyos', 'totales'));

        } catch (\Exception $e) {
            Log::error("Error en reporte de apoyos", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar reporte de apoyos');
        }
    }

    /**
     * Reporte de discrepancias y alertas
     * GET /admin/reconciliacion/alertas
     */
    public function reporteAlertas()
    {
        try {
            $discrepancias = $this->reconciliacionService->validarDiscrepancias();
            $alertas = $this->reconciliacionService->obtenerAlertas();

            // Clasificar por severidad
            $criticas = array_filter(array_merge($discrepancias, $alertas), 
                fn($a) => ($a['severidad'] ?? '') === 'CRITICA');
            $advertencias = array_filter(array_merge($discrepancias, $alertas),
                fn($a) => ($a['severidad'] ?? '') === 'ADVERTENCIA');

            $totales = [
                'total_criticas' => count($criticas),
                'total_advertencias' => count($advertencias),
                'total_discrepancias' => count($discrepancias),
                'total_alertas' => count($alertas),
            ];

            return view('admin.reconciliacion.alertas', compact(
                'discrepancias',
                'alertas',
                'criticas',
                'advertencias',
                'totales'
            ));

        } catch (\Exception $e) {
            Log::error("Error en reporte de alertas", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al generar reporte de alertas');
        }
    }

    /**
     * API: Obtener ejecución global
     * GET /api/reconciliacion/ejecucion-global
     */
    public function apiEjecucionGlobal()
    {
        try {
            $ejecucion = $this->reconciliacionService->obtenerEjecucionGlobal();
            return response()->json($ejecucion);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Obtener ejecución por categoría
     * GET /api/reconciliacion/categorias
     */
    public function apiCategorias()
    {
        try {
            $categorias = $this->reconciliacionService->obtenerEjecucionPorCategoria();
            return response()->json($categorias);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Obtener ejecución por apoyo
     * GET /api/reconciliacion/apoyos
     */
    public function apiApoyos()
    {
        try {
            $apoyos = $this->reconciliacionService->obtenerEjecucionPorApoyo();
            return response()->json($apoyos);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Obtener discrepancias
     * GET /api/reconciliacion/discrepancias
     */
    public function apiDiscrepancias()
    {
        try {
            $discrepancias = $this->reconciliacionService->validarDiscrepancias();
            return response()->json($discrepancias);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Obtener alertas
     * GET /api/reconciliacion/alertas
     */
    public function apiAlertas()
    {
        try {
            $alertas = $this->reconciliacionService->obtenerAlertas();
            return response()->json($alertas);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * API: Generar reporte completo
     * GET /api/reconciliacion/reporte
     */
    public function apiReporteCompleto()
    {
        try {
            $reporte = $this->reconciliacionService->generarReporteCompleto();
            return response()->json($reporte);

        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 422);
        }
    }

    /**
     * Descargar reporte como JSON
     * GET /admin/reconciliacion/descargar
     */
    public function descargar()
    {
        try {
            $reporte = $this->reconciliacionService->generarReporteCompleto();

            return response()->json($reporte, 200, [
                'Content-Disposition' => 'attachment; filename=reconciliacion_' . now()->format('Y-m-d_His') . '.json',
                'Content-Type' => 'application/json',
            ]);

        } catch (\Exception $e) {
            Log::error("Error descargando reporte", ['error' => $e->getMessage()]);
            return back()->with('error', 'Error al descargar reporte');
        }
    }
}
