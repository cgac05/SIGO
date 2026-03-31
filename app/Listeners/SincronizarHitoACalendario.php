<?php

namespace App\Listeners;

use App\Events\HitoCambiado;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\Log;

/**
 * SincronizarHitoACalendario Listener
 * 
 * Escucha cambios en hitos y sincroniza automáticamente con Google Calendar
 * para todos los directivos que tengan permisos de calendario activos.
 * 
 * Acciones:
 * - Crear evento en Google Calendar (cuando hito es creado)
 * - Actualizar evento en Google Calendar (cuando hito es modificado)
 * - Eliminar evento en Google Calendar (cuando hito es eliminado)
 */
class SincronizarHitoACalendario
{
    protected $googleCalendarService;

    /**
     * Create the event listener.
     */
    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Handle the event.
     */
    public function handle(HitoCambiado $event)
    {
        try {
            $hito = $event->hito;
            $tipo_cambio = $event->tipo_cambio;

            // Verificar si el apoyo tiene sincronización habilitada
            if (!$hito->apoyo || !$hito->apoyo->sincronizar_calendario) {
                Log::info("Sincronización deshabilitada para apoyo {$hito->fk_id_apoyo}");
                return;
            }

            // Realizar sincronización según el tipo de cambio
            switch ($tipo_cambio) {
                case 'creacion':
                    // ✅ CORREGIDA: Usar crearEventoHito() en lugar de crearEventosApoyo()
                    // Esto crea SOLO el evento para este hito, no itera sobre todos los hitos del apoyo
                    $resultado = $this->googleCalendarService->crearEventoHito($hito->id_hito);
                    if ($resultado['exito']) {
                        Log::info("Evento creado en Google Calendar para hito {$hito->id_hito}: {$resultado['event_id']}");
                    } else {
                        Log::error("Error al crear evento para hito {$hito->id_hito}: {$resultado['error']}");
                    }
                    break;

                case 'actualizacion':
                    // Cuando se actualiza un hito, se actualiza el evento en Google Calendar
                    $this->googleCalendarService->actualizarEventoHito($hito->id_hito);
                    Log::info("Evento actualizado en Google Calendar para hito {$hito->id_hito}");
                    break;

                case 'eliminacion':
                    // Cuando se elimina un hito, se elimina el evento de Google Calendar
                    $this->googleCalendarService->eliminarEventosApoyo($hito->fk_id_apoyo, $hito->id_hito);
                    Log::info("Evento eliminado de Google Calendar para hito {$hito->id_hito}");
                    break;

                default:
                    Log::warning("Tipo de cambio desconocido: {$tipo_cambio}");
            }

        } catch (\Exception $e) {
            Log::error("Error sincronizando hito con Google Calendar: " . $e->getMessage(), [
                'hito_id' => $event->hito->id_hito,
                'exception' => $e,
            ]);

            // No lanzar excepción para no afectar flujo de SIGO
            // La sincronización es asincrónica y no debe afectar operaciones críticas
        }
    }
}
