<?php

namespace App\Listeners;

use App\Events\SolicitudRechazada;
use App\Notifications\SolicitudRechazadaNotification;

class EnviarNotificacionRechazo
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(SolicitudRechazada $event): void
    {
        // Obtener el beneficiario
        $beneficiario = $event->solicitud->beneficiario;
        
        if ($beneficiario) {
            // Enviar notificación al beneficiario
            $beneficiario->notify(new SolicitudRechazadaNotification(
                $event->solicitud,
                $event->motivo
            ));
        }
    }
}
