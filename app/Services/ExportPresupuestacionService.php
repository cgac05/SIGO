<?php

namespace App\Services;

use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;
use App\Models\PresupuestoApoyo;
use App\Models\AlertaPresupuesto;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportPresupuestacionService
{
    protected $service;

    public function __construct(ReportePresupuestarioService $service)
    {
        $this->service = $service;
    }

    /**
     * Exportar dashboard presupuestación a Excel
     */
    public function exportarDashboardExcel()
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Dashboard');

        // Título principal
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'DASHBOARD PRESUPUESTACIÓN SIGO - ' . date('Y-m-d'));
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(25);

        // KPI Summary
        $row = 3;
        $ciclo = CicloPresupuestario::where('estado', 'ABIERTO')->first();
        
        $sheet->setCellValue('A' . $row, 'KPI RESUMEN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        $kpis = [
            ['Ciclo Presupuestario:', $ciclo?->año ?? 'N/A'],
            ['Estado del Ciclo:', $ciclo?->estado ?? 'N/A'],
            ['Presupuesto Total:', $ciclo?->presupuesto_total ? '$' . number_format($ciclo->presupuesto_total, 2) : 'N/A'],
            ['Total Categorías:', PresupuestoCategoria::count()],
        ];

        foreach ($kpis as $kpi) {
            $sheet->setCellValue('A' . $row, $kpi[0]);
            $sheet->setCellValue('B' . $row, $kpi[1]);
            $sheet->getStyle('A' . $row)->getFont()->setBold(true);
            $row++;
        }

        // Tabla de Categorías
        $row += 2;
        $sheet->setCellValue('A' . $row, 'DETALLE DE CATEGORÍAS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(12);
        $row++;

        // Headers
        $headers = ['Categoría', 'Presupuesto Anual', 'Utilizado', 'Disponible', 'Porcentaje Utilizado', 'Estado'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . $row, $header);
            $sheet->getStyle(chr(65 + $col) . $row)->getFont()->setBold(true)->setColor('FFFFFF');
            $sheet->getStyle(chr(65 + $col) . $row)->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor('1F4E78');
        }
        $row++;

        // Data
        $categorias = PresupuestoCategoria::all();
        foreach ($categorias as $cat) {
            $sheet->setCellValue('A' . $row, $cat->nombre);
            $sheet->setCellValue('B' . $row, $cat->presupuesto_anual);
            $sheet->setCellValue('C' . $row, $cat->presupuesto_utilizado);
            $sheet->setCellValue('D' . $row, $cat->presupuesto_anual - $cat->presupuesto_utilizado);
            $porcentaje = ($cat->presupuesto_anual > 0) ? ($cat->presupuesto_utilizado / $cat->presupuesto_anual) * 100 : 0;
            $sheet->setCellValue('E' . $row, number_format($porcentaje, 2) . '%');
            $sheet->setCellValue('F' . $row, $this->getEstadoCategoria($porcentaje));

            // Formato moneda
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

            // Color por estado
            $color = $this->getColorEstado($porcentaje);
            $sheet->getStyle('F' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor($color);

            $row++;
        }

        // Auto-fit columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $spreadsheet;
    }

    /**
     * Exportar reportes mensuales a Excel
     */
    public function exportarReportesMensualExcel($mes = null, $año = null)
    {
        $mes = $mes ?? date('m');
        $año = $año ?? date('Y');

        $spreadsheet = new Spreadsheet();

        // Sheet 1: Resumen Mensual
        $this->crearSheetResumenMensual($spreadsheet, $mes, $año);

        // Sheet 2: Alertas
        $this->crearSheetAlertas($spreadsheet);

        // Sheet 3: Apoyos
        $this->crearSheetApoyos($spreadsheet);

        return $spreadsheet;
    }

    /**
     * Crear sheet de resumen mensual
     */
    protected function crearSheetResumenMensual(Spreadsheet $spreadsheet, $mes, $año)
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen Mensual');

        // Título
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', "RESUMEN MENSUAL - " . $this->getNombreMes($mes) . " " . $año);
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(25);

        $row = 3;

        // Headers
        $headers = ['Categoría', 'Presupuesto Anual', 'Utilizado', 'Disponible', 'Movimientos del Mes', '% Utilizado'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . $row, $header);
            $sheet->getStyle(chr(65 + $col) . $row)->getFont()->setBold(true)->setColor('FFFFFF');
            $sheet->getStyle(chr(65 + $col) . $row)->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor('366092');
        }
        $row++;

        // Data
        $reporteData = $this->service->generarReporteMensual($mes, $año);
        if ($reporteData && isset($reporteData['categorias'])) {
            foreach ($reporteData['categorias'] as $cat) {
                $sheet->setCellValue('A' . $row, $cat['nombre'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, $cat['presupuesto_anual'] ?? 0);
                $sheet->setCellValue('C' . $row, $cat['presupuesto_utilizado'] ?? 0);
                $sheet->setCellValue('D' . $row, ($cat['presupuesto_anual'] ?? 0) - ($cat['presupuesto_utilizado'] ?? 0));
                $sheet->setCellValue('E' . $row, $cat['movimientos_mes'] ?? 0);
                $porcentaje = ($cat['presupuesto_anual'] ?? 0) > 0 ? (($cat['presupuesto_utilizado'] ?? 0) / ($cat['presupuesto_anual'] ?? 0)) * 100 : 0;
                $sheet->setCellValue('F' . $row, number_format($porcentaje, 2) . '%');

                // Formato
                $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

                $row++;
            }
        }

        // Auto-fit columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Crear sheet de alertas
     */
    protected function crearSheetAlertas(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Alertas');

        // Título
        $sheet->mergeCells('A1:E1');
        $sheet->setCellValue('A1', 'ALERTAS PRESUPUESTARIAS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(25);

        $row = 3;

        // Resumen
        $alertas = $this->service->obtenerResumenAlertas();
        $sheet->setCellValue('A' . $row, 'RESUMEN DE ALERTAS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($alertas && isset($alertas['contadores'])) {
            $sheet->setCellValue('A' . $row, 'Total de Alertas:');
            $sheet->setCellValue('B' . $row, $alertas['contadores']['total'] ?? 0);
            $row++;

            $sheet->setCellValue('A' . $row, 'Alertas Críticas:');
            $sheet->setCellValue('B' . $row, $alertas['contadores']['critica'] ?? 0);
            $row++;

            $sheet->setCellValue('A' . $row, 'Alertas Rojas:');
            $sheet->setCellValue('B' . $row, $alertas['contadores']['roja'] ?? 0);
            $row++;

            $sheet->setCellValue('A' . $row, 'Alertas Amarillas:');
            $sheet->setCellValue('B' . $row, $alertas['contadores']['amarilla'] ?? 0);
            $row++;
        }

        $row += 2;

        // Detalle de alertas
        $sheet->setCellValue('A' . $row, 'DETALLE DE ALERTAS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $headers = ['Categoría', 'Porcentaje', 'Nivel', 'Presupuesto Disponible', 'Presupuesto Utilizado'];
        foreach ($headers as $col => $header) {
            $sheet->setCellValue(chr(65 + $col) . $row, $header);
            $sheet->getStyle(chr(65 + $col) . $row)->getFont()->setBold(true)->setColor('FFFFFF');
            $sheet->getStyle(chr(65 + $col) . $row)->getFill()->setFillType(Fill::FILL_SOLID)->setStartColor('C00000');
        }
        $row++;

        if ($alertas && isset($alertas['detalle'])) {
            foreach ($alertas['detalle'] as $alerta) {
                $sheet->setCellValue('A' . $row, $alerta['categoria'] ?? 'N/A');
                $sheet->setCellValue('B' . $row, number_format($alerta['porcentaje'] ?? 0, 2) . '%');
                $sheet->setCellValue('C' . $row, $alerta['nivel'] ?? 'N/A');
                $sheet->setCellValue('D' . $row, $alerta['disponible'] ?? 0);
                $sheet->setCellValue('E' . $row, $alerta['utilizado'] ?? 0);

                // Formato moneda
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');
                $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('$#,##0.00');

                $row++;
            }
        }

        // Auto-fit columns
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Crear sheet de apoyos
     */
    protected function crearSheetApoyos(Spreadsheet $spreadsheet)
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Apoyos');

        // Título
        $sheet->mergeCells('A1:F1');
        $sheet->setCellValue('A1', 'ESTADÍSTICAS DE APOYOS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(1)->setRowHeight(25);

        $row = 3;

        // Resumen
        $stats = $this->service->estadisticasApoyo();
        $sheet->setCellValue('A' . $row, 'RESUMEN GENERAL');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        if ($stats && isset($stats['totales'])) {
            $sheet->setCellValue('A' . $row, 'Total de Apoyos:');
            $sheet->setCellValue('B' . $row, $stats['totales']['total'] ?? 0);
            $row++;

            $sheet->setCellValue('A' . $row, 'Aprobados:');
            $sheet->setCellValue('B' . $row, $stats['totales']['aprobados'] ?? 0);
            $row++;

            $sheet->setCellValue('A' . $row, 'Pendientes:');
            $sheet->setCellValue('B' . $row, $stats['totales']['pendientes'] ?? 0);
            $row++;

            $sheet->setCellValue('A' . $row, 'Rechazados:');
            $sheet->setCellValue('B' . $row, $stats['totales']['rechazados'] ?? 0);
            $row++;

            $sheet->setCellValue('A' . $row, 'Porcentaje de Ejecución:');
            $sheet->setCellValue('B' . $row, number_format($stats['totales']['porcentaje_ejecucion'] ?? 0, 2) . '%');
            $row++;
        }

        // Auto-fit columns
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    /**
     * Obtener estado de categoría basado en porcentaje
     */
    protected function getEstadoCategoria($porcentaje)
    {
        if ($porcentaje >= 95) {
            return 'CRÍTICA';
        } elseif ($porcentaje >= 85) {
            return 'ROJA';
        } elseif ($porcentaje >= 70) {
            return 'AMARILLA';
        } else {
            return 'NORMAL';
        }
    }

    /**
     * Obtener color hexadecimal del estado
     */
    protected function getColorEstado($porcentaje)
    {
        if ($porcentaje >= 95) {
            return 'C00000'; // Rojo crítico
        } elseif ($porcentaje >= 85) {
            return 'FF0000'; // Rojo
        } elseif ($porcentaje >= 70) {
            return 'FFC000'; // Amarillo
        } else {
            return '00B050'; // Verde
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
