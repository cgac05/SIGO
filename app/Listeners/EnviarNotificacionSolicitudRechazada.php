<?php

namespace App\Listeners;

use App\Events\SolicitudRechazada;

class EnviarNotificacionSolicitudRechazada
{
    public function handle(SolicitudRechazada $event)
    {
        // Las notificaciones por rechazo están deshabilitadas.
        return null;
    }
}
