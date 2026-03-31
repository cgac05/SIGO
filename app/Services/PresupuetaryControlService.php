<?php

namespace App\Services;

use App\Models\CicloPresupuestario;
use App\Models\MovimientoPresupuestario;
use App\Models\PresupuestoApoyo;
use App\Models\PresupuestoCategoria;
use App\Models\Solicitud;
use App\Models\Usuario;
use Exception;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PresupuetaryControlService
 * 
 * Centraliza toda la lógica de control presupuestario para SIGO.
 * Maneja la asignación en 2 niveles (Categoría → Apoyo),
 * validaciones, transacciones atómicas, y auditoría completa.
 * 
 * IMPORTANTE: Todas las operaciones son AUDITORADAS en movimientos_presupuestarios
 */
class PresupuetaryControlService
{
    /**
     * ===================================================================
     * NIVEL 1: CONTROL DE CICLOS PRESUPUESTARIOS (AÑO FISCAL)
     * ===================================================================
     */

    /**
     * Crear un nuevo ciclo presupuestario (año fiscal)
     * 
     * @param int $año_fiscal
     * @param float $presupuesto_total
     * @param array $categorias_config Array de [nombre => presupuesto]
     * @return CicloPresupuestario
     */
    public function crearCicloPresupuestario($año_fiscal, $presupuesto_total, $categorias_config = []): CicloPresupuestario
    {
        // Verificar que el año no existe
        if (CicloPresupuestario::where('año_fiscal', $año_fiscal)->exists()) {
            throw new Exception("Ciclo presupuestario para {$año_fiscal} ya existe");
        }

        return DB::transaction(function () use ($año_fiscal, $presupuesto_total, $categorias_config) {
            $ciclo = CicloPresupuestario::create([
                'año_fiscal' => $año_fiscal,
                'estado' => 'ABIERTO',
                'presupuesto_total' => $presupuesto_total,
                'fecha_apertura' => now(),
                'fecha_cierre_programado' => now()->copy()->endOfYear(),
            ]);

            Log::info("Ciclo presupuestario {$año_fiscal} creado", ['ciclo_id' => $ciclo->id_ciclo]);

            // Crear categorías con su presupuesto asignado
            if (!empty($categorias_config)) {
                $this->crearCategoriasParaCiclo($ciclo, $categorias_config);
            }

            return $ciclo;
        });
    }

    /**
     * Cerrar un ciclo presupuestario (fin de año fiscal)
     * PRECONDICIÓN: No debe haber presupuestos RESERVADOS sin usar
     */
    public function cerrarCicloPresupuestario($id_ciclo): bool
    {
        return DB::transaction(function () use ($id_ciclo) {
            $ciclo = CicloPresupuestario::findOrFail($id_ciclo);

            if (!$ciclo->isAbierto()) {
                throw new Exception("Ciclo ya está cerrado");
            }

            // Verificar que no hay apoyos en estado RESERVADO
            $reservados = PresupuestoApoyo::where('id_ciclo', $id_ciclo)
                ->where('estado', 'RESERVADO')
                ->count();

            if ($reservados > 0) {
                throw new Exception("No se puede cerrar ciclo con {$reservados} apoyos en estado RESERVADO");
            }

            // Verificar que toda presupuesto debe estar asignado (no hay disponible)
            // Nota: Es posible devolver dinero no utilizado
            $total_disponible = PresupuestoCategoria::where('id_ciclo', $id_ciclo)
                ->sum('disponible');

            Log::warning("Cierre de ciclo {$ciclo->año_fiscal}: dinero disponible sin asignar: {$total_disponible}");

            return $ciclo->cerrar();
        });
    }

    /**
     * ===================================================================
     * NIVEL 2: CONTROL DE CATEGORÍAS
     * ===================================================================
     */

