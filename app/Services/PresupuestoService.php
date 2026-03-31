<?php

namespace App\Services;

use App\Models\Apoyo;
use App\Models\PresupuestoApoyo;
use App\Models\PresupuestoCategoria;
use App\Models\Solicitud;
use App\Models\Usuario;
use App\Models\MovimientoPresupuestario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * PresupuestoService
 *
 * Servicio centralizado para manejar toda la lógica de presupuestación.
 * Responsabilidades:
 * - Validar presupuesto disponible
 * - Reservar presupuesto cuando se crea solicitud
 * - Aprobar presupuesto cuando directivo autoriza
 * - Manejar movimientos presupuestarios
 * - Generar alertas de presupuesto bajo
 */
class PresupuestoService
{
    /**
     * Validar si hay presupuesto disponible para una solicitud
     *
     * @param int $id_apoyo ID del apoyo
     * @param float $monto Monto solicitado
     * @param int $ciclo_id ID del ciclo presupuestario (opcional)
     * @return array ['valido' => bool, 'mensaje' => string, 'presupuesto' => PresupuestoApoyo|null]
     */
    public function validarPresupuestoDisponible(int $id_apoyo, float $monto = 0, ?int $ciclo_id = null): array
    {
        $apoyo = Apoyo::find($id_apoyo);

        if (!$apoyo) {
            return [
                'valido' => false,
                'mensaje' => 'El apoyo seleccionado no existe.',
                'presupuesto' => null,
            ];
        }

        if (!$apoyo->id_categoria) {
            return [
                'valido' => false,
                'mensaje' => 'El apoyo no tiene categoría de presupuesto asignada.',
                'presupuesto' => null,
            ];
        }

        $categoria = PresupuestoCategoria::find($apoyo->id_categoria);

        if (!$categoria || !$categoria->activo) {
            return [
                'valido' => false,
                'mensaje' => 'La categoría de presupuesto del apoyo no está activa.',
                'presupuesto' => null,
            ];
        }

        // Obtener el presupuesto actual del apoyo
        $presupuesto = $apoyo->presupuestos()
            ->where('id_categoria', $apoyo->id_categoria)
            ->orderBy('created_at', 'desc')
            ->first();

        if (!$presupuesto) {
            return [
                'valido' => false,
                'mensaje' => 'No hay presupuesto reservado para este apoyo en la categoría.',
                'presupuesto' => null,
            ];
        }

        // Validar que haya presupuesto disponible
        $disponible = (float) $categoria->disponible;
        $monto_float = (float) $monto;

        if ($disponible < $monto_float) {
            return [
                'valido' => false,
                'mensaje' => sprintf(
                    'Presupuesto insuficiente. Disponible: %s, Solicitado: %s',
                    '$' . number_format($disponible, 2),
                    '$' . number_format($monto_float, 2)
                ),
                'presupuesto' => $presupuesto,
            ];
        }

        return [
            'valido' => true,
            'mensaje' => sprintf(
                'Presupuesto disponible: %s',
                '$' . number_format($disponible, 2)
            ),
            'presupuesto' => $presupuesto,
        ];
    }

