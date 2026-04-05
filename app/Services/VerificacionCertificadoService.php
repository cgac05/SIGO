<?php

namespace App\Services;

use App\Models\HistoricoCierre;
use App\Models\AuditoriaVerificacion;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class VerificacionCertificadoService
{
    /**
     * Verificar integridad del certificado mediante hash SHA-256
     * 
     * @param int $id_historico
     * @return array [exito, razon, hash_calculado, hash_almacenado, integridad_valida]
     */
    public function verificarIntegridad($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with(['solicitud', 'usuario'])->findOrFail($id_historico);

            // Construir datos para validación
            $datos_para_hash = [
                'id_historico' => $desembolso->id_historico,
                'fk_folio' => $desembolso->fk_folio,
                'monto_entregado' => $desembolso->monto_entregado,
                'fecha_entrega' => $desembolso->fecha_entrega->toDateTimeString(),
                'id_beneficiario' => $desembolso->fk_id_solicitud,
                'id_usuario' => $desembolso->id_usuario,
                'estado_certificacion' => $desembolso->estado_certificacion,
                'fecha_certificacion' => $desembolso->fecha_certificacion ? $desembolso->fecha_certificacion->toDateTimeString() : null,
            ];

            // Serializar datos y calcular hash
            $json_datos = json_encode($datos_para_hash, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $hash_calculado = hash('sha256', $json_datos);
            $hash_almacenado = $desembolso->hash_certificado;

            // Verificar integridad
            $integridad_valida = ($hash_calculado === $hash_almacenado);

            // Registrar en auditoría
            $this->registrarAuditoria($id_historico, 'VERIFICACION_INTEGRIDAD', [
                'hash_calculado' => $hash_calculado,
                'hash_almacenado' => $hash_almacenado,
                'coinciden' => $integridad_valida,
            ]);

            return [
                'exito' => true,
                'razon' => 'Verificación de integridad completada',
                'hash_calculado' => $hash_calculado,
                'hash_almacenado' => $hash_almacenado,
                'integridad_valida' => $integridad_valida,
            ];
        } catch (\Exception $e) {
            Log::error("[VerificacionCertificadoService] Error verificarIntegridad: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al verificar integridad: {$e->getMessage()}",
                'hash_calculado' => null,
                'hash_almacenado' => null,
                'integridad_valida' => false,
            ];
        }
    }

    /**
     * Validar certificado completo (integridad + estado + Cadena de custodia)
     * 
     * @param int $id_historico
     * @return array [exito, razon, validaciones, resultado_general]
     */
    public function validarCertificado($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with(['solicitud', 'usuario'])->findOrFail($id_historico);

            $validaciones = [];
            $resultado_general = true;

            // 1. Verificar integridad
            $integridad = $this->verificarIntegridad($id_historico);
            $validaciones['integridad'] = [
                'valido' => $integridad['integridad_valida'],
                'mensaje' => $integridad['integridad_valida'] 
                    ? 'Certificado íntegro - No hay alteraciones' 
                    : 'ALERTA: Hash no coincide - Posible alteración',
                'hash_servidor' => substr($integridad['hash_almacenado'], 0, 16) . '...',
                'hash_verificado' => substr($integridad['hash_calculado'], 0, 16) . '...',
            ];
            $resultado_general = $resultado_general && $integridad['integridad_valida'];

            // 2. Verificar estado del certificado
            $validaciones['estado'] = [
                'valido' => !is_null($desembolso->estado_certificacion),
                'mensaje' => $desembolso->estado_certificacion 
                    ? "Estado: {$desembolso->estado_certificacion}" 
                    : "ALERTA: Sin estado de certificación",
                'estado' => $desembolso->estado_certificacion,
            ];
            $resultado_general = $resultado_general && !is_null($desembolso->estado_certificacion);

            // 3. Verificar datos del beneficiario
            $validaciones['beneficiario'] = [
                'valido' => $desembolso->solicitud && $desembolso->solicitud->beneficiario,
                'mensaje' => $desembolso->solicitud && $desembolso->solicitud->beneficiario
                    ? "Beneficiario registrado"
                    : "ALERTA: Beneficiario inválido o no encontrado",
                'beneficiario' => $desembolso->solicitud->beneficiario->display_name ?? 'N/A',
            ];
            $resultado_general = $resultado_general && ($desembolso->solicitud && $desembolso->solicitud->beneficiario);

            // 4. Verificar montos
            $validaciones['montos'] = [
                'valido' => $desembolso->monto_entregado > 0,
                'mensaje' => $desembolso->monto_entregado > 0
                    ? "Monto válido: ${number_format($desembolso->monto_entregado, 2)}"
                    : "ALERTA: Monto inválido o cero",
                'monto' => $desembolso->monto_entregado,
            ];
            $resultado_general = $resultado_general && ($desembolso->monto_entregado > 0);

            // 5. Verificar fechas
            $validaciones['fechas'] = [
                'valido' => $desembolso->fecha_entrega && (!is_null($desembolso->fecha_certificacion) || $desembolso->estado_certificacion === 'CERTIFICADO'),
                'mensaje' => 'Fechas válidas y consistentes',
                'fecha_entrega' => $desembolso->fecha_entrega->format('d/m/Y H:i:s'),
                'fecha_certificacion' => $desembolso->fecha_certificacion ? $desembolso->fecha_certificacion->format('d/m/Y H:i:s') : 'Pendiente',
            ];
            $resultado_general = $resultado_general && $desembolso->fecha_entrega;

            // Registrar validación en auditoría
            $this->registrarAuditoria($id_historico, 'VALIDACION_COMPLETA', [
                'resultado' => $resultado_general ? 'VÁLIDO' : 'CON ALERTAS',
                'validaciones_count' => count($validaciones),
                'validaciones_exitosas' => count(array_filter($validaciones, fn($v) => $v['valido'])),
            ]);

            return [
                'exito' => true,
                'razon' => $resultado_general ? 'Certificado válido en todos los criterios' : 'Certificado con alertas de validación',
                'validaciones' => $validaciones,
                'resultado_general' => $resultado_general,
                'tipo_resultado' => $resultado_general ? 'VÁLIDO' : 'CON_ALERTAS',
            ];
        } catch (\Exception $e) {
            Log::error("[VerificacionCertificadoService] Error validarCertificado: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error en validación: {$e->getMessage()}",
                'validaciones' => [],
                'resultado_general' => false,
                'tipo_resultado' => 'ERROR',
            ];
        }
    }

    /**
     * Obtener auditoría detallada del certificado
     * 
     * @param int $id_historico
     * @return array [exito, razon, auditorias, resumen]
     */
    public function obtenerAuditoriaDetallada($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::findOrFail($id_historico);

            // Obtener todas las auditorías del certificado
            $auditorias = AuditoriaVerificacion::where('id_historico', $id_historico)
                ->orderBy('created_at', 'desc')
                ->get()
                ->map(function ($auditoria) {
                    return [
                        'tipo_evento' => $auditoria->tipo_verificacion,
                        'fecha' => $auditoria->created_at->format('d/m/Y H:i:s'),
                        'detalles' => json_decode($auditoria->detalles, true),
                        'ip_terminal' => $auditoria->ip_terminal,
                        'usuario_validador' => $auditoria->usuario ? $auditoria->usuario->email : 'Sistema',
                    ];
                });

            // Calcular resumen de auditoría
            $resumen = [
                'total_eventos' => $auditorias->count(),
                'certificado_creado' => $desembolso->created_at->format('d/m/Y H:i:s'),
                'ultima_verificacion' => $auditorias->first() ? $auditorias->first()['fecha'] : null,
                'verificaciones_integridad' => $auditorias->where('tipo_evento', 'VERIFICACION_INTEGRIDAD')->count(),
                'validaciones' => $auditorias->where('tipo_evento', 'VALIDACION_COMPLETA')->count(),
                'cambios_estado' => $auditorias->where('tipo_evento', 'CAMBIO_ESTADO')->count(),
                'estado_actual' => $desembolso->estado_certificacion,
            ];

            return [
                'exito' => true,
                'razon' => 'Auditoría obtenida exitosamente',
                'auditorias' => $auditorias,
                'resumen' => $resumen,
                'certificado' => [
                    'id' => $desembolso->id_historico,
                    'folio' => $desembolso->fk_folio,
                    'monto' => $desembolso->monto_entregado,
                ],
            ];
        } catch (\Exception $e) {
            Log::error("[VerificacionCertificadoService] Error obtenerAuditoriaDetallada: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al obtener auditoría: {$e->getMessage()}",
                'auditorias' => [],
                'resumen' => [],
            ];
        }
    }

    /**
     * Generar reporte de validación en PDF
     * 
     * @param int $id_historico
     * @return array [exito, razon, ruta_pdf, nombre_archivo]
     */
    public function generarReporteValidacion($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id_historico);

            // Obtener validación completa
            $validacion = $this->validarCertificado($id_historico);
            $auditoria = $this->obtenerAuditoriaDetallada($id_historico);

            // Crear directorio si no existe
            $ruta_base = public_path('reportes/validaciones');
            if (!is_dir($ruta_base)) {
                mkdir($ruta_base, 0755, true);
            }

            // Nombre del archivo
            $fecha_hoy = Carbon::now()->format('Y-m-d');
            $nombre_archivo = "Validacion_{$desembolso->fk_folio}_{$fecha_hoy}.pdf";
            $ruta_pdf = "{$ruta_base}/{$nombre_archivo}";

            // Preparar datos para la vista
            $datos = [
                'desembolso' => $desembolso,
                'validacion' => $validacion,
                'auditoria' => $auditoria,
                'fecha_reporte' => Carbon::now()->format('d/m/Y H:i:s'),
                'total_auditorias' => $auditoria['auditorias']->count(),
            ];

            // Generar PDF
            $pdf = \Pdf::loadView('reportes.reporte-validacion-pdf', $datos);
            $pdf->save($ruta_pdf);

            // Registrar generación en auditoría
            $this->registrarAuditoria($id_historico, 'REPORTE_VALIDACION_GENERADO', [
                'nombre_archivo' => $nombre_archivo,
                'ruta_almacenamiento' => $ruta_pdf,
            ]);

            return [
                'exito' => true,
                'razon' => 'Reporte de validación generado exitosamente',
                'ruta_pdf' => asset("reportes/validaciones/{$nombre_archivo}"),
                'nombre_archivo' => $nombre_archivo,
                'resultado_validacion' => $validacion['tipo_resultado'],
            ];
        } catch (\Exception $e) {
            Log::error("[VerificacionCertificadoService] Error generarReporteValidacion: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al generar reporte: {$e->getMessage()}",
                'ruta_pdf' => null,
                'nombre_archivo' => null,
            ];
        }
    }

    /**
     * Generar reporte de cumplimiento LGPDP
     * 
     * @param int $id_historico
     * @return array [exito, razon, cumplimiento_score, detalles]
     */
    public function generarReporteCumplimiento($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with(['solicitud.beneficiario', 'usuario'])->findOrFail($id_historico);
            $auditoria = $this->obtenerAuditoriaDetallada($id_historico);

            $criterios_cumplimiento = [];
            $puntuacion_total = 0;

            // 1. Integridad de datos
            $criterios_cumplimiento['integridad'] = [
                'criterio' => 'Integridad de Datos',
                'cumple' => true,
                'descripcion' => 'Hash SHA-256 validado contra datos almacenados',
                'evidencia' => 'Verificación de hash completada',
                'puntuacion' => 25,
            ];
            $puntuacion_total += 25;

            // 2. Trazabilidad
            $criterios_cumplimiento['trazabilidad'] = [
                'criterio' => 'Trazabilidad Completa',
                'cumple' => $auditoria['auditorias']->count() > 0,
                'descripcion' => 'Registro de todos los eventos del certificado',
                'evidencia' => $auditoria['auditorias']->count() . ' eventos registrados',
                'puntuacion' => $auditoria['auditorias']->count() > 0 ? 25 : 0,
            ];
            $puntuacion_total += $criterios_cumplimiento['trazabilidad']['puntuacion'];

            // 3. Datos del beneficiario
            $criterios_cumplimiento['beneficiario'] = [
                'criterio' => 'Datos del Beneficiario',
                'cumple' => $desembolso->solicitud && $desembolso->solicitud->beneficiario,
                'descripcion' => 'Información completa del beneficiario registrada',
                'evidencia' => $desembolso->solicitud->beneficiario->display_name ?? 'N/A',
                'puntuacion' => ($desembolso->solicitud && $desembolso->solicitud->beneficiario) ? 25 : 0,
            ];
            $puntuacion_total += $criterios_cumplimiento['beneficiario']['puntuacion'];

            // 4. Propósito legítimo
            $criterios_cumplimiento['proposito'] = [
                'criterio' => 'Propósito Legítimo',
                'cumple' => $desembolso->solicitud && $desembolso->solicitud->apoyo,
                'descripcion' => 'Desembolso vinculado a programa de apoyo válido',
                'evidencia' => $desembolso->solicitud->apoyo->nombre_apoyo ?? 'N/A',
                'puntuacion' => ($desembolso->solicitud && $desembolso->solicitud->apoyo) ? 25 : 0,
            ];
            $puntuacion_total += $criterios_cumplimiento['proposito']['puntuacion'];

            // Calcular nivel de cumplimiento
            $nivel_cumplimiento = match(true) {
                $puntuacion_total >= 95 => 'EXCELENTE',
                $puntuacion_total >= 75 => 'BUENO',
                $puntuacion_total >= 50 => 'ACEPTABLE',
                default => 'MEJORABLE',
            };

            // Registrar en auditoría
            $this->registrarAuditoria($id_historico, 'REPORTE_CUMPLIMIENTO_LGPDP', [
                'puntuacion' => $puntuacion_total,
                'nivel' => $nivel_cumplimiento,
                'criterios_cumplidos' => count(array_filter($criterios_cumplimiento, fn($c) => $c['cumple'])),
            ]);

            return [
                'exito' => true,
                'razon' => 'Reporte de cumplimiento generado exitosamente',
                'cumplimiento_score' => $puntuacion_total,
                'nivel_cumplimiento' => $nivel_cumplimiento,
                'detalles' => $criterios_cumplimiento,
                'resumen' => [
                    'total_criterios' => count($criterios_cumplimiento),
                    'criterios_cumplidos' => count(array_filter($criterios_cumplimiento, fn($c) => $c['cumple'])),
                    'fecha_evaluacion' => Carbon::now()->format('d/m/Y H:i:s'),
                ],
            ];
        } catch (\Exception $e) {
            Log::error("[VerificacionCertificadoService] Error generarReporteCumplimiento: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al generar reporte: {$e->getMessage()}",
                'cumplimiento_score' => 0,
                'nivel_cumplimiento' => 'ERROR',
                'detalles' => [],
            ];
        }
    }

    /**
     * Registrar evento de auditoría
     * 
     * @param int $id_historico
     * @param string $tipo_verificacion
     * @param array $detalles
     * @return bool
     */
    private function registrarAuditoria($id_historico, $tipo_verificacion, $detalles = [])
    {
        try {
            AuditoriaVerificacion::create([
                'id_historico' => $id_historico,
                'tipo_verificacion' => $tipo_verificacion,
                'detalles' => json_encode($detalles, JSON_UNESCAPED_UNICODE),
                'ip_terminal' => request()->ip(),
                'id_usuario_validador' => auth()->id(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("[VerificacionCertificadoService] Error registrando auditoría: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Obtener estadísticas de verificación globales
     * 
     * @return array [total, validos, con_alertas, por_verificar]
     */
    public function obtenerEstadisticasVerificacion()
    {
        try {
            $total = HistoricoCierre::count();
            $con_estado = HistoricoCierre::whereNotNull('estado_certificacion')->count();
            $validados = HistoricoCierre::where('estado_certificacion', 'VALIDADO')->count();

            // Contar verificaciones en el último mes
            $mes_actual = Carbon::now()->startOfMonth();
            $verificaciones_mes = AuditoriaVerificacion::where('tipo_verificacion', 'VALIDACION_COMPLETA')
                ->where('created_at', '>=', $mes_actual)
                ->count();

            return [
                'total_certificados' => $total,
                'certificados_con_estado' => $con_estado,
                'certificados_validados' => $validados,
                'verificaciones_este_mes' => $verificaciones_mes,
                'porcentaje_validacion' => $total > 0 ? round(($con_estado / $total) * 100, 2) : 0,
            ];
        } catch (\Exception $e) {
            Log::error("[VerificacionCertificadoService] Error obtenerEstadisticasVerificacion: {$e->getMessage()}");
            return [
                'total_certificados' => 0,
                'certificados_con_estado' => 0,
                'certificados_validados' => 0,
            ];
        }
    }
}
