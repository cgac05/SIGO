<?php

namespace App\Services;

use App\Models\Apoyo;
use App\Models\CicloPresupuestario;
use App\Models\PresupuestoApoyo;
use App\Models\Solicitud;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * PresupuetaryIntegrationService
 * 
 * Servicio de integración que conecta los controllers existentes con el sistema 
 * de presupuestación. Proporciona métodos seguros que manejan errores gracefully
 */
class PresupuetaryIntegrationService
{
    public function __construct(private PresupuetaryControlService $presupuetoService)
    {
    }

    /**
     * Integración en ApoyoController::store
     * Llamar DESPUÉS de crear el apoyo: $this->reservarPresupuestoApoyo($apoyo)
     */
    public function reservarPresupuestoApoyo(Apoyo $apoyo, ?int $id_categoria = null): bool
    {
        try {
            // Si no se proporciona categoría, intentar obtener del request o usar default
            if (empty($id_categoria)) {
                $id_categoria = request()->input('id_categoria');
            }

            if (empty($id_categoria)) {
                Log::info("PresupuetaryIntegration: No se especificó categoría para apoyo {$apoyo->id_apoyo}. Skipping.");
                return true; // No es error crítico, simplemente no se reserva
            }

            // Verificar que la categoría existe
            $categoria = DB::table('presupuesto_categorias')
                ->where('id_categoria', $id_categoria)
                ->first();

            if (!$categoria) {
                Log::warning("PresupuetaryIntegration: Categoría {$id_categoria} no existe. Skipping.");
                return true;
            }

            // Intentar reservar
            $costo_estimado = $apoyo->monto_maximo ?? 0;

            $this->presupuetoService->reservarPresupuestoApoyo(
                id_apoyo: (int) $apoyo->id_apoyo,
                id_categoria: (int) $id_categoria,
                costo_estimado: (float) $costo_estimado,
                id_usuario_creador: (int) auth()->user()->id_usuario
            );

            Log::info("PresupuetaryIntegration: Presupuesto RESERVADO para apoyo {$apoyo->id_apoyo}", [
                'categoria' => $id_categoria,
                'monto' => $costo_estimado,
            ]);

            return true;
        } catch (Exception $e) {
            // No queremos romper el flujo de creación de apoyos
            Log::error("PresupuetaryIntegration ERROR en reservarPresupuestoApoyo: " . $e->getMessage(), [
                'id_apoyo' => $apoyo->id_apoyo ?? 'unknown',
                'exception' => $e,
            ]);

            return false;
        }
    }

    /**
     * Integración en SolicitudProcesoController::firmaDirectiva
     * Llamar DESPUÉS de actualizar Solicitudes con estado 3 (AUTORIZADO)
     */
    public function asignarPresupuestoAlAutorizar(int $folio, int $id_directivo_aprobador): bool
    {
        try {
            $solicitud = Solicitud::where('folio', $folio)->first();

            if (!$solicitud) {
                Log::warning("PresupuetaryIntegration: Solicitud {$folio} no encontrada");
                return false;
            }

            // Llamar al servicio para asignar presupuesto
            $this->presupuetoService->asignarPresupuestoSolicitud(
                id_solicitud: (int) $folio,
                id_directivo_aprobador: (int) $id_directivo_aprobador
            );

            Log::info("PresupuetaryIntegration: Presupuesto ASIGNADO para solicitud {$folio}", [
                'directivo' => $id_directivo_aprobador,
                'monto' => $solicitud->monto ?? 'unknown',
            ]);

            return true;
        } catch (Exception $e) {
            // Registrar error pero no romper el flujo de autorización
            Log::error("PresupuetaryIntegration ERROR en asignarPresupuestoAlAutorizar: " . $e->getMessage(), [
                'folio' => $folio,
                'id_directivo' => $id_directivo_aprobador,
                'exception' => $e,
            ]);

            // IMPORTANTE: Devolver false para que se maneje en el controller si es crítico
            return false;
        }
    }

    /**
     * Verificar si un apoyo tiene presupuesto asignado
     */
    public function apoyoHasPresupuesto(int $id_apoyo): bool
    {
        return PresupuestoApoyo::where('id_apoyo', $id_apoyo)->exists();
    }

    /**
     * Obtener estado presupuestario de un apoyo
     */
    public function getEstadoPresupuestoApoyo(int $id_apoyo): ?array
    {
        $presupuesto = PresupuestoApoyo::where('id_apoyo', $id_apoyo)
            ->with('categoria')
            ->first();

        if (!$presupuesto) {
            return null;
        }

        return [
            'estado' => $presupuesto->estado,
            'costo_estimado' => $presupuesto->costo_estimado,
            'categoria' => $presupuesto->categoria->nombre ?? null,
            'disponible_en_categoria' => $presupuesto->categoria->disponible ?? null,
            'fecha_reserva' => $presupuesto->fecha_reserva?->toDateTimeString(),
            'fecha_aprobacion' => $presupuesto->fecha_aprobacion?->toDateTimeString(),
        ];
    }

    /**
     * Verificar disponibilidad ANTES de autorizar una solicitud
     */
    public function verificarPresupuestoDisponibleAntesDeAutorizar(int $folio): bool
    {
        try {
            $solicitud = Solicitud::where('folio', $folio)->first();
            if (!$solicitud) {
                return false;
            }

            $presupuesto_apoyo = PresupuestoApoyo::where('id_apoyo', $solicitud->fk_id_apoyo)->first();
            if (!$presupuesto_apoyo) {
                Log::warning("PresupuetaryIntegration: No hay presupuesto para apoyo {$solicitud->fk_id_apoyo}");
                return false;
            }

            if (!$presupuesto_apoyo->canBeApproved()) {
                Log::warning("PresupuetaryIntegration: PresupuestoApoyo no puede ser aprobado", [
                    'id_presupuesto' => $presupuesto_apoyo->id_presupuesto_apoyo,
                    'estado' => $presupuesto_apoyo->estado,
                ]);
                return false;
            }

            return true;
        } catch (Exception $e) {
            Log::error("PresupuetaryIntegration ERROR en verificarPresupuestoDisponible: " . $e->getMessage());
            return false;
        }
    }
}
