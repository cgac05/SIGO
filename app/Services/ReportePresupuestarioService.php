<?php

namespace App\Services;

use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;
use App\Models\PresupuestoApoyo;
use App\Models\MovimientoPresupuestario;
use Illuminate\Support\Collection;

class ReportePresupuestarioService
{
    /**
     * Generar reporte mensual de presupuestación
     */
    public function generarReporteMensual($mes, $año): array
    {
        $ciclo = CicloPresupuestario::where('ano_fiscal', $año)
            ->where('estado', 'ABIERTO')
            ->first();

        if (!$ciclo) {
            return ['error' => 'Ciclo presupuestario no encontrado'];
        }

        $categorias = PresupuestoCategoria::where('id_ciclo', $ciclo->id)->get();

        // Calcular movimientos del mes
        $movimientosDelMes = MovimientoPresupuestario::where('id_ciclo', $ciclo->id)
            ->whereMonth('created_at', $mes)
            ->whereYear('created_at', $año)
            ->get()
            ->groupBy('id_categoria');

        $datosReporte = [];
        foreach ($categorias as $cat) {
            $movimientos = $movimientosDelMes->get($cat->id_categoria, collect());
            
            $datosReporte[] = [
                'categoria' => $cat->nombre,
                'presupuesto_anual' => $cat->presupuesto_anual,
                'disponible' => $cat->disponible,
                'utilizado' => $cat->presupuesto_anual - $cat->disponible,
                'movimientos_mes' => $movimientos->count(),
                'montos_movimientos' => $movimientos->sum('monto'),
                'porcentaje_utilizado' => (($cat->presupuesto_anual - $cat->disponible) / $cat->presupuesto_anual * 100),
            ];
        }

        return [
            'ciclo' => $año,
            'mes' => $mes,
            'fecha_reporte' => now()->format('d/m/Y H:i'),
            'categorias' => $datosReporte,
            'total_presupuesto' => $ciclo->presupuesto_total_inicial,
            'total_utilizado' => $categorias->sum(fn($c) => $c->presupuesto_anual - $c->disponible),
            'total_disponible' => $categorias->sum('disponible'),
        ];
    }

    /**
     * Obtener resumen de alertas presupuestarias
     */
    public function obtenerResumenAlertas(): array
    {
        $ciclo = CicloPresupuestario::where('ano_fiscal', now()->year)
            ->where('estado', 'ABIERTO')
            ->first();

        if (!$ciclo) {
            return [];
        }

        $categorias = PresupuestoCategoria::where('id_ciclo', $ciclo->id)->get();

        $alertas = [];
        foreach ($categorias as $cat) {
            $porcentaje = (($cat->presupuesto_anual - $cat->disponible) / $cat->presupuesto_anual) * 100;
            
            $nivel = match(true) {
                $porcentaje >= 95 => 'CRITICA',
                $porcentaje >= 85 => 'ROJA',
                $porcentaje >= 70 => 'AMARILLA',
                default => 'NORMAL'
            };

            if ($nivel !== 'NORMAL') {
                $alertas[] = [
                    'categoria' => $cat->nombre,
                    'porcentaje' => round($porcentaje, 2),
                    'nivel' => $nivel,
                    'disponible' => round($cat->disponible, 2),
                    'utilizado' => round($cat->presupuesto_anual - $cat->disponible, 2),
                ];
            }
        }

        return [
            'ciclo' => $ciclo->ano_fiscal,
            'total_alertas' => count($alertas),
            'alertas_criticas' => count(array_filter($alertas, fn($a) => $a['nivel'] === 'CRITICA')),
            'alertas_rojas' => count(array_filter($alertas, fn($a) => $a['nivel'] === 'ROJA')),
            'alertas_amarillas' => count(array_filter($alertas, fn($a) => $a['nivel'] === 'AMARILLA')),
            'detalle' => $alertas,
        ];
    }

    /**
     * Generar gráfico de tendencia mensual
     */
    public function generarTrendenciaMensual($ciclo_año): array
    {
        $ciclo = CicloPresupuestario::where('ano_fiscal', $ciclo_año)
            ->where('estado', 'ABIERTO')
            ->first();

        if (!$ciclo) {
            return [];
        }

        $meses = [];
        for ($mes = 1; $mes <= 12; $mes++) {
            $movimientos = MovimientoPresupuestario::where('id_ciclo', $ciclo->id)
                ->whereMonth('created_at', $mes)
                ->whereYear('created_at', $ciclo_año)
                ->get();

            $meses[] = [
                'mes' => $mes,
                'mes_nombre' => $this->getNombreMes($mes),
                'movimientos' => $movimientos->count(),
                'montos' => round($movimientos->sum('monto'), 2),
                'gasto_promedio' => $movimientos->count() > 0 
                    ? round($movimientos->sum('monto') / $movimientos->count(), 2)
                    : 0,
            ];
        }

        return $meses;
    }

    /**
     * Helper: Obtener nombre del mes
     */
    private function getNombreMes($mes): string
    {
        $meses = [
            'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
            'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'
        ];
        return $meses[$mes - 1] ?? 'Mes ' . $mes;
    }

    /**
     * Obtener estadísticas por apoyo
     */
    public function estadisticasApoyo(): array
    {
        $apoyos = PresupuestoApoyo::with('categoria', 'movimientos')
            ->get()
            ->map(function ($apoyo) {
                return [
                    'id' => $apoyo->id_apoyo_presupuesto,
                    'folio' => $apoyo->folio,
                    'categoria' => $apoyo->categoria->nombre ?? 'N/A',
                    'monto_solicitado' => $apoyo->monto_solicitado,
                    'monto_aprobado' => $apoyo->monto_aprobado,
                    'estado' => $apoyo->estado,
                    'movimientos' => $apoyo->movimientos->count(),
                    'porcentaje_ejecucion' => $apoyo->monto_aprobado > 0
                        ? round(($apoyo->movimientos->sum('monto') / $apoyo->monto_aprobado) * 100, 2)
                        : 0,
                ];
            });

        return [
            'total_apoyos' => $apoyos->count(),
            'apoyos_aprobados' => $apoyos->where('estado', 'APROBADO')->count(),
            'apoyos_pendientes' => $apoyos->where('estado', 'PENDIENTE')->count(),
            'apoyos_rechazados' => $apoyos->where('estado', 'RECHAZADO')->count(),
            'detalle' => $apoyos->toArray(),
        ];
    }
}
