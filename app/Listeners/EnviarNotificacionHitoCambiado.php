<?php

namespace App\Listeners;

use App\Events\HitoCambiado;
use App\Models\Notificacion;
use App\Mail\HitoCambiadoMail;
use Illuminate\Support\Facades\Mail;

class EnviarNotificacionHitoCambiado
{
    public function handle(HitoCambiado $event)
    {
        // Obtener el beneficiario desde el apoyo
        $beneficiario = $event->hito->apoyo->solicitud->beneficiario;

        if (!$beneficiario) {
            return null;
        }

        // 1. Crear notificación en BD
        $notificacion = Notificacion::create([
            'id_beneficiario' => $beneficiario->id,
            'tipo' => 'hito_cambio',
            'titulo' => 'Progreso: ' . $this->obtenerNombreHito($event->hito->tipo),
            'mensaje' => "Tu solicitud ha avanzado a: {$this->obtenerNombreHito($event->hito->tipo)}",
            'datos' => [
                'hito_tipo' => $event->hito->tipo,
                'tipo_cambio' => $event->tipo_cambio,
                'fecha_cambio' => $event->hito->fecha_inicio?->toDateTimeString(),
                'id_apoyo' => $event->hito->id_apoyo,
            ],
            'accion_url' => "/solicitud/{$event->hito->apoyo->id_solicitud}",
            'leida' => false,
        ]);

        // 2. Enviar Email
        if ($beneficiario->email) {
            Mail::to($beneficiario->email)->send(new HitoCambiadoMail(
                $beneficiario,
                $event->hito,
                $event->tipo_cambio
            ));
        }

        return $notificacion;
    }

    private function obtenerNombreHito($tipo): string
    {
        $nombres = [
            1 => 'Publicación',
            2 => 'Recepción',
            3 => 'Análisis Administrativo',
            4 => 'Resultados',
            5 => 'Cierre',
        ];

        return $nombres[$tipo] ?? "Etapa {$tipo}";
    }
}
