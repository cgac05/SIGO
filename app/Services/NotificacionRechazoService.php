<?php

namespace App\Services;

use App\Models\Beneficiario;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Message;

class NotificacionRechazoService
{
    /**
     * Enviar notificación de rechazo al beneficiario por correo
     */
    public static function enviarNotificacionRechazo($folio, $beneficiario, $apoyo, $motivoDirectivo = null)
    {
        try {
            // Obtener correo del beneficiario
            $correoDestino = $beneficiario->email;
            
            if (!$correoDestino || !filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
                \Log::warning("Correo inválido para beneficiario {$beneficiario->curp}: {$correoDestino}");
                return false;
            }

            // Construir asunto
            $asunto = "Solicitud {$folio} - Rechazada";

            // Construir cuerpo del correo
            $cuerpoCorreo = $this->construirCuerpoRechazo($folio, $beneficiario, $apoyo, $motivoDirectivo);

            // Enviar correo
            Mail::send('emails.rechazo-solicitud', [
                'folio' => $folio,
                'beneficiario_nombre' => $beneficiario->nombre,
                'apoyo_nombre' => $apoyo->nombre_apoyo,
                'motivos_generales' => self::obtenerMotivosGenerales(),
                'motivo_directivo' => $motivoDirectivo,
            ], function (Message $message) use ($asunto, $correoDestino) {
                $message->subject($asunto)
                        ->to($correoDestino);
            });

            \Log::info("Correo de rechazo enviado a {$correoDestino} para folio {$folio}");
            return true;

        } catch (\Exception $e) {
            \Log::error("Error enviando correo de rechazo para folio {$folio}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener motivos generales de rechazo
     */
    private static function obtenerMotivosGenerales()
    {
        return [
            'Documentación incompleta o no cumple con los requisitos especificados',
            'Información conflictiva o no verificable',
            'Incumplimiento de criterios de elegibilidad del programa',
            'Presupuesto disponible insuficiente en la categoría del apoyo',
            'Otros requisitos administrativos no satisfechos',
        ];
    }

    /**
     * Construir cuerpo del correo
     */
    private static function construirCuerpoRechazo($folio, $beneficiario, $apoyo, $motivoDirectivo)
    {
        $nombre = $beneficiario->nombre . ' ' . $beneficiario->apellido_paterno;
        
        $cuerpo = "Estimado(a) {$nombre},\n\n";
        $cuerpo .= "Después de revisar thoracicamente su solicitud, se ha tomado la decisión de rechazar su participación en el programa.\n\n";
        $cuerpo .= "DETALLES DE LA SOLICITUD:\n";
        $cuerpo .= "─────────────────────────────\n";
        $cuerpo .= "Folio: {$folio}\n";
        $cuerpo .= "Programa: {$apoyo->nombre_apoyo}\n";
        $cuerpo .= "Fecha de Rechazo: " . now()->format('d/m/Y H:i') . "\n\n";

        $cuerpo .= "MOTIVOS DEL RECHAZO:\n";
        $cuerpo .= "─────────────────────────────\n";

        if ($motivoDirectivo && !empty(trim($motivoDirectivo))) {
            $cuerpo .= "Detalle del Directivo:\n";
            $cuerpo .= "{$motivoDirectivo}\n\n";
        } else {
            $cuerpo .= "• La solicitud no cumplió con los requisitos mínimos de elegibilidad.\n";
            $cuerpo .= "• Es posible que la documentación presentada sea incompleta o no corresponda a los criterios del programa.\n\n";
        }

        $cuerpo .= "PRÓXIMOS PASOS:\n";
        $cuerpo .= "─────────────────────────────\n";
        $cuerpo .= "Si considera que hay un error en esta decisión, puede contactar a nuestra oficina administrativa para solicitar una revisión de su caso.\n\n";

        $cuerpo .= "Cordialmente,\n";
        $cuerpo .= "Equipo de Evaluación de Solicitudes\n";
        $cuerpo .= "Sistema SIGO\n";

        return $cuerpo;
    }
}
