<?php

namespace App\Services;

use App\Models\Beneficiario;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class NotificacionAprobacionService
{
    /**
     * Enviar notificación de aprobación al beneficiario por correo
     */
    public static function enviarNotificacionAprobacion($folio, $cuv, $beneficiario, $apoyo, $monto)
    {
        try {
            // Obtener correo del beneficiario
            $correoDestino = $beneficiario->email;
            
            if (!$correoDestino || !filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
                \Log::warning("Correo inválido para beneficiario {$beneficiario->curp}: {$correoDestino}");
                return false;
            }

            // Construir asunto
            $asunto = "Solicitud {$folio} - Aprobada";

            // Enviar correo
            Mail::send('emails.aprobacion-solicitud', [
                'folio' => $folio,
                'cuv' => $cuv,
                'beneficiario_nombre' => $beneficiario->nombre,
                'apoyo_nombre' => $apoyo->nombre_apoyo,
                'tipo_apoyo' => $apoyo->tipo_apoyo ?? 'Económico',
                'costo_unitario' => $apoyo->costo_unitario ?? 0,
                'monto' => $monto,
                'curp' => $beneficiario->curp,
            ], function (Message $message) use ($asunto, $correoDestino) {
                $message->subject($asunto)
                        ->to($correoDestino);
            });

            \Log::info("Correo de aprobación enviado a {$correoDestino} para folio {$folio}");
            return true;

        } catch (\Exception $e) {
            \Log::error("Error enviando correo de aprobación para folio {$folio}: " . $e->getMessage());
            return false;
        }
    }
}
