<?php

namespace App\Listeners;

use App\Events\DocumentoRechazado;
use App\Models\Notificacion;
use App\Mail\DocumentoRechazadoMail;
use Illuminate\Support\Facades\Mail;

class EnviarNotificacionDocumentoRechazado
{
    public function handle(DocumentoRechazado $event)
    {
        // 1. Crear notificación en BD
        $notificacion = Notificacion::create([
            'id_beneficiario' => $event->beneficiario->id,
            'tipo' => 'documento_rechazado',
            'titulo' => 'Documento Rechazado: ' . $event->nombreDocumento,
            'mensaje' => "El documento {$event->nombreDocumento} fue rechazado. Motivo: {$event->motivo}",
            'datos' => [
                'nombre_documento' => $event->nombreDocumento,
                'motivo' => $event->motivo,
                'id_solicitud' => $event->idSolicitud,
            ],
            'accion_url' => $event->idSolicitud ? "/solicitud/{$event->idSolicitud}" : null,
            'leida' => false,
        ]);

        // 2. Enviar Email (si tiene email)
        if ($event->beneficiario->email) {
            Mail::to($event->beneficiario->email)->send(new DocumentoRechazadoMail(
                $event->beneficiario,
                $event->nombreDocumento,
                $event->motivo,
                $event->idSolicitud
            ));
        }

        return $notificacion;
    }
}
