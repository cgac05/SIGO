<?php

namespace App\Listeners;

use App\Events\DocumentoRechazado;

class EnviarNotificacionDocumentoRechazado
{
    public function handle(DocumentoRechazado $event)
    {
        // Las notificaciones por rechazo de documento están deshabilitadas.
        return null;
    }
}
