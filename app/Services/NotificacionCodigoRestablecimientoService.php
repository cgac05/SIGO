<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Mail\Message;
use Illuminate\Support\Facades\Mail;

class NotificacionCodigoRestablecimientoService
{
    /**
     * Enviar el código de restablecimiento al correo del usuario.
     */
    public static function enviarCodigo(User $usuario, string $codigo): bool
    {
        try {
            $correoDestino = trim((string) $usuario->email);

            if ($correoDestino === '' || ! filter_var($correoDestino, FILTER_VALIDATE_EMAIL)) {
                \Log::warning("Correo inválido para restablecimiento de contraseña: {$correoDestino}");

                return false;
            }

            $asunto = 'Código de restablecimiento de contraseña';

            Mail::send('emails.codigo-restablecimiento', [
                'nombre' => $usuario->display_name ?: $usuario->email,
                'codigo' => $codigo,
                'minutosVigencia' => (int) config('auth.passwords.users.expire', 60),
            ], function (Message $message) use ($asunto, $correoDestino) {
                $message->subject($asunto)
                    ->from((string) config('mail.from.address'), 'Plataforma Estatal de Juventud')
                    ->to($correoDestino);
            });

            \Log::info("Código de restablecimiento enviado a {$correoDestino} para usuario {$usuario->id_usuario}");

            return true;
        } catch (\Exception $e) {
            \Log::error('Error enviando código de restablecimiento: ' . $e->getMessage());

            return false;
        }
    }
}