    /**
     * Reservar presupuesto para una solicitud (cuando beneficiario crea solicitud)
     *
     * @param Solicitud $solicitud
     * @param float $monto
     * @param int|null $id_directivo
     * @return bool
     */
    public function reservarPresupuesto(Solicitud $solicitud, float $monto, ?int $id_directivo = null): bool
    {
        return DB::transaction(function () use ($solicitud, $monto, $id_directivo) {
            $apoyo = $solicitud->apoyo;

            if (!$apoyo || !$apoyo->id_categoria) {
                return false;
            }

            $categoria = PresupuestoCategoria::find($apoyo->id_categoria);

            if (!$categoria || !$categoria->isDisponibleFor($monto)) {
                return false;
            }

            // Crear registro en presupuesto_apoyos
            $presupuesto = PresupuestoApoyo::create([
                'id_apoyo' => $apoyo->id_apoyo,
                'id_categoria' => $categoria->id_categoria,
                'costo_estimado' => $monto,
                'estado' => 'RESERVADO',
                'fecha_reserva' => Carbon::now(),
                'observaciones' => "Solicitud {$solicitud->folio} - Beneficiario: {$solicitud->beneficiario?->nombre}",
            ]);

            // Decrementar presupuesto disponible en categoría
            $categoria->decrementarDisponible($monto);

            // Registrar movimiento presupuestario (que vincula la solicitud con presupuesto)
            MovimientoPresupuestario::create([
                'id_categoria' => $categoria->id_categoria,
                'id_presupuesto_apoyo' => $presupuesto->id_presupuesto_apoyo,
                'folio_solicitud' => $solicitud->folio,
                'tipo' => 'RESERVA_SOLICITUD',
                'monto' => $monto,
                'id_usuario' => auth()->user()->id_usuario ?? null,
                'descripcion' => "Reserva de presupuesto para solicitud {$solicitud->folio}",
                'fecha_cambio' => Carbon::now(),
                'estado' => 'CONFIRMADO',
            ]);

            return true;
        });
    }

    /**
     * Aprobar presupuesto para una solicitud (cuando directivo autoriza)
     *
     * @param Solicitud $solicitud
     * @param int $id_directivo
     * @return bool
     */
    public function aprobarPresupuesto(Solicitud $solicitud, int $id_directivo): bool
    {
        return DB::transaction(function () use ($solicitud, $id_directivo) {
            if (!$solicitud->presupuestoApoyo) {
                return false;
            }

            $presupuesto = $solicitud->presupuestoApoyo;

            // Transicionar de RESERVADO a APROBADO
            if (!$presupuesto->canBeApproved()) {
                return false;
            }

            $presupuesto->update([
                'estado' => 'APROBADO',
                'fecha_aprobacion' => Carbon::now(),
                'id_directivo_aprobador' => $id_directivo,
            ]);

            // Registrar movimiento presupuestario
            MovimientoPresupuestario::create([
                'id_categoria' => $presupuesto->id_categoria,
                'id_presupuesto_apoyo' => $presupuesto->id_presupuesto_apoyo,
                'folio_solicitud' => $solicitud->folio,
                'tipo' => 'ASIGNACION_DIRECTIVO',
                'monto' => $presupuesto->costo_estimado,
                'id_usuario' => $id_directivo,
                'descripcion' => "Aprobación de solicitud {$solicitud->folio} por directivo",
                'fecha_cambio' => Carbon::now(),
                'estado' => 'CONFIRMADO',
            ]);

            return true;
        });
    }

    /**
     * Rechazar solicitud y liberar presupuesto (cuando directivo rechaza)
     *
     * @param Solicitud $solicitud
     * @param int $id_directivo
     * @param string|null $razon
     * @return bool
     */
    public function rechazarPresupuesto(Solicitud $solicitud, int $id_directivo, ?string $razon = null): bool
    {
        return DB::transaction(function () use ($solicitud, $id_directivo, $razon) {
            if (!$solicitud->presupuestoApoyo) {
                return false;
            }

            $presupuesto = $solicitud->presupuestoApoyo;
            $categoria = $presupuesto->categoria;

            // Solo puede ser rechazado si está RESERVADO
            if ($presupuesto->estado !== 'RESERVADO') {
                return false;
            }

            // Liberar presupuesto
            $categoria->incrementarDisponible($presupuesto->costo_estimado);

            // Actualizar estado presupuesto a CANCELADO
            $presupuesto->update([
                'estado' => 'CANCELADO',
            ]);

            // Registrar movimiento presupuestario
            MovimientoPresupuestario::create([
                'id_categoria' => $categoria->id_categoria,
                'id_presupuesto_apoyo' => $presupuesto->id_presupuesto_apoyo,
                'folio_solicitud' => $solicitud->folio,
                'tipo' => 'RECHAZO_SOLICITUD',
                'monto' => $presupuesto->costo_estimado,
                'id_usuario' => $id_directivo,
                'descripcion' => "Rechazo de solicitud {$solicitud->folio}. Razón: {$razon}",
                'fecha_cambio' => Carbon::now(),
                'estado' => 'CONFIRMADO',
            ]);

            return true;
        });
    }

