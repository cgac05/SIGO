<?php

namespace App\Services;

use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;
use Barryvdh\DomPDF\Facade\Pdf;

class ExportPresupuestacionPdfService
{
    protected $service;
    protected $exportService;

    public function __construct(ReportePresupuestarioService $service, ExportPresupuestacionService $exportService)
    {
        $this->service = $service;
        $this->exportService = $exportService;
    }

    /**
     * Exportar dashboard presupuestación a PDF
     */
    public function exportarDashboardPdf()
    {
        $ciclo = CicloPresupuestario::where('estado', 'ABIERTO')->first();
        $categorias = PresupuestoCategoria::all();
        
        // Calcular totales
        $totalPresupuesto = $ciclo->presupuesto_total ?? 0;
        $totalUtilizado = $categorias->sum('presupuesto_utilizado');
        $totalDisponible = $totalPresupuesto - $totalUtilizado;
        $porcentajeDisponible = $totalPresupuesto > 0 ? ($totalDisponible / $totalPresupuesto) * 100 : 0;

        $data = [
            'titulo' => 'Dashboard Presupuestación SIGO',
            'fecha' => date('d/m/Y H:i'),
            'ciclo' => $ciclo,
            'totalPresupuesto' => $totalPresupuesto,
            'totalUtilizado' => $totalUtilizado,
            'totalDisponible' => $totalDisponible,
            'porcentajeDisponible' => $porcentajeDisponible,
            'categorias' => $categorias,
        ];

        $pdf = Pdf::loadView('exports.presupuesto-dashboard-pdf', $data);
        $pdf->setPaper('A4', 'landscape');
        return $pdf;
    }

    /**
     * Exportar reportes presupuestación a PDF
     */
    public function exportarReportesPdf($mes = null, $año = null)
    {
        $mes = $mes ?? date('m');
        $año = $año ?? date('Y');

        $reporteMensual = $this->service->generarReporteMensual($mes, $año);
        $alertas = $this->service->obtenerResumenAlertas();
        $estadisticas = $this->service->estadisticasApoyo();

        $data = [
            'titulo' => 'Reportes Presupuestación SIGO',
            'mes' => $mes,
            'año' => $año,
            'nombreMes' => $this->getNombreMes($mes),
            'fecha' => date('d/m/Y H:i'),
            'reporteMensual' => $reporteMensual,
            'alertas' => $alertas,
            'estadisticas' => $estadisticas,
        ];

        $pdf = Pdf::loadView('exports.presupuesto-reportes-pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf;
    }

    /**
     * Exportar categoría específica a PDF
     */
    public function exportarCategoriaPdf($idCategoria)
    {
        $categoria = PresupuestoCategoria::findOrFail($idCategoria);
        $ciclo = $categoria->ciclo;

        // Calcular disponible
        $disponible = $categoria->presupuesto_anual - $categoria->presupuesto_utilizado;
        $porcentaje = $categoria->presupuesto_anual > 0 ? ($categoria->presupuesto_utilizado / $categoria->presupuesto_anual) * 100 : 0;

        $data = [
            'titulo' => 'Detalle Categoría Presupuestaria',
            'fecha' => date('d/m/Y H:i'),
            'categoria' => $categoria,
            'ciclo' => $ciclo,
            'disponible' => $disponible,
            'porcentaje' => $porcentaje,
            'estado' => $this->getEstadoCategoria($porcentaje),
        ];

        $pdf = Pdf::loadView('exports.presupuesto-categoria-pdf', $data);
        $pdf->setPaper('A4', 'portrait');
        return $pdf;
    }

    /**
     * Obtener estado de categoría
     */
    protected function getEstadoCategoria($porcentaje)
    {
        if ($porcentaje >= 95) {
            return ['nivel' => 'CRÍTICA', 'color' => '#C00000', 'icon' => '⚠️'];
        } elseif ($porcentaje >= 85) {
            return ['nivel' => 'ROJA', 'color' => '#FF0000', 'icon' => '⚠️'];
        } elseif ($porcentaje >= 70) {
            return ['nivel' => 'AMARILLA', 'color' => '#FFC000', 'icon' => '⚠'];
        } else {
            return ['nivel' => 'NORMAL', 'color' => '#00B050', 'icon' => '✓'];
        }
    }

    /**
     * Obtener nombre del mes en español
     */
    protected function getNombreMes($mes)
    {
        $meses = [
            1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
            5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
            9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
        ];
        return $meses[$mes] ?? 'Mes desconocido';
    }
}
