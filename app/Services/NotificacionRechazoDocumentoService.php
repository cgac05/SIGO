<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Solicitud;
use App\Models\Beneficiario;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para enviar notificaciones de rechazo de documentos
 * a los beneficiarios
 */
class NotificacionRechazoDocumentoService
{
    /**
     * Envía notificación de rechazo de documento al beneficiario
     * 
     * @param Documento $documento Documento rechazado
     * @param string $motivo Motivo del rechazo
     * @return bool
     */
    public static function enviarNotificacionRechazo(Documento $documento, string $motivo): bool
    {
        try {
            // Obtener solicitud asociada
            $solicitud = $documento->solicitud;
            if (!$solicitud) {
                Log::warning('NotificacionRechazoDocumentoService: Solicitud no encontrada para documento', [
                    'doc_id' => $documento->id_doc,
                ]);
                return false;
            }

            // Obtener beneficiario
            $beneficiario = $solicitud->beneficiario;
            if (!$beneficiario) {
                Log::warning('NotificacionRechazoDocumentoService: Beneficiario no encontrado', [
                    'folio' => $solicitud->folio,
                ]);
                return false;
            }

            // Obtener usuario asociado al beneficiario para obtener email
            $usuario = $beneficiario->user;
            if (!$usuario || !$usuario->email) {
                Log::warning('NotificacionRechazoDocumentoService: Email no encontrado', [
                    'beneficiario_id' => $beneficiario->id_beneficiario,
                ]);
                return false;
            }

            $email = $usuario->email;

            // Validar email
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                Log::warning('NotificacionRechazoDocumentoService: Email inválido', [
                    'email' => $email,
                    'folio' => $solicitud->folio,
                ]);
                return false;
            }

            // Obtener datos del documento
            $nombreDocumento = $documento->tipoDocumento?->nombre_documento ?? 'Documento';
            $nombreApoyo = $solicitud->apoyo?->nombre_apoyo ?? 'Apoyo desconocido';

            // Enviar email
            Mail::send('emails.rechazo-documento', [
                'beneficiario_nombre' => $beneficiario->nombre,
                'beneficiario_genero' => $beneficiario->genero ?? 'O',
                'folio' => $solicitud->folio,
                'documento_nombre' => $nombreDocumento,
                'apoyo_nombre' => $nombreApoyo,
                'motivo' => $motivo,
                'fecha_rechazo' => now()->format('d/m/Y H:i'),
                'soporte_email' => config('mail.from.address', 'soporte@sigo.gob.mx'),
                'soporte_telefono' => '+52 (311) 2330853',
                'soporte_horario' => 'Lunes a Viernes 9:00 - 17:00 hrs',
            ], function ($message) use ($email, $beneficiario, $solicitud) {
                $message
                    ->to($email)
                    ->subject("Documento Rechazado - Folio {$solicitud->folio}");
            });

            Log::info('NotificacionRechazoDocumentoService: Email enviado exitosamente', [
                'email' => $email,
                'folio' => $solicitud->folio,
                'doc_id' => $documento->id_doc,
                'documento' => $nombreDocumento,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('NotificacionRechazoDocumentoService: Error enviando email', [
                'doc_id' => $documento->id_doc,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