    /**
     * Crear categorías para un ciclo presupuestario
     */
    public function crearCategoriasParaCiclo(CicloPresupuestario $ciclo, array $config): Collection
    {
        return DB::transaction(function () use ($ciclo, $config) {
            $categorias = collect();

            foreach ($config as $nombre => $presupuesto) {
                $categoria = PresupuestoCategoria::create([
                    'nombre' => $nombre,
                    'id_ciclo' => $ciclo->id_ciclo,
                    'presupuesto_anual' => $presupuesto,
                    'disponible' => $presupuesto,
                    'activo' => true,
                ]);

                $categorias->push($categoria);
            }

            Log::info("Categorías creadas para ciclo {$ciclo->año_fiscal}", ['count' => $categorias->count()]);

            return $categorias;
        });
    }

    /**
     * Verificar disponibilidad en categoría
     */
    public function verificarDisponibilidadCategoria($id_categoria, $monto): bool
    {
        $categoria = PresupuestoCategoria::findOrFail($id_categoria);
        return $categoria->isDisponibleFor($monto);
    }

    /**
     * ===================================================================
     * NIVEL 3: RESERVACIÓN DE PRESUPUESTO (Admin crea Apoyo)
     * ===================================================================
     */

    /**
     * Reservar presupuesto cuando Admin CREA un apoyo
     * 
     * ESTADO: RESERVADO (aún no gastado, solo comprometido)
     * 
     * @param int $id_apoyo
     * @param int $id_categoria
     * @param float $costo_estimado
     * @param int $id_usuario_creador (Admin)
     * @return PresupuestoApoyo
     */
    public function reservarPresupuestoApoyo(
        $id_apoyo,
        $id_categoria,
        $costo_estimado,
        $id_usuario_creador
    ): PresupuestoApoyo {
        return DB::transaction(function () use ($id_apoyo, $id_categoria, $costo_estimado, $id_usuario_creador) {
            // Validación 1: Categoría existe y está activa
            $categoria = PresupuestoCategoria::findOrFail($id_categoria);
            if (!$categoria->activo) {
                throw new Exception("Categoría '{$categoria->nombre}' está inactiva");
            }

            // Validación 2: Hay presupuesto disponible en la categoría
            if (!$categoria->isDisponibleFor($costo_estimado)) {
                throw new Exception(
                    "Presupuesto insuficiente en categoría '{$categoria->nombre}'. " .
                    "Disponible: {$categoria->getDisponibleFormato()}, Requerido: \${$costo_estimado}"
                );
            }

            // Validación 3: No existe presupuesto anterior para este apoyo
            if (PresupuestoApoyo::where('id_apoyo', $id_apoyo)->exists()) {
                throw new Exception("Apoyo {$id_apoyo} ya tiene presupuesto asignado");
            }

            // Crear PresupuestoApoyo en estado RESERVADO
            $presupuesto_apoyo = PresupuestoApoyo::create([
                'id_apoyo' => $id_apoyo,
                'id_categoria' => $id_categoria,
                'costo_estimado' => $costo_estimado,
                'estado' => 'RESERVADO',
                'fecha_reserva' => now(),
            ]);

            // OPERACIÓN CRÍTICA: Decrementar disponible en categoría
            $categoria->decrementarDisponible($costo_estimado);

            // Registrar movimiento en auditoría
            $this->registrarMovimiento(
                presupuesto_apoyo: $presupuesto_apoyo,
                tipo_movimiento: MovimientoPresupuestario::TIPO_RESERVACION,
                monto: $costo_estimado,
                id_usuario_responsable: $id_usuario_creador,
                notas: "Reservación de presupuesto para apoyo {$id_apoyo}",
                ip_origen: request()->ip(),
                user_agent: request()->header('User-Agent')
            );

            Log::info("Presupuesto RESERVADO para apoyo", [
                'id_apoyo' => $id_apoyo,
                'id_categoria' => $id_categoria,
                'monto' => $costo_estimado,
                'disponible_restante' => $categoria->disponible,
            ]);

            return $presupuesto_apoyo;
        });
    }

