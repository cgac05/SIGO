<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ReconciliacionPresupuestariaService
{
    /**
     * Obtener estado de ejecución presupuestaria global
     * 
     * @return array Resumen agregado de presupuestos
     */
    public function obtenerEjecucionGlobal(): array
    {
        try {
            // Total asignado a nivel de categorías
            $totalAsignado = DB::table('presupuesto_categorias')
                ->where('activo', 1)
                ->sum('presupuesto_anual');

            // Total entregado a beneficiarios
            $totalEntregado = DB::table('Solicitudes')
                ->where('presupuesto_confirmado', 1)
                ->sum('monto_entregado');

            // Total en fichas de presupuesto (BD_Finanzas)
            $totalEjercido = DB::table('BD_Finanzas')
                ->sum('monto_ejercido');

            // Total diferencia (sobreejercicio o subejecución)
            $diferencia = $totalAsignado - $totalEjercido;
            $porcentajeEjecucion = ($totalAsignado > 0) 
                ? round(($totalEjercido / $totalAsignado) * 100, 2)
                : 0;

            return [
                'total_asignado' => floatval($totalAsignado),
                'total_ejercido' => floatval($totalEjercido),
                'total_entregado' => floatval($totalEntregado),
                'diferencia' => floatval($diferencia),
                'porcentaje_ejecucion' => $porcentajeEjecucion,
                'estado' => $this->determinarEstado($porcentajeEjecucion),
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo ejecución global", ['error' => $e->getMessage()]);
            return [
                'total_asignado' => 0,
                'total_ejercido' => 0,
                'total_entregado' => 0,
                'diferencia' => 0,
                'porcentaje_ejecucion' => 0,
                'estado' => 'ERROR',
            ];
        }
    }

    /**
     * Obtener ejecución por categoría de presupuesto
     * 
     * @return array Ejecución desglosada por categoría
     */
    public function obtenerEjecucionPorCategoria(): array
    {
        try {
            $categorias = DB::table('presupuesto_categorias as pc')
                ->leftJoin('BD_Finanzas as bf', 'pc.id_categoria', '=', 'bf.id_categoria')
                ->where('pc.activo', 1)
                ->select(
                    'pc.id_categoria',
                    'pc.nombre',
                    DB::raw('CAST(pc.presupuesto_anual AS DECIMAL(18,2)) as presupuesto_asignado'),
                    DB::raw('COALESCE(SUM(bf.monto_ejercido), 0) as monto_ejercido')
                )
                ->groupBy('pc.id_categoria', 'pc.nombre', 'pc.presupuesto_anual')
                ->orderBy('pc.nombre')
                ->get()
                ->map(function ($cat) {
                    $asignado = floatval($cat->presupuesto_asignado);
                    $ejercido = floatval($cat->monto_ejercido);
                    $disponible = $asignado - $ejercido;
                    $porcentaje = ($asignado > 0) ? round(($ejercido / $asignado) * 100, 2) : 0;

                    return [
                        'id_categoria' => $cat->id_categoria,
                        'nombre' => $cat->nombre,
                        'presupuesto_asignado' => $asignado,
                        'monto_ejercido' => $ejercido,
                        'disponible' => max(0, $disponible),
                        'porcentaje_ejecucion' => $porcentaje,
                        'estado' => $this->determinarEstado($porcentaje),
                        'sobreejercicio' => $disponible < 0 ? abs($disponible) : 0,
                    ];
                })
                ->toArray();

            return $categorias;

        } catch (\Exception $e) {
            Log::error("Error obteniendo ejecución por categoría", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener ejecución por apoyo
     * 
     * @return array Ejecución desglosada por apoyo
     */
    public function obtenerEjecucionPorApoyo(): array
    {
        try {
            $apoyos = DB::table('Apoyos as a')
                ->leftJoin('BD_Finanzas as bf', 'a.id_apoyo', '=', 'bf.fk_id_apoyo')
                ->select(
                    'a.id_apoyo',
                    'a.nombre_apoyo',
                    DB::raw('COALESCE(bf.monto_asignado, 0) as presupuesto_asignado'),
                    DB::raw('COALESCE(bf.monto_ejercido, 0) as monto_ejercido')
                )
                ->groupBy('a.id_apoyo', 'a.nombre_apoyo', 'bf.monto_asignado', 'bf.monto_ejercido')
                ->orderBy('a.nombre_apoyo')
                ->get()
                ->map(function ($apoyo) {
                    $asignado = floatval($apoyo->presupuesto_asignado);
                    $ejercido = floatval($apoyo->monto_ejercido);
                    $disponible = $asignado - $ejercido;
                    $porcentaje = ($asignado > 0) ? round(($ejercido / $asignado) * 100, 2) : 0;

                    // Contar beneficiarios pagados
                    $beneficiarios = DB::table('Solicitudes')
                        ->where('fk_id_apoyo', $apoyo->id_apoyo)
                        ->where('monto_entregado', '>', 0)
                        ->count();

                    return [
                        'id_apoyo' => $apoyo->id_apoyo,
                        'nombre_apoyo' => $apoyo->nombre_apoyo,
                        'presupuesto_asignado' => $asignado,
                        'monto_ejercido' => $ejercido,
                        'disponible' => max(0, $disponible),
                        'porcentaje_ejecucion' => $porcentaje,
                        'beneficiarios_pagados' => $beneficiarios,
                        'estado' => $this->determinarEstado($porcentaje),
                        'sobreejercicio' => $disponible < 0 ? abs($disponible) : 0,
                    ];
                })
                ->toArray();

            return $apoyos;

        } catch (\Exception $e) {
            Log::error("Error obteniendo ejecución por apoyo", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Validar discrepancias entre tablas (reconciliación)
     * 
     * Compara:
     * - BD_Finanzas.monto_ejercido vs Historico_Cierre desembolsos
     * - Solicitudes.monto_entregado vs Historico_Cierre total
     * 
     * @return array Discrepancias encontradas
     */
    public function validarDiscrepancias(): array
    {
        try {
            $discrepancias = [];

            // 1. Validar por apoyo: BD_Finanzas vs Historico_Cierre
            $apoyos = DB::table('Apoyos')
                ->select('id_apoyo', 'nombre_apoyo')
                ->get();

            foreach ($apoyos as $apoyo) {
                $bdFinanzas = DB::table('BD_Finanzas')
                    ->where('fk_id_apoyo', $apoyo->id_apoyo)
                    ->sum('monto_ejercido');

                $historicoCierre = DB::table('Historico_Cierre as hc')
                    ->join('Solicitudes as s', 's.folio', '=', 'hc.fk_folio')
                    ->where('s.fk_id_apoyo', $apoyo->id_apoyo)
                    ->sum('hc.monto_entregado');

                $diferencia = floatval($bdFinanzas) - floatval($historicoCierre);

                if (abs($diferencia) > 0.01) { // Tolerancia de $0.01
                    $discrepancias[] = [
                        'tipo' => 'MONTO_APOYO',
                        'id_apoyo' => $apoyo->id_apoyo,
                        'nombre_apoyo' => $apoyo->nombre_apoyo,
                        'bd_finanzas' => $bdFinanzas,
                        'historico_cierre' => $historicoCierre,
                        'diferencia' => $diferencia,
                        'severidad' => abs($diferencia) > 1000 ? 'CRITICA' : 'ADVERTENCIA',
                    ];
                }
            }

            // 2. Validar presupuestos sobre-ejercidos
            $sobreejercidos = DB::table('BD_Finanzas as bf')
                ->join('Apoyos as a', 'a.id_apoyo', '=', 'bf.fk_id_apoyo')
                ->whereRaw('bf.monto_ejercido > bf.monto_asignado')
                ->select(
                    'a.id_apoyo',
                    'a.nombre_apoyo',
                    DB::raw('CAST(bf.monto_asignado AS DECIMAL(18,2)) as monto_asignado'),
                    DB::raw('CAST(bf.monto_ejercido AS DECIMAL(18,2)) as monto_ejercido')
                )
                ->get();

            foreach ($sobreejercidos as $item) {
                $discrepancias[] = [
                    'tipo' => 'SOBREEJERCICIO',
                    'id_apoyo' => $item->id_apoyo,
                    'nombre_apoyo' => $item->nombre_apoyo,
                    'monto_asignado' => floatval($item->monto_asignado),
                    'monto_ejercido' => floatval($item->monto_ejercido),
                    'diferencia' => floatval($item->monto_ejercido) - floatval($item->monto_asignado),
                    'severidad' => 'CRITICA',
                ];
            }

            return $discrepancias;

        } catch (\Exception $e) {
            Log::error("Error validando discrepancias", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Obtener alertas de presupuesto
     * 
     * @return array Alertas por estado de ejecución
     */
    public function obtenerAlertas(): array
    {
        try {
            $alertas = [];

            // Alertas por categoría
            $categoriasAlerta = DB::table('presupuesto_categorias as pc')
                ->leftJoin('BD_Finanzas as bf', 'pc.id_categoria', '=', 'bf.id_categoria')
                ->where('pc.activo', 1)
                ->select(
                    'pc.id_categoria',
                    'pc.nombre',
                    DB::raw('CAST(pc.presupuesto_anual AS DECIMAL(18,2)) as presupuesto_anual'),
                    DB::raw('COALESCE(SUM(bf.monto_ejercido), 0) as monto_ejercido')
                )
                ->groupBy('pc.id_categoria', 'pc.nombre', 'pc.presupuesto_anual')
                ->get();

            foreach ($categoriasAlerta as $cat) {
                $asignado = floatval($cat->presupuesto_anual);
                $ejercido = floatval($cat->monto_ejercido);
                $porcentaje = ($asignado > 0) ? round(($ejercido / $asignado) * 100, 2) : 0;

                if ($porcentaje >= 95) {
                    $alertas[] = [
                        'tipo' => 'PRESUPUESTO_CRITICO',
                        'entidad' => 'Categoría',
                        'id' => $cat->id_categoria,
                        'nombre' => $cat->nombre,
                        'porcentaje' => $porcentaje,
                        'severidad' => 'CRITICA',
                    ];
                } elseif ($porcentaje >= 85) {
                    $alertas[] = [
                        'tipo' => 'PRESUPUESTO_ALTO',
                        'entidad' => 'Categoría',
                        'id' => $cat->id_categoria,
                        'nombre' => $cat->nombre,
                        'porcentaje' => $porcentaje,
                        'severidad' => 'ADVERTENCIA',
                    ];
                }
            }

            return $alertas;

        } catch (\Exception $e) {
            Log::error("Error obteniendo alertas de presupuesto", ['error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * Determinar estado visual basado en porcentaje de ejecución
     * 
     * @param float $porcentaje
     * @return string Estado: PENDIENTE, EN_EJECUCION, ALTO, CRITICO, COMPLETADO
     */
    private function determinarEstado(float $porcentaje): string
    {
        if ($porcentaje == 0) {
            return 'PENDIENTE';
        } elseif ($porcentaje < 50) {
            return 'EN_EJECUCION';
        } elseif ($porcentaje < 85) {
            return 'EN_EJECUCION_AVANZADA';
        } elseif ($porcentaje < 95) {
            return 'ALTO';
        } elseif ($porcentaje < 100) {
            return 'CRITICO';
        } else {
            return 'COMPLETADO';
        }
    }

    /**
     * Generar reporte de reconciliación detallado
     * 
     * @return array Reporte completo con todas las métricas
     */
    public function generarReporteCompleto(): array
    {
        try {
            return [
                'timestamp' => now(),
                'ejecucion_global' => $this->obtenerEjecucionGlobal(),
                'ejecucion_por_categoria' => $this->obtenerEjecucionPorCategoria(),
                'ejecucion_por_apoyo' => $this->obtenerEjecucionPorApoyo(),
                'discrepancias' => $this->validarDiscrepancias(),
                'alertas' => $this->obtenerAlertas(),
            ];

        } catch (\Exception $e) {
            Log::error("Error generando reporte completo", ['error' => $e->getMessage()]);
            return [];
        }
    }
}
