<?php

namespace App\Listeners;

use App\Events\SolicitudRechazada;
use App\Models\Notificacion;
use App\Mail\SolicitudRechazadaMail;
use Illuminate\Support\Facades\Mail;

class EnviarNotificacionSolicitudRechazada
{
    public function handle(SolicitudRechazada $event)
    {
        // 1. Crear notificación en BD
        $notificacion = Notificacion::create([
            'id_beneficiario' => $event->solicitud->id_beneficiario,
            'tipo' => 'solicitud_rechazada',
            'titulo' => 'Solicitud Rechazada: ' . $event->solicitud->folio,
            'mensaje' => "Tu solicitud {$event->solicitud->folio} fue rechazada. Motivo: {$event->motivo}",
            'datos' => [
                'folio' => $event->solicitud->folio,
                'motivo' => $event->motivo,
                'id_solicitud' => $event->solicitud->id,
            ],
            'accion_url' => "/solicitud/{$event->solicitud->id}",
            'leida' => false,
        ]);

        // 2. Enviar Email
        if ($event->solicitud->beneficiario->email) {
            Mail::to($event->solicitud->beneficiario->email)->send(new SolicitudRechazadaMail(
                $event->solicitud->beneficiario,
                $event->solicitud,
                $event->motivo
            ));
        }

        return $notificacion;
    }
}