    /**
     * ===================================================================
     * NIVEL 4: ASIGNACIÓN POR DIRECTIVO (Autoriza Solicitud)
     * ===================================================================
     */

    /**
     * Asignar presupuesto cuando DIRECTIVO AUTORIZA una solicitud
     * 
     * ESTADO: RESERVADO → APROBADO (IRREVERSIBLE - dinero gastado)
     * 
     * Este es el punto crítico donde el dinero se considera GASTADO.
     * Una vez aprobado por directivo, NO se puede revertir.
     * 
     * @param int $id_solicitud
     * @param int $id_directivo_aprobador
     * @return PresupuestoApoyo
     */
    public function asignarPresupuestoSolicitud($id_solicitud, $id_directivo_aprobador): PresupuestoApoyo
    {
        return DB::transaction(function () use ($id_solicitud, $id_directivo_aprobador) {
            $solicitud = Solicitud::findOrFail($id_solicitud);

            // Validación 1: Encontrar el PresupuestoApoyo para este apoyo
            $presupuesto_apoyo = PresupuestoApoyo::where('id_apoyo', $solicitud->fk_id_apoyo)
                ->firstOrFail();

            // Validación 2: Debe estar en estado RESERVADO
            if (!$presupuesto_apoyo->isReservado()) {
                throw new Exception(
                    "PresupuestoApoyo está en estado {$presupuesto_apoyo->estado}, " .
                    "debe ser RESERVADO para ser aprobado"
                );
            }

            // Validación 3: Verificar disponibilidad en AMBOS NIVELES (2-Level Check)
            $categoria = $presupuesto_apoyo->categoria;
            if (!$this->validar2Niveles($presupuesto_apoyo, $solicitud->monto)) {
                throw new Exception(
                    "Presupuesto insuficiente al momento de asignación. " .
                    "Categoría: {$categoria->nombre}, " .
                    "Disponible: {$categoria->getDisponibleFormato()}, " .
                    "Requerido: \${$solicitud->monto}"
                );
            }

            // Validación 4: Usuario directivo existe y tiene rol 2
            $directivo = Usuario::findOrFail($id_directivo);
            if ($directivo->rol !== 2) {
                throw new Exception("Usuario {$directivo->nombre} no tiene permisos de Directivo");
            }

            // OPERACIÓN CRÍTICA: Transicionar a APROBADO (IRREVERSIBLE)
            $presupuesto_apoyo->approve($id_directivo_aprobador);

            // Registrar movimiento crítico en auditoría
            $this->registrarMovimiento(
                presupuesto_apoyo: $presupuesto_apoyo,
                id_solicitud: $id_solicitud,
                tipo_movimiento: MovimientoPresupuestario::TIPO_ASIGNACION_DIRECTIVO,
                monto: $solicitud->monto,
                id_usuario_responsable: $id_directivo_aprobador,
                notas: "ASIGNACIÓN DIRECTIVO - IRREVERSIBLE. " .
                       "Solicitud {$solicitud->folio}, Beneficiario {$solicitud->fk_curp}, " .
                       "Monto: \${$solicitud->monto}",
                ip_origen: request()->ip(),
                user_agent: request()->header('User-Agent')
            );

            // Verificar si está próximo a límite (70%, 85%, 100%)
            $this->verificarAlertasPresupuesto($categoria);

            Log::warning("ASIGNACIÓN DIRECTIVO - PRESUPUESTO APROBADO (IRREVERSIBLE)", [
                'id_solicitud' => $id_solicitud,
                'id_presupuesto_apoyo' => $presupuesto_apoyo->id_presupuesto_apoyo,
                'id_directivo' => $id_directivo_aprobador,
                'monto' => $solicitud->monto,
                'categoria_disponible' => $categoria->disponible,
            ]);

            return $presupuesto_apoyo;
        });
    }

