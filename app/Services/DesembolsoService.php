<?php

namespace App\Services;

use App\Models\Solicitud;
use App\Models\HistoricoCierre;
use App\Models\BDFinanzas;
use App\Models\MovimientoPresupuestario;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class DesembolsoService
{
    /**
     * Registrar desembolso de pago a beneficiario
     * 
     * Flujo:
     * 1. Validar que la solicitud existe y existe presupuesto
     * 2. Registrar en Historico_Cierre (payment record)
     * 3. Actualizar monto_ejercido en BD_Finanzas
     * 4. Registrar movimiento en movimientos_presupuestarios (audit)
     * 5. Actualizar Solicitud con monto_entregado
     * 
     * @param string $folio Folio de solicitud
     * @param decimal $monto Monto a desembolsar
     * @param int $usuario_cierre ID del usuario que autoriza pago
     * @param string|null $ruta_pdf Ruta del PDF de comprobante
     * @param string|null $descripcion Descripción adicional
     * 
     * @return array ['exito' => bool, 'razon' => string, 'id_historico' => int|null]
     */
    public function registrarDesembolso(
        string $folio,
        float $monto,
        int $usuario_cierre,
        ?string $ruta_pdf = null,
        ?string $descripcion = null
    ): array {
        try {
            DB::beginTransaction();

            // 1. Validar solicitud existe y obtener presupuesto asignado
            $solicitud = Solicitud::where('folio', $folio)->first();
            if (!$solicitud) {
                return ['exito' => false, 'razon' => "Solicitud con folio {$folio} no existe"];
            }

            if ($solicitud->presupuesto_confirmado !== true) {
                return ['exito' => false, 'razon' => "Presupuesto no confirmado para solicitud {$folio}"];
            }

            $presupuesto_disponible = $solicitud->presupuesto_asignado ?? 0;
            if ($monto > $presupuesto_disponible) {
                return [
                    'exito' => false,
                    'razon' => "Monto {$monto} excede presupuesto disponible {$presupuesto_disponible}"
                ];
            }

            // 2. Registrar en Historico_Cierre (payment record)
            $snapshot_antes = [
                'presupuesto_asignado' => $solicitud->presupuesto_asignado,
                'monto_entregado_anterior' => $solicitud->monto_entregado,
                'estado_anterior' => $solicitud->fk_id_estado,
            ];

            $historico = HistoricoCierre::create([
                'fk_folio' => $folio,
                'fk_id_usuario' => $usuario_cierre,
                'monto_entregado' => $monto,
                'fecha_entrega' => now(),
                'ruta_pdf_final' => $ruta_pdf,
                'descripcion' => $descripcion ?? "Desembolso registrado el " . now()->format('d/m/Y H:i'),
                'snapshot_json' => json_encode($snapshot_antes),
                'estado_pago' => 'COMPLETADO',
                'ip_terminal' => request()->ip(),
            ]);

            // 3. Actualizar monto_ejercido en BD_Finanzas (budget consumption)
            $presupuesto = BDFinanzas::where('fk_id_apoyo', $solicitud->fk_id_apoyo)->first();
            if ($presupuesto) {
                $presupuesto->monto_ejercido = ($presupuesto->monto_ejercido ?? 0) + $monto;
                $presupuesto->save();
            }

            // 4. Registrar movimiento presupuestario (audit trail)
            MovimientoPresupuestario::create([
                'id_categoria' => $solicitud->fk_id_categoria ?? null,
                'fk_id_apoyo' => $solicitud->fk_id_apoyo,
                'tipo_movimiento' => 'DESEMBOLSO',
                'monto' => $monto,
                'descripcion' => "Desembolso folio {$folio}: {$descripcion}",
                'creado_por' => $usuario_cierre,
                'fecha_movimiento' => now(),
                'referencia' => 'Historico_Cierre:' . $historico->id_historico,
            ]);

            // 5. Actualizar Solicitud con monto_entregado y fecha
            $solicitud->update([
                'monto_entregado' => ($solicitud->monto_entregado ?? 0) + $monto,
                'fecha_entrega_recurso' => now(),
                'fk_id_estado' => 5, // Estado "Pagado"
            ]);

            DB::commit();

            Log::info("Desembolso registrado", [
                'folio' => $folio,
                'monto' => $monto,
                'usuario_cierre' => $usuario_cierre,
                'id_historico' => $historico->id_historico,
            ]);

            return [
                'exito' => true,
                'razon' => "Desembolso de {$monto} registrado exitosamente",
                'id_historico' => $historico->id_historico,
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error registrando desembolso", [
                'folio' => $folio,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return ['exito' => false, 'razon' => "Error del sistema: " . $e->getMessage()];
        }
    }

    /**
     * Validar disponibilidad de presupuesto para desembolso
     * 
     * @param string $folio
     * @param float $monto Monto a validar
     * 
     * @return array ['disponible' => bool, 'razon' => string, 'presupuesto_restante' => float]
     */
    public function validarPresupuestoDisponible(string $folio, float $monto): array
    {
        try {
            $solicitud = Solicitud::where('folio', $folio)->first();
            if (!$solicitud) {
                return [
                    'disponible' => false,
                    'razon' => "Solicitud no encontrada",
                    'presupuesto_restante' => 0,
                ];
            }

            $presupuesto_asignado = $solicitud->presupuesto_asignado ?? 0;
            $monto_ya_entregado = $solicitud->monto_entregado ?? 0;
            $presupuesto_restante = $presupuesto_asignado - $monto_ya_entregado;

            if (!$solicitud->presupuesto_confirmado) {
                return [
                    'disponible' => false,
                    'razon' => "Presupuesto no confirmado",
                    'presupuesto_restante' => $presupuesto_restante,
                ];
            }

            if ($monto > $presupuesto_restante) {
                return [
                    'disponible' => false,
                    'razon' => "Monto supera presupuesto disponible ({$presupuesto_restante})",
                    'presupuesto_restante' => $presupuesto_restante,
                ];
            }

            // Verificar presupuesto en BD_Finanzas por apoyo
            $presupuesto_apoyo = BDFinanzas::where('fk_id_apoyo', $solicitud->fk_id_apoyo)->first();
            if ($presupuesto_apoyo) {
                $ejercido = $presupuesto_apoyo->monto_ejercido ?? 0;
                $disponible_apoyo = $presupuesto_apoyo->monto_asignado - $ejercido;

                if ($monto > $disponible_apoyo) {
                    return [
                        'disponible' => false,
                        'razon' => "Monto supera presupuesto disponible del apoyo ({$disponible_apoyo})",
                        'presupuesto_restante' => $disponible_apoyo,
                    ];
                }
            }

            return [
                'disponible' => true,
                'razon' => "Presupuesto disponible",
                'presupuesto_restante' => $presupuesto_restante,
            ];

        } catch (\Exception $e) {
            Log::error("Error validando presupuesto", ['folio' => $folio, 'error' => $e->getMessage()]);
            return [
                'disponible' => false,
                'razon' => "Error del sistema: " . $e->getMessage(),
                'presupuesto_restante' => 0,
            ];
        }
    }

    /**
     * Obtener historial de desembolsos para una solicitud
     * 
     * @param string $folio
     * 
     * @return array Desembolsos registrados
     */
    public function obtenerHistorialDesembolsos(string $folio): array
    {
        try {
            $desembolsos = HistoricoCierre::where('fk_folio', $folio)
                ->with(['usuario' => function ($query) {
                    $query->join('Personal', 'Personal.fk_id_usuario', '=', 'usuarios.id_usuario')
                        ->select(
                            'usuarios.id_usuario',
                            DB::raw("CONCAT(Personal.nombre, ' ', Personal.apellido_paterno) as nombre_completo")
                        );
                }])
                ->orderBy('fecha_entrega', 'desc')
                ->get()
                ->map(function ($pago) {
                    return [
                        'id' => $pago->id_historico,
                        'monto' => $pago->monto_entregado,
                        'fecha' => $pago->fecha_entrega->format('d/m/Y H:i'),
                        'usuario' => $pago->usuario->nombre_completo ?? 'Usuario no identificado',
                        'pdf' => $pago->ruta_pdf_final,
                        'descripcion' => $pago->descripcion,
                        'snapshot' => $pago->snapshot_json ? json_decode($pago->snapshot_json, true) : null,
                    ];
                })
                ->toArray();

            return $desembolsos;

        } catch (\Exception $e) {
            Log::error("Error obteniendo historial desembolsos", [
                'folio' => $folio,
                'error' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Obtener resumen de ejecución presupuestaria por apoyo
     * 
     * @param int $fk_id_apoyo
     * 
     * @return array ['monto_asignado', 'monto_ejercido', 'porcentaje_ejecucion', 'disponible']
     */
    public function obtenerEjecucionPresupuestaria(int $fk_id_apoyo): array
    {
        try {
            $presupuesto = BDFinanzas::where('fk_id_apoyo', $fk_id_apoyo)->first();

            if (!$presupuesto) {
                return [
                    'monto_asignado' => 0,
                    'monto_ejercido' => 0,
                    'porcentaje_ejecucion' => 0,
                    'disponible' => 0,
                ];
            }

            $asignado = floatval($presupuesto->monto_asignado ?? 0);
            $ejercido = floatval($presupuesto->monto_ejercido ?? 0);
            $disponible = $asignado - $ejercido;
            $porcentaje = ($asignado > 0) ? round(($ejercido / $asignado) * 100, 2) : 0;

            return [
                'monto_asignado' => $asignado,
                'monto_ejercido' => $ejercido,
                'porcentaje_ejecucion' => $porcentaje,
                'disponible' => max(0, $disponible),
            ];

        } catch (\Exception $e) {
            Log::error("Error obteniendo ejecución presupuestaria", [
                'fk_id_apoyo' => $fk_id_apoyo,
                'error' => $e->getMessage(),
            ]);
            return ['monto_asignado' => 0, 'monto_ejercido' => 0, 'porcentaje_ejecucion' => 0, 'disponible' => 0];
        }
    }
}
