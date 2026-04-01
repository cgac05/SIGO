<?php

namespace App\Services;

use App\Models\AlertaPresupuesto;
use App\Models\MovimientoPresupuestario;
use App\Models\PresupuestoApoyo;
use App\Models\PresupuestoCategoria;
use App\Models\Solicitud;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PresupuestaryControlService
 *
 * Servicio centralizado para gestión presupuestaria en dos niveles:
 * 1. Presupuesto por Categoría (Educación, Salud, etc) - Anual
 * 2. Presupuesto por Apoyo (Becas Universitarias, etc) - Sub-asignación
 *
 * Operaciones:
 * - RESERVA: Cuando se crea un apoyo (presupuesto "bloqueado" pero no gastado)
 * - ASIGNACIÓN: Cuando directivo autoriza una solicitud (presupuesto "gastado" - irreversible)
 * - LIBERACIÓN: Cuando se rechaza una solicitud (presupuesto vuelve a disponible)
 *
 * Compliance:
 * - LGPDP: Auditoría completa de movimientos
 * - LFTAIPG: Transparencia en flujo de presupuesto
 * - Validaciones: Double-check antes de transacciones críticas
 */
class PresupuestaryControlService
{
    /**
     * Validar si se puede crear un apoyo (RESERVA)
     *
     * @param int $idCategoria
     * @param decimal $costoEstimado Costo total: monto_maximo * cantidad
     * @param int|null $anoFiscal
     * @return array ['valido' => bool, 'mensaje' => string]
     */
    public function validarPresupuestoParaApoyo(int $idCategoria, $costoEstimado, ?int $anoFiscal = null): array
    {
        $ano = $anoFiscal ?? now()->year;

        $categoria = PresupuestoCategoria::wherePorAno($ano)
            ->find($idCategoria);

        if (!$categoria) {
            return [
                'valido' => false,
                'mensaje' => "Presupuesto no configurado para año {$ano}. Contacta a administración.",
            ];
        }

        if ($categoria->estado !== 'ABIERTO') {
            return [
                'valido' => false,
                'mensaje' => "Ciclo presupuestario {$ano} está cerrado. No se pueden crear nuevos apoyos.",
            ];
        }

        $disponible = $categoria->disponible ?? $categoria->presupuesto_inicial ?? 0;

        if ($disponible < $costoEstimado) {
            return [
                'valido' => false,
                'mensaje' => "❌ Presupuesto insuficiente en categoría '{$categoria->nombre_categoria}'.\n" .
                    "Disponible: \$" . number_format($disponible, 2) . "\n" .
                    "Se solicita: \$" . number_format($costoEstimado, 2),
            ];
        }

        return [
            'valido' => true,
            'mensaje' => "✅ Presupuesto OK - Se pueden crear apoyos",
        ];
    }

