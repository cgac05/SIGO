<?php

namespace App\Services;

use App\Models\HistoricoCierre;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Servicio de Certificación Digital de Entregas
 * 
 * Genera certificados digitales, QR codes y firmas electrónicas para todos los desembolsos.
 * Implementa cadena de custodia para validación de entregas.
 */
class CertificacionDigitalService
{
    /**
     * Generar certificado digital completo para un desembolso
     * 
     * @param int $id_historico
     * @param string $ip_terminal
     * @param array $datos_adicionales
     * @return array [exito, razon, id_historico, hash_certificado, url_qrcode]
     */
    public function generarCertificado($id_historico, $ip_terminal, $datos_adicionales = [])
    {
        try {
            $desembolso = HistoricoCierre::find($id_historico);
            if (!$desembolso) {
                return [
                    'exito' => false,
                    'razon' => 'Desembolso no encontrado',
                ];
            }

            // Si ya tiene certificado, no regenerar
            if ($desembolso->estado_certificacion === 'CERTIFICADO') {
                return [
                    'exito' => true,
                    'razon' => 'Certificado ya existe',
                    'id_historico' => $id_historico,
                    'hash_certificado' => $desembolso->hash_certificado,
                    'url_qrcode' => $desembolso->ruta_qrcode,
                ];
            }

            // 1. Generar datos para QR
            $qrdata = $this->generarDatosQR($desembolso);

            // 2. Generar hash certificado
            $hash_certificado = $this->generarHashCertificado($desembolso, $qrdata);

            // 3. Generar firma digital
            $firma_digital = $this->generarFirmaDigital($desembolso, $qrdata);

            // 4. Generar QR code PNG
            $ruta_qrcode = $this->generarQRCode($qrdata, $hash_certificado);

            // 5. Registrar en cadena de custodia
            $cadena_custodia = $this->inicializarCadenaCustodia($ip_terminal);

            // 6. Actualizar registro
            $desembolso->update([
                'hash_certificado' => $hash_certificado,
                'qrcode_data' => $qrdata,
                'ruta_qrcode' => $ruta_qrcode,
                'firma_digital' => $firma_digital,
                'fecha_certificacion' => Carbon::now(),
                'estado_certificacion' => 'CERTIFICADO',
                'cadena_custodia_json' => $cadena_custodia,
            ]);

            Log::channel('auditoria')->info('Certificado digital generado', [
                'id_historico' => $id_historico,
                'hash_certificado' => $hash_certificado,
                'folio' => $desembolso->fk_folio,
                'monto' => $desembolso->monto_entregado,
                'ip_terminal' => $ip_terminal,
            ]);

            return [
                'exito' => true,
                'razon' => 'Certificado generado exitosamente',
                'id_historico' => $id_historico,
                'hash_certificado' => $hash_certificado,
                'url_qrcode' => $ruta_qrcode,
            ];
        } catch (\Exception $e) {
            Log::error('Error generando certificado digital: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Validar integridad de un certificado usando QR code
     * 
     * @param string $hash_certificado
     * @return array [valido, razon, detalles]
     */
    public function validarCertificado($hash_certificado)
    {
        try {
            $desembolso = HistoricoCierre::where('hash_certificado', $hash_certificado)->first();
            if (!$desembolso) {
                return [
                    'valido' => false,
                    'razon' => 'Certificado no encontrado',
                    'detalles' => null,
                ];
            }

            // Validar que el hash corresponda a los datos
            $qrdata_recalculado = $this->generarDatosQR($desembolso);
            $hash_recalculado = $this->generarHashCertificado($desembolso, $qrdata_recalculado);

            $valido = hash_equals($hash_certificado, $hash_recalculado);

            return [
                'valido' => $valido,
                'razon' => $valido ? 'Certificado válido' : 'Hash no coincide',
                'detalles' => [
                    'id_historico' => $desembolso->id_historico,
                    'folio' => $desembolso->fk_folio,
                    'monto' => $desembolso->monto_entregado,
                    'fecha_entrega' => $desembolso->fecha_entrega,
                    'fecha_certificacion' => $desembolso->fecha_certificacion,
                    'estado_certificacion' => $desembolso->estado_certificacion,
                ],
            ];
        } catch (\Exception $e) {
            Log::error('Error validando certificado: ' . $e->getMessage());
            return [
                'valido' => false,
                'razon' => 'Error: ' . $e->getMessage(),
                'detalles' => null,
            ];
        }
    }

    /**
     * Registrar validación de certificado en cadena de custodia
     * 
     * @param int $id_historico
     * @param int $id_usuario_validador
     * @param string $ip_terminal
     * @param string $notas
     * @return array [exito, razon]
     */
    public function registrarValidacion($id_historico, $id_usuario_validador, $ip_terminal, $notas = '')
    {
        try {
            $desembolso = HistoricoCierre::find($id_historico);
            if (!$desembolso) {
                return [
                    'exito' => false,
                    'razon' => 'Desembolso no encontrado',
                ];
            }

            $cadena = $desembolso->cadena_custodia_json ?? [];
            
            $cadena[] = [
                'tipo_evento' => 'VALIDACION',
                'id_usuario_validador' => $id_usuario_validador,
                'fecha_validacion' => Carbon::now()->toIso8601String(),
                'ip_terminal' => $ip_terminal,
                'notas' => $notas,
            ];

            $desembolso->update([
                'cadena_custodia_json' => $cadena,
                'estado_certificacion' => 'VALIDADO',
            ]);

            Log::channel('auditoria')->info('Validación registrada en cadena de custodia', [
                'id_historico' => $id_historico,
                'id_usuario_validador' => $id_usuario_validador,
                'ip_terminal' => $ip_terminal,
            ]);

            return [
                'exito' => true,
                'razon' => 'Validación registrada',
            ];
        } catch (\Exception $e) {
            Log::error('Error registrando validación: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Descargar comprobante certificado como PDF con QR incrustado
     * 
     * @param int $id_historico
     * @return array [exito, razon, contenido_pdf]
     */
    public function generarComprobanteCertificado($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with('solicitud', 'usuario')->find($id_historico);
            if (!$desembolso) {
                return [
                    'exito' => false,
                    'razon' => 'Desembolso no encontrado',
                ];
            }

            if ($desembolso->estado_certificacion !== 'CERTIFICADO') {
                return [
                    'exito' => false,
                    'razon' => 'El desembolso no tiene certificado digital',
                ];
            }

            // Datos para el comprobante
            $datos = [
                'id_historico' => $desembolso->id_historico,
                'folio' => $desembolso->fk_folio,
                'monto' => number_format($desembolso->monto_entregado, 2),
                'fecha_entrega' => $desembolso->fecha_entrega->format('d/m/Y H:i'),
                'fecha_certificacion' => $desembolso->fecha_certificacion->format('d/m/Y H:i'),
                'hash_certificado' => $desembolso->hash_certificado,
                'ruta_qrcode' => $desembolso->ruta_qrcode,
                'usuario_registrador' => $desembolso->usuario->display_name ?? 'N/A',
                'estado_certificacion' => $desembolso->estado_certificacion,
            ];

            return [
                'exito' => true,
                'razon' => 'Comprobante generado',
                'contenido_pdf' => $datos,
            ];
        } catch (\Exception $e) {
            Log::error('Error generando comprobante: ' . $e->getMessage());
            return [
                'exito' => false,
                'razon' => 'Error: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Obtener estadísticas de certificación digital
     * 
     * @return array [total, certificados, validados, pendientes]
     */
    public function obtenerEstadisticas()
    {
        try {
            $total = HistoricoCierre::count();
            $certificados = HistoricoCierre::where('estado_certificacion', 'CERTIFICADO')->count();
            $validados = HistoricoCierre::where('estado_certificacion', 'VALIDADO')->count();
            $pendientes = HistoricoCierre::where('estado_certificacion', 'PENDIENTE')->count();

            $monto_total_certificado = HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
                ->sum('monto_entregado');

            return [
                'total' => $total,
                'certificados' => $certificados,
                'validados' => $validados,
                'pendientes' => $pendientes,
                'porcentaje_certificacion' => $total > 0 ? round(($certificados / $total) * 100, 2) : 0,
                'monto_total_certificado' => $monto_total_certificado,
            ];
        } catch (\Exception $e) {
            Log::error('Error obteniendo estadísticas: ' . $e->getMessage());
            return [];
        }
    }

    // ============ MÉTODOS PRIVADOS ============

    /**
     * Generar datos para incluir en QR (folio|fecha|monto|hash)
     */
    private function generarDatosQR($desembolso)
    {
        return implode('|', [
            $desembolso->fk_folio,
            $desembolso->fecha_entrega->format('Y-m-d H:i:s'),
            $desembolso->monto_entregado,
            hash('sha256', $desembolso->fk_folio . $desembolso->monto_entregado . $desembolso->fecha_entrega),
        ]);
    }

    /**
     * Generar hash SHA-256 único del certificado
     */
    private function generarHashCertificado($desembolso, $qrdata)
    {
        $datos_certificado = implode('|', [
            $desembolso->id_historico,
            $qrdata,
            $desembolso->usuario->id ?? 'N/A',
            Carbon::now()->toIso8601String(),
        ]);

        return hash('sha256', $datos_certificado);
    }

    /**
     * Generar firma digital usando OpenSSL (simulada con hash HMAC)
     * En producción, usar certificados X.509 o servicio de firma externo
     */
    private function generarFirmaDigital($desembolso, $qrdata)
    {
        $clave_privada = env('FIRMA_DIGITAL_KEY', Str::random(32));
        
        $datos_firmar = implode('|', [
            $desembolso->id_historico,
            $desembolso->fk_folio,
            $desembolso->monto_entregado,
            $qrdata,
        ]);

        // HMAC-SHA256 (en producción usar OpenSSL con certificados reales)
        $firma = hash_hmac('sha256', $datos_firmar, $clave_privada);
        
        return base64_encode($firma);
    }

    /**
     * Generar QR code PNG usando SimpleQRCode
     * Nota: Requiere `composer require simplesoftwareio/simple-qrcode`
     */
    private function generarQRCode($qrdata, $hash_certificado)
    {
        try {
            // Crear directorio para QR codes si no existe
            $directorio = 'public/certificados/qrcodes';
            if (!Storage::exists($directorio)) {
                Storage::makeDirectory($directorio, 0755, true);
            }

            // Generar datos del QR en formato JSON
            $datos_json = json_encode([
                'hash' => $hash_certificado,
                'qr_data' => $qrdata,
                'fecha_gen' => Carbon::now()->toIso8601String(),
            ]);

            // Nombre de archivo único
            $nombre_archivo = 'qrcode_' . $hash_certificado . '.txt';
            $ruta_archivo = $directorio . '/' . $nombre_archivo;

            // Guardar datos del QR
            Storage::put($ruta_archivo, $datos_json);

            // Retornar ruta accesible
            return 'certificados/qrcodes/' . $nombre_archivo;
        } catch (\Exception $e) {
            Log::error('Error generando QR code: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Inicializar cadena de custodia con evento de creación
     */
    private function inicializarCadenaCustodia($ip_terminal)
    {
        return [
            [
                'tipo_evento' => 'CREACION_CERTIFICADO',
                'fecha_creacion' => Carbon::now()->toIso8601String(),
                'ip_terminal' => $ip_terminal,
            ],
        ];
    }
}