    /**
     * Obtener alertas de presupuesto para una categoría
     *
     * @param PresupuestoCategoria $categoria
     * @return array
     */
    public function obtenerAlertasCategoria(PresupuestoCategoria $categoria): array
    {
        $alertas = [];
        $porcentaje = $categoria->getPorcentajeUtilizacion();

        if ($porcentaje >= 100) {
            $alertas[] = [
                'tipo' => 'danger',
                'mensaje' => 'Presupuesto agotado. No hay fondos disponibles.',
                'icono' => '⚠️',
            ];
        } elseif ($porcentaje >= 85) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => sprintf('Presupuesto crítico: %.1f%% utilizado', $porcentaje),
                'icono' => '⚠️',
            ];
        } elseif ($porcentaje >= 70) {
            $alertas[] = [
                'tipo' => 'warning',
                'mensaje' => sprintf('Presupuesto alto: %.1f%% utilizado', $porcentaje),
                'icono' => 'ℹ️',
            ];
        }

        return $alertas;
    }

    /**
     * Obtener resumen presupuestario por apoyo
     *
     * @param Apoyo $apoyo
     * @return array
     */
    public function obtenerResumenApoyo(Apoyo $apoyo): array
    {
        $presupuesto = $apoyo->getPresupuestoActual();
        $categoria = $apoyo->categoria;

        return [
            'apoyo_id' => $apoyo->id_apoyo,
            'apoyo_nombre' => $apoyo->nombre_apoyo,
            'categoria_id' => $categoria?->id_categoria,
            'categoria_nombre' => $categoria?->nombre ?? 'Sin categoría',
            'presupuesto_total' => $categoria ? (float) $categoria->presupuesto_anual : 0,
            'presupuesto_disponible' => $categoria ? (float) $categoria->disponible : 0,
            'presupuesto_gastado' => $categoria ? ((float) $categoria->presupuesto_anual - (float) $categoria->disponible) : 0,
            'porcentaje_utilizacion' => $categoria ? $categoria->getPorcentajeUtilizacion() : 0,
            'tiene_presupuesto' => $categoria ? $categoria->disponible > 0 : false,
            'estado' => $presupuesto?->estado ?? 'SIN_ASIGNACION',
        ];
    }

    /**
     * Obtener estado presupuestario del apoyo con detalles
     *
     * @param Apoyo $apoyo
     * @return array
     */
    public function obtenerEstadoDetalladoApoyo(Apoyo $apoyo): array
    {
        $categoria = $apoyo->categoria;

        if (!$categoria) {
            return [
                'estado' => 'ERROR',
                'mensaje' => 'Apoyo sin categoría de presupuesto asignada',
                'disponible' => 0,
                'alertas' => [],
            ];
        }

        $alertas = $this->obtenerAlertasCategoria($categoria);

        return [
            'estado' => $categoria->disponible > 0 ? 'DISPONIBLE' : 'AGOTADO',
            'disponible' => (float) $categoria->disponible,
            'disponible_formato' => $categoria->getDisponibleFormato(),
            'total' => (float) $categoria->presupuesto_anual,
            'gastado' => (float) $categoria->presupuesto_anual - (float) $categoria->disponible,
            'porcentaje' => $categoria->getPorcentajeUtilizacion(),
            'alertas' => $alertas,
            'puede_solicitar' => !empty($alertas) == false && $categoria->disponible > 0,
        ];
    }
}