    /**
     * RESERVAR presupuesto al crear apoyo
     *
     * @param int $idApoyo
     * @param decimal $costoEstimado
     * @param int $idCategoria
     * @return PresupuestoApoyo
     * @throws Exception
     */
    public function reservarPresupuestoApoyo(int $idApoyo, $costoEstimado, int $idCategoria): PresupuestoApoyo
    {
        DB::beginTransaction();
        try {
            // Validar pre-requisitos
            $validacion = $this->validarPresupuestoParaApoyo($idCategoria, $costoEstimado);
            if (!$validacion['valido']) {
                throw new Exception($validacion['mensaje']);
            }

            $ano = now()->year;

            // Crear registro en presupuesto_apoyos
            $presupuestoApoyo = PresupuestoApoyo::create([
                'fk_id_apoyo' => $idApoyo,
                'fk_id_categoria' => $idCategoria,
                'ano_fiscal' => $ano,
                'presupuesto_total' => $costoEstimado,
                'reservado' => $costoEstimado,
                'aprobado' => 0,
                'disponible' => $costoEstimado,
            ]);

            // Restar del presupuesto categoría
            PresupuestoCategoria::find($idCategoria)
                ->decrement('disponible', $costoEstimado)
                ->increment('reservado', $costoEstimado);

            // Registrar movimiento (AUDITORÍA)
            MovimientoPresupuestario::create([
                'fk_id_apoyo' => $idApoyo,
                'fk_id_categoria' => $idCategoria,
                'tipo_movimiento' => 'RESERVA',
                'monto_movimiento' => $costoEstimado,
                'ano_fiscal' => $ano,
                'estado_movimiento' => 'CONFIRMADO',
                'observaciones' => "Presupuesto reservado para apoyo #$idApoyo",
            ]);

            DB::commit();

            Log::channel('presupuesto')->info('Presupuesto reservado', [
                'apoyo_id' => $idApoyo,
                'categoria_id' => $idCategoria,
                'monto' => $costoEstimado,
                'ano' => $ano,
            ]);

            return $presupuestoApoyo;

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error reservando presupuesto', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Validar si se puede AUTORIZAR una solicitud (pre-firma directivo)
     *
     * @param int $idSolicitud
     * @return array ['valido' => bool, 'mensaje' => string, 'datos' => array|null]
     */
    public function validarPresupuestoParaSolicitud(int $idSolicitud): array
    {
        try {
            $solicitud = Solicitud::with(['apoyo'])->findOrFail($idSolicitud);
            $ano = now()->year;

            if (!$solicitud->apoyo) {
                return [
                    'valido' => false,
                    'mensaje' => 'Solicitud sin apoyo asociado.',
                ];
            }

            // Obtener presupuestos
            $presupuestoApoyo = PresupuestoApoyo::where('fk_id_apoyo', $solicitud->apoyo->id)
                ->where('ano_fiscal', $ano)
                ->first();

            if (!$presupuestoApoyo) {
                return [
                    'valido' => false,
                    'mensaje' => 'No hay presupuesto configurado para este apoyo en ' . $ano,
                ];
            }

            $presupuestoCategoria = PresupuestoCategoria::find($presupuestoApoyo->fk_id_categoria);

            if (!$presupuestoCategoria || $presupuestoCategoria->estado !== 'ABIERTO') {
                return [
                    'valido' => false,
                    'mensaje' => 'El ciclo presupuestario está cerrado.',
                ];
            }

            $monto = $solicitud->monto_solicitado ?? 0;

            // Validación a dos niveles
            if ($presupuestoApoyo->disponible < $monto) {
                return [
                    'valido' => false,
                    'mensaje' => "❌ Presupuesto insuficiente en apoyo '{$solicitud->apoyo->nombre}'.\n" .
                        "Disponible: \$" . number_format($presupuestoApoyo->disponible, 2) . "\n" .
                        "Se requiere: \$" . number_format($monto, 2),
                ];
            }

            if ($presupuestoCategoria->disponible < $monto) {
                return [
                    'valido' => false,
                    'mensaje' => "❌ Presupuesto insuficiente en categoría '{$presupuestoCategoria->nombre_categoria}'.\n" .
                        "Disponible: \$" . number_format($presupuestoCategoria->disponible, 2) . "\n" .
                        "Se requiere: \$" . number_format($monto, 2),
                ];
            }

            return [
                'valido' => true,
                'mensaje' => '✅ Presupuesto OK - Puede autorizar',
                'datos' => [
                    'monto' => $monto,
                    'disponible_apoyo' => $presupuestoApoyo->disponible,
                    'disponible_categoria' => $presupuestoCategoria->disponible,
                ],
            ];

        } catch (Exception $e) {
            return [
                'valido' => false,
                'mensaje' => 'Error validando presupuesto: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * ASIGNAR presupuesto cuando Directivo autoriza (PUNTO DE NO RETORNO)
     * En este punto el presupuesto se "gasta" de forma irreversible
     *
     * @param int $idSolicitud
     * @param int $idDirectivo
     * @return array ['exitoso' => bool, 'mensaje' => string]
     * @throws Exception
     */
    public function asignarPresupuestoSolicitud(int $idSolicitud, int $idDirectivo): array
    {
        DB::beginTransaction();
        try {
            $ano = now()->year;
            $solicitud = Solicitud::with(['apoyo'])->findOrFail($idSolicitud);
            $monto = $solicitud->monto_solicitado ?? 0;

            // Validar NUEVAMENTE (por si otro directivo aprobó en paralelo)
            $validacion = $this->validarPresupuestoParaSolicitud($idSolicitud);
            if (!$validacion['valido']) {
                throw new Exception($validacion['mensaje']);
            }

            // Obtener presupuestos
            $presupuestoApoyo = PresupuestoApoyo::where('fk_id_apoyo', $solicitud->apoyo->id)
                ->where('ano_fiscal', $ano)
                ->lockForUpdate()
                ->first();

            $presupuestoCategoria = PresupuestoCategoria::lockForUpdate()
                ->find($presupuestoApoyo->fk_id_categoria);

            // TRANSACCIÓN CRÍTICA: Convertir presupuesto "reservado" → "aprobado"

            // 1. Modificar presupuesto_apoyos
            $presupuestoApoyo->update([
                'disponible' => $presupuestoApoyo->disponible - $monto,
                'aprobado' => $presupuestoApoyo->aprobado + $monto,
                'cantidad_beneficiarios_aprobada' => $presupuestoApoyo->cantidad_beneficiarios_aprobada + 1,
            ]);

            // 2. Modificar presupuesto_categorías
            $presupuestoCategoria->update([
                'disponible' => $presupuestoCategoria->disponible - $monto,
                'aprobado' => $presupuestoCategoria->aprobado + $monto,
            ]);

            // 3. Marcar solicitud como presupuesto confirmado
            $solicitud->update([
                'presupuesto_confirmado' => 1,
                'fecha_confirmacion_presupuesto' => now(),
                'directivo_autorizo' => $idDirectivo,
            ]);

            // 4. Registrar movimiento (AUDITORÍA - IRREVERSIBLE)
            MovimientoPresupuestario::create([
                'fk_id_solicitud' => $idSolicitud,
                'fk_id_apoyo' => $solicitud->apoyo->id,
                'fk_id_categoria' => $presupuestoCategoria->id_presupuesto,
                'tipo_movimiento' => 'ASIGNACION_DIRECTIVO',
                'monto_movimiento' => $monto,
                'ano_fiscal' => $ano,
                'directivo_id' => $idDirectivo,
                'estado_movimiento' => 'CONFIRMADO',
                'observaciones' => "Solicitud autorizada por directivo. Presupuesto GASTADO (irreversible). Folio: {$solicitud->folio_institucional}",
            ]);

            DB::commit();

            // Verificar si deben generarse alertas
            $this->verificarAlertasCategoria($presupuestoCategoria->id_presupuesto);

            Log::channel('presupuesto')->info('Presupuesto asignado', [
                'solicitud_id' => $idSolicitud,
                'directivo_id' => $idDirectivo,
                'monto' => $monto,
                'disponible_despues' => $presupuestoCategoria->disponible - $monto,
            ]);

            return [
                'exitoso' => true,
                'mensaje' => "✅ Presupuesto asignado - \$" . number_format($monto, 2) . " GASTADO (irreversible)",
            ];

        } catch (Exception $e) {
            DB::rollback();
            Log::error('Error asignando presupuesto', [
                'solicitud_id' => $idSolicitud,
                'error' => $e->getMessage(),
            ]);

            return [
                'exitoso' => false,
                'mensaje' => 'Error asignando presupuesto: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * LIBERAR presupuesto cuando se rechaza una solicitud
     *
     * @param int $idSolicitud
     * @param int $idDirectivo
     * @return array
     */
    public function liberarPresupuestoSolicitud(int $idSolicitud, int $idDirectivo): array
    {
        DB::beginTransaction();
        try {
            $ano = now()->year;
            $solicitud = Solicitud::with(['apoyo'])->findOrFail($idSolicitud);

            // Solo liberar si ya estava confirmado (se gastó)
            if (!$solicitud->presupuesto_confirmado) {
                return [
                    'exitoso' => true,
                    'mensaje' => 'Presupuesto no estava confirmado - Nada que liberar',
                ];
            }

            $monto = $solicitud->monto_solicitado ?? 0;

            // Obtener presupuestos
            $presupuestoApoyo = PresupuestoApoyo::where('fk_id_apoyo', $solicitud->apoyo->id)
                ->where('ano_fiscal', $ano)
                ->lockForUpdate()
                ->first();

            $presupuestoCategoria = PresupuestoCategoria::lockForUpdate()
                ->find($presupuestoApoyo->fk_id_categoria);

            // Revertir transacción
            $presupuestoApoyo->update([
                'disponible' => $presupuestoApoyo->disponible + $monto,
                'aprobado' => $presupuestoApoyo->aprobado - $monto,
                'cantidad_beneficiarios_aprobada' => max(0, $presupuestoApoyo->cantidad_beneficiarios_aprobada - 1),
            ]);

            $presupuestoCategoria->update([
                'disponible' => $presupuestoCategoria->disponible + $monto,
                'aprobado' => $presupuestoCategoria->aprobado - $monto,
            ]);

            // Marcar solicitud
            $solicitud->update([
                'presupuesto_confirmado' => 0,
                'estado' => 'RECHAZADA',
            ]);

            // Registrar movimiento
            MovimientoPresupuestario::create([
                'fk_id_solicitud' => $idSolicitud,
                'fk_id_apoyo' => $solicitud->apoyo->id,
                'fk_id_categoria' => $presupuestoCategoria->id_presupuesto,
                'tipo_movimiento' => 'LIBERACION',
                'monto_movimiento' => $monto,
                'ano_fiscal' => $ano,
                'directivo_id' => $idDirectivo,
                'estado_movimiento' => 'CONFIRMADO',
                'observaciones' => "Presupuesto liberado por rechazo. Folio: {$solicitud->folio_institucional}",
            ]);

            DB::commit();

            Log::channel('presupuesto')->info('Presupuesto liberado', [
                'solicitud_id' => $idSolicitud,
                'monto' => $monto,
            ]);

            return [
                'exitoso' => true,
                'mensaje' => "✅ Presupuesto liberado - \$" . number_format($monto, 2),
            ];

        } catch (Exception $e) {
            DB::rollback();
            return [
                'exitoso' => false,
                'mensaje' => 'Error liberando presupuesto: ' . $e->getMessage(),
            ];
        }
    }

    /**
     * Verificar si deben generarse alertas (presupuesto bajo)
     *
     * @param int $idCategoria
     * @return void
     */
    private function verificarAlertasCategoria(int $idCategoria): void
    {
        try {
            $categoria = PresupuestoCategoria::find($idCategoria);

            if (!$categoria) {
                return;
            }

            $porcentaje = ($categoria->disponible / max($categoria->presupuesto_inicial, 1)) * 100;
            $nivelActual = $categoria->getNivelAlerta();

            // Obtener última alerta registrada
            $ultimaAlerta = AlertaPresupuesto::where('fk_id_categoria', $idCategoria)
                ->latest('fecha_alerta')
                ->first();

            // Solo crear alerta si es diferente del nivel anterior
            if (!$ultimaAlerta || $ultimaAlerta->nivel_alerta !== $nivelActual) {
                $mensaje = match ($nivelActual) {
                    'CRITICA' => "⛔ CRÍTICA: Presupuesto AGOTADO en '{$categoria->nombre_categoria}'",
                    'ROJA' => "🔴 ROJA: Presupuesto crítico ({$porcentaje}%) en '{$categoria->nombre_categoria}'",
                    'AMARILLA' => "⚠️ AMARILLA: Presupuesto bajo ({$porcentaje}%) en '{$categoria->nombre_categoria}'",
                    default => "✅ NORMAL: Presupuesto recuperado en '{$categoria->nombre_categoria}'",
                };

                AlertaPresupuesto::create([
                    'fk_id_categoria' => $idCategoria,
                    'nivel_alerta' => $nivelActual,
                    'mensaje' => $mensaje,
                ]);

                Log::channel('presupuesto')->warning('Alerta generada', [
                    'categoria' => $categoria->nombre_categoria,
                    'nivel' => $nivelActual,
                    'disponible_pct' => $porcentaje,
                ]);
            }

        } catch (Exception $e) {
            Log::error('Error verificando alertas', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Obtener resumen de presupuesto por categoría
     *
     * @param int|null $anoFiscal
     * @return array
     */
    public function obtenerResumen(?int $anoFiscal = null): array
    {
        $ano = $anoFiscal ?? now()->year;

        $categorias = PresupuestoCategoria::wherePorAno($ano)
            ->get()
            ->map(function ($cat) {
                return [
                    'id' => $cat->id_presupuesto,
                    'nombre' => $cat->nombre_categoria,
                    'presupuesto_inicial' => (float) $cat->presupuesto_inicial,
                    'aprobado' => (float) $cat->aprobado,
                    'reservado' => (float) $cat->reservado,
                    'disponible' => (float) $cat->disponible,
                    'porcentaje_utilizado' => $cat->getPorcentajeUtilizacionAttribute(),
                    'nivel_alerta' => $cat->getNivelAlerta(),
                ];
            });

        return [
            'ano_fiscal' => $ano,
            'total_presupuesto' => $categorias->sum('presupuesto_inicial'),
            'total_aprobado' => $categorias->sum('aprobado'),
            'total_reservado' => $categorias->sum('reservado'),
            'total_disponible' => $categorias->sum('disponible'),
            'categorias' => $categorias,
        ];
    }
}