    /**
     * Validación de 2 Niveles: Categoría + Apoyo
     */
    protected function validar2Niveles(PresupuestoApoyo $presupuesto_apoyo, $monto): bool
    {
        // Nivel 1: Verificar disponibilidad en categoría
        $categoria = $presupuesto_apoyo->categoria;
        if (!$categoria || !$categoria->isDisponibleFor($monto)) {
            return false;
        }

        // Nivel 2: Verificar que PresupuestoApoyo es suficiente
        if ((float) $presupuesto_apoyo->costo_estimado < (float) $monto) {
            return false;
        }

        return true;
    }

    /**
     * ===================================================================
     * NIVEL 5: AUDITORÍA Y ALERTAS
     * ===================================================================
     */

    /**
     * Registrar movimiento en auditoría
     */
    protected function registrarMovimiento(
        PresupuestoApoyo $presupuesto_apoyo,
        $tipo_movimiento,
        $monto,
        $id_usuario_responsable,
        $notas = null,
        $ip_origen = null,
        $user_agent = null,
        $id_solicitud = null
    ): MovimientoPresupuestario {
        return MovimientoPresupuestario::create([
            'id_presupuesto_apoyo' => $presupuesto_apoyo->id_presupuesto_apoyo,
            'id_solicitud' => $id_solicitud,
            'tipo_movimiento' => $tipo_movimiento,
            'monto' => $monto,
            'id_usuario_responsable' => $id_usuario_responsable,
            'notas' => $notas,
            'ip_origen' => $ip_origen ?? request()->ip(),
            'user_agent' => $user_agent ?? request()->header('User-Agent'),
        ]);
    }

    /**
     * Verificar si categoría está en niveles de alerta
     */
    protected function verificarAlertasPresupuesto(PresupuestoCategoria $categoria): void
    {
        $utilizado = $categoria->getPorcentajeUtilizacion();

        if ($utilizado >= 85) {
            Log::alert("⚠️ ALERTA CRÍTICA: Categoría '{$categoria->nombre}' en {$utilizado}% de utilización");
            // TODO: Enviar notificación a admin
        } elseif ($utilizado >= 70) {
            Log::warning("⚠️ ALERTA: Categoría '{$categoria->nombre}' en {$utilizado}% de utilización");
            // TODO: Enviar notificación a admin
        }
    }

    /**
     * ===================================================================
     * NIVEL 6: REPORTES Y CONSULTAS
     * ===================================================================
     */

    /**
     * Obtener reporte de presupuesto por categoría
     */
    public function reportePresupuestoPorCategoria($id_ciclo): array
    {
        $categorias = PresupuestoCategoria::where('id_ciclo', $id_ciclo)
            ->with('apoyos.movimientos')
            ->get();

        return $categorias->map(function ($categoria) {
            return [
                'nombre' => $categoria->nombre,
                'presupuesto_total' => $categoria->presupuesto_anual,
                'disponible' => $categoria->disponible,
                'gastado' => (float) $categoria->presupuesto_anual - (float) $categoria->disponible,
                'porcentaje_utilizado' => $categoria->getPorcentajeUtilizacion(),
                'estado_visual' => $this->getEstadoVisual($categoria->getPorcentajeUtilizacion()),
            ];
        })->toArray();
    }

    /**
     * Historial de movimientos presupuestarios
     */
    public function historialMovimientos($id_presupuesto_apoyo, $limit = 50)
    {
        return MovimientoPresupuestario::where('id_presupuesto_apoyo', $id_presupuesto_apoyo)
            ->with('usuarioResponsable')
            ->orderBy('fecha_movimiento', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * ===================================================================
     * UTILIDADES
     * ===================================================================
     */

    protected function getEstadoVisual($porcentaje): string
    {
        if ($porcentaje >= 100) {
            return 'AGOTADO';
        }
        if ($porcentaje >= 85) {
            return 'CRÍTICO';
        }
        if ($porcentaje >= 70) {
            return 'ALTO';
        }
        return 'NORMAL';
    }
}
