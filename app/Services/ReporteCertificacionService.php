<?php

namespace App\Services;

use App\Models\HistoricoCierre;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Font;
use Carbon\Carbon;
use ZipArchive;

/**
 * Servicio de Reportes y Exportación de Certificados Digitales
 * 
 * Genera PDFs profesionales con QR embebido, exporta a Excel,
 * y crea archivos ZIP con múltiples certificados.
 */
class ReporteCertificacionService
{
    /**
     * Generar PDF profesional de un certificado individual
     * 
     * @param int $id_historico
     * @return array [exito, razon, ruta_pdf, nombre_archivo]
     */
    public function generarPDFCertificado($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with('solicitud.beneficiario', 'usuario')->find($id_historico);
            if (!$desembolso) {
                return [
                    'exito' => false,
                    'razon' => 'Desembolso no encontrado',
                ];
            }

            if ($desembolso->estado_certificacion !== 'CERTIFICADO' && $desembolso->estado_certificacion !== 'VALIDADO') {
                return [
                    'exito' => false,
                    'razon' => 'El desembolso debe estar certificado',
                ];
            }

            // Preparar datos para el PDF
            $datos = [
                'id_historico' => $desembolso->id_historico,
                'folio' => $desembolso->fk_folio,
                'monto' => number_format($desembolso->monto_entregado, 2),
                'fecha_entrega' => $desembolso->fecha_entrega->format('d de F de Y'),
                'hora_entrega' => $desembolso->fecha_entrega->format('H:i:s'),
                'fecha_certificacion' => $desembolso->fecha_certificacion->format('d de F de Y H:i:s'),
                'beneficiario' => $desembolso->solicitud->beneficiario->display_name ?? 'N/A',
                'programa' => $desembolso->solicitud->apoyo->nombre_apoyo ?? 'N/A',
                'usuario_registrador' => $desembolso->usuario->display_name ?? 'N/A',
                'hash_certificado' => $desembolso->hash_certificado,
                'estado' => $desembolso->estado_certificacion,
                'ip_terminal' => $desembolso->ip_terminal,
                'ruta_qrcode' => $desembolso->ruta_qrcode,
                'cadena_custodia_count' => count($desembolso->cadena_custodia_json ?? []),
            ];

            // Generar PDF con Blade
            $pdf = Pdf::loadView('reportes.certificado-pdf', $datos);
            $pdf->setPaper('A4');
            $pdf->setOption([
                'margin-top' => 15,
                'margin-bottom' => 15,
                'margin-left' => 15,
                'margin-right' => 15,
            ]);

            // Guardar en storage
            $directorio = 'public/reportes/certificados';
            if (!Storage::exists($directorio)) {
                Storage::makeDirectory($directorio, 0755, true);
            }

            $nombre_archivo = 'CERTIFICADO_' . $desembolso->fk_folio . '_' . Carbon::now()->format('Ymd_His') . '.pdf';
            $ruta_archivo = $directorio . '/' . $nombre_archivo;

            Storage::put($ruta_archivo, $pdf->output());

            Log::channel('auditoria')->info('PDF certificado generado', [
                'id_historico' => $id_historico,
                'folio' => $desembolso->fk_folio,
                'archivo' => $nombre_archivo,
            ]);

            return [
                'exito' => true,
                'razon' => 'PDF generado exitosamente',
                'ruta_pdf' => $ruta_archivo,
                'nombre_archivo' => $nombre_archivo,
            ];
        } catch (\Exception $e) {
            Log::error('Error generando PDF: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generar reporte en Excel de certificados certificados
     * 
     * @param array $filtros [estado, fecha_inicio, fecha_fin, apoyo_id]
     * @return array [exito, razon, ruta_excel]
     */
    public function generarExcelCertificados($filtros = [])
    {
        try {
            // Construir query con filtros
            $query = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
                ->with('solicitud.beneficiario', 'usuario', 'solicitud.apoyo');

            if (isset($filtros['estado']) && $filtros['estado']) {
                $query->where('estado_certificacion', $filtros['estado']);
            }

            if (isset($filtros['fecha_inicio']) && $filtros['fecha_inicio']) {
                $query->whereDate('fecha_certificacion', '>=', $filtros['fecha_inicio']);
            }

            if (isset($filtros['fecha_fin']) && $filtros['fecha_fin']) {
                $query->whereDate('fecha_certificacion', '<=', $filtros['fecha_fin']);
            }

            if (isset($filtros['apoyo_id']) && $filtros['apoyo_id']) {
                $query->whereHas('solicitud', function ($q) use ($filtros) {
                    $q->where('fk_id_apoyo', $filtros['apoyo_id']);
                });
            }

            $certificados = $query->orderBy('fecha_certificacion', 'desc')->get();

            // Crear spreadsheet
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Certificados Digitales');

            // Encabezados
            $headers = ['Folio', 'Monto', 'Beneficiario', 'Programa', 'Fecha Entrega', 'Fecha Certificación', 
                        'Estado', 'Usuario Registrador', 'Flash Hash (primeros 16)', 'Cadena Custodia eventos'];

            $sheet->fromArray($headers, NULL, 'A1');

            // Estilos de encabezado
            $headerStyle = [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E78']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ];

            for ($col = 'A'; $col !== 'K'; $col++) {
                $sheet->getStyle($col . '1')->applyFromArray($headerStyle);
            }

            // Datos
            $row = 2;
            foreach ($certificados as $cert) {
                $sheet->setCellValue('A' . $row, $cert->fk_folio);
                $sheet->setCellValue('B' . $row, $cert->monto_entregado);
                $sheet->setCellValue('C' . $row, $cert->solicitud->beneficiario->display_name ?? 'N/A');
                $sheet->setCellValue('D' . $row, $cert->solicitud->apoyo->nombre_apoyo ?? 'N/A');
                $sheet->setCellValue('E' . $row, $cert->fecha_entrega->format('d/m/Y H:i'));
                $sheet->setCellValue('F' . $row, $cert->fecha_certificacion ? $cert->fecha_certificacion->format('d/m/Y H:i') : 'N/A');
                $sheet->setCellValue('G' . $row, $cert->estado_certificacion);
                $sheet->setCellValue('H' . $row, $cert->usuario->display_name ?? 'N/A');
                $sheet->setCellValue('I' . $row, substr($cert->hash_certificado ?? 'N/A', 0, 16));
                $sheet->setCellValue('J' . $row, count($cert->cadena_custodia_json ?? []));

                $row++;
            }

            // Ajustar anchos de columna
            $sheet->getColumnDimension('A')->setWidth(15);
            $sheet->getColumnDimension('B')->setWidth(12);
            $sheet->getColumnDimension('C')->setWidth(20);
            $sheet->getColumnDimension('D')->setWidth(20);
            $sheet->getColumnDimension('E')->setWidth(18);
            $sheet->getColumnDimension('F')->setWidth(18);
            $sheet->getColumnDimension('G')->setWidth(12);
            $sheet->getColumnDimension('H')->setWidth(18);
            $sheet->getColumnDimension('I')->setWidth(18);
            $sheet->getColumnDimension('J')->setWidth(18);

            // Guardar archivo
            $directorio = 'public/reportes/excel';
            if (!Storage::exists($directorio)) {
                Storage::makeDirectory($directorio, 0755, true);
            }

            $nombre_archivo = 'CERTIFICADOS_' . Carbon::now()->format('Ymd_His') . '.xlsx';
            $ruta_archivo = $directorio . '/' . $nombre_archivo;
            $ruta_completa = storage_path('app') . '/' . $ruta_archivo;

            $writer = new Xlsx($spreadsheet);
            $writer->save($ruta_completa);

            Log::channel('auditoria')->info('Excel certificados generado', [
                'total_registros' => count($certificados),
                'archivo' => $nombre_archivo,
            ]);

            return [
                'exito' => true,
                'razon' => 'Excel generado exitosamente',
                'ruta_excel' => $ruta_archivo,
                'nombre_archivo' => $nombre_archivo,
                'total_registros' => count($certificados),
            ];
        } catch (\Exception $e) {
            Log::error('Error generando Excel: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generar ZIP con múltiples PDFs de certificados
     * 
     * @param array $ids_historico
     * @return array [exito, razon, ruta_zip, nombre_archivo]
     */
    public function generarZIPMultiplePDFs($ids_historico = [])
    {
        try {
            // Si no se especifican IDs, generar para todos los certificados
            if (empty($ids_historico)) {
                $desembolsos = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
                    ->limit(100)
                    ->get();
            } else {
                $desembolsos = HistoricoCierre::whereIn('id_historico', $ids_historico)
                    ->get();
            }

            if ($desembolsos->isEmpty()) {
                return [
                    'exito' => false,
                    'razon' => 'No hay certificados para descargar',
                ];
            }

            // Crear directorio temporal
            $directorio = 'public/reportes/temporal';
            if (!Storage::exists($directorio)) {
                Storage::makeDirectory($directorio, 0755, true);
            }

            // Generar PDFs individuales
            $pdfs = [];
            foreach ($desembolsos as $desembolso) {
                $resultado = $this->generarPDFCertificado($desembolso->id_historico);
                if ($resultado['exito']) {
                    $pdfs[] = $resultado;
                }
            }

            if (empty($pdfs)) {
                return [
                    'exito' => false,
                    'razon' => 'No se pudieron generar los PDFs',
                ];
            }

            // Crear ZIP
            $zip = new ZipArchive();
            $nombre_zip = 'CERTIFICADOS_' . Carbon::now()->format('Ymd_His') . '.zip';
            $ruta_zip_temporal = base_path('storage/app/' . $directorio . '/' . $nombre_zip);

            if ($zip->open($ruta_zip_temporal, ZipArchive::CREATE) !== true) {
                return [
                    'exito' => false,
                    'razon' => 'No se pudo crear el archivo ZIP',
                ];
            }

            // Agregar PDFs al ZIP
            foreach ($pdfs as $pdf) {
                $ruta_completa = base_path('storage/app/' . $pdf['ruta_pdf']);
                if (file_exists($ruta_completa)) {
                    $zip->addFile($ruta_completa, $pdf['nombre_archivo']);
                }
            }

            $zip->close();

            // Mover a ubicación final
            $directorio_final = 'public/reportes/zip';
            if (!Storage::exists($directorio_final)) {
                Storage::makeDirectory($directorio_final, 0755, true);
            }

            $ruta_final = $directorio_final . '/' . $nombre_zip;
            rename($ruta_zip_temporal, base_path('storage/app/' . $ruta_final));

            Log::channel('auditoria')->info('ZIP con múltiples PDFs generado', [
                'cantidad_pdfs' => count($pdfs),
                'archivo' => $nombre_zip,
            ]);

            return [
                'exito' => true,
                'razon' => 'ZIP generado exitosamente',
                'ruta_zip' => $ruta_final,
                'nombre_archivo' => $nombre_zip,
                'cantidad_pdfs' => count($pdfs),
            ];
        } catch (\Exception $e) {
            Log::error('Error generando ZIP: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generar reporte de estadísticas en PDF
     * 
     * @param array $estadisticas
     * @param array $ejecucion_por_categoria
     * @return array [exito, razon, ruta_pdf]
     */
    public function generarReporteEstadisticasPDF($estadisticas, $ejecucion_por_categoria = [])
    {
        try {
            $datos = [
                'estadisticas' => $estadisticas,
                'ejecucion_por_categoria' => $ejecucion_por_categoria,
                'fecha_reporte' => Carbon::now()->format('d de F de Y H:i:s'),
                'total_certificados_json' => count($ejecucion_por_categoria),
            ];

            $pdf = Pdf::loadView('reportes.estadisticas-pdf', $datos);
            $pdf->setPaper('A4', 'landscape');
            $pdf->setOption([
                'margin-top' => 15,
                'margin-bottom' => 15,
                'margin-left' => 15,
                'margin-right' => 15,
            ]);

            $directorio = 'public/reportes/estadisticas';
            if (!Storage::exists($directorio)) {
                Storage::makeDirectory($directorio, 0755, true);
            }

            $nombre_archivo = 'REPORTE_ESTADISTICAS_' . Carbon::now()->format('Ymd_His') . '.pdf';
            $ruta_archivo = $directorio . '/' . $nombre_archivo;

            Storage::put($ruta_archivo, $pdf->output());

            Log::channel('auditoria')->info('Reporte estadísticas PDF generado', [
                'fecha' => Carbon::now(),
            ]);

            return [
                'exito' => true,
                'razon' => 'Reporte generado',
                'ruta_pdf' => $ruta_archivo,
                'nombre_archivo' => $nombre_archivo,
            ];
        } catch (\Exception $e) {
            Log::error('Error generando reporte estadísticas: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Generar reporte de Cadena de Custodia por certificado
     */
    public function generarReporteCadenaCustodiaPDF($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with('solicitud.beneficiario', 'usuario')->find($id_historico);
            if (!$desembolso) {
                return [
                    'exito' => false,
                    'razon' => 'Desembolso no encontrado',
                ];
            }

            $datos = [
                'desembolso' => $desembolso,
                'cadena_custodia' => $desembolso->cadena_custodia_json ?? [],
                'fecha_reporte' => Carbon::now()->format('d de F de Y H:i:s'),
            ];

            $pdf = Pdf::loadView('reportes.cadena-custodia-pdf', $datos);
            $pdf->setPaper('A4');

            $directorio = 'public/reportes/cadena-custodia';
            if (!Storage::exists($directorio)) {
                Storage::makeDirectory($directorio, 0755, true);
            }

            $nombre_archivo = 'CADENA_CUSTODIA_' . $desembolso->fk_folio . '_' . Carbon::now()->format('Ymd_His') . '.pdf';
            $ruta_archivo = $directorio . '/' . $nombre_archivo;

            Storage::put($ruta_archivo, $pdf->output());

            return [
                'exito' => true,
                'razon' => 'Reporte cadena custodia generado',
                'ruta_pdf' => $ruta_archivo,
                'nombre_archivo' => $nombre_archivo,
            ];
        } catch (\Exception $e) {
            Log::error('Error generando cadena custodia: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }
}
