<?php

namespace App\Http\Controllers;

use App\Models\Usuario;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

/**
 * ReauthenticationController
 *
 * Controlador para manejar la re-autenticación de usuarios.
 * Se utiliza cuando se realizan operaciones sensibles como:
 * - Firma digital de solicitudes
 * - Cambio de configuración crítica
 * - Desconexión de servicios integrados
 *
 * Compliance:
 * - LGPDP: Auditoría de todos los intentos de re-autenticación
 * - Seguridad: Protección contra ataques de fuerza bruta
 */
class ReauthenticationController extends Controller
{
    /**
     * Verificar re-autenticación del usuario
     *
     * POST /auth/reauth-verify
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function verify(Request $request)
    {
        // Validar que el usuario esté autenticado
        if (!Auth::check()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay sesión activa.',
            ], 401);
        }

        // Validar input
        $request->validate([
            'password' => 'required|string|min:6',
            'otp' => 'nullable|string|size:6|regex:/^\d+$/',
        ]);

        $usuario = Auth::user();
        $password = $request->input('password');
        $otp = $request->input('otp');

        try {
            // 1. Verificar contraseña
            if (!Hash::check($password, $usuario->password)) {
                $this->registrarIntento(false, $usuario->id, 'contraseña_incorrecta');

                return response()->json([
                    'success' => false,
                    'message' => 'Contraseña incorrecta.',
                ], 422);
            }

            // 2. Si está habilitado 2FA, validar OTP
            if ($usuario->two_factor_enabled && !empty($otp)) {
                if (!$this->validarOTP($usuario, $otp)) {
                    $this->registrarIntento(false, $usuario->id, 'otp_incorrecto');

                    return response()->json([
                        'success' => false,
                        'message' => 'Código de verificación incorrecto.',
                    ], 422);
                }
            } elseif ($usuario->two_factor_enabled && empty($otp)) {
                // Se requiere OTP pero no se proporcionó
                return response()->json([
                    'success' => false,
                    'message' => 'Se requiere código de verificación 2FA.',
                    'requires_2fa' => true,
                ], 422);
            }

            // 3. Registrar intento exitoso
            $this->registrarIntento(true, $usuario->id, 'verificado');

            // 4. Crear token de re-autenticación temporal
            $token = $this->generarTokenReauth($usuario->id);

            return response()->json([
                'success' => true,
                'message' => 'Identidad verificada correctamente.',
                'reauth_token' => $token,
                'usuario' => [
                    'nombre' => $usuario->nombre,
                    'apellidos' => $usuario->apellidos,
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('Error en re-autenticación', [
                'usuario_id' => $usuario->id ?? null,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error en la verificación. Intenta de nuevo.',
            ], 500);
        }
    }

    /**
     * Validar código OTP (placeholder - implementar con servicio 2FA real)
     *
     * @param Usuario $usuario
     * @param string $otp
     * @return bool
     */
    private function validarOTP(Usuario $usuario, string $otp): bool
    {
        // TODO: Integrar con servicio de 2FA real
        // Ejemplo: Google Authenticator, Authy, AWS Cognito, etc.

        // Por ahora, verificar contra tabla de OTP temporal
        $otpRecord = \DB::table('otp_temporal')
            ->where('usuario_id', $usuario->id)
            ->where('codigo', $otp)
            ->where('expira_en', '>', now())
            ->first();

        if ($otpRecord) {
            // Marcar OTP como usado
            \DB::table('otp_temporal')
                ->where('id', $otpRecord->id)
                ->delete();

            return true;
        }

        return false;
    }

    /**
     * Generar token temporal de re-autenticación
     * Válido por 10 minutos
     *
     * @param int $usuarioId
     * @return string Token
     */
    private function generarTokenReauth(int $usuarioId): string
    {
        $token = \Illuminate\Support\Str::random(60);

        \DB::table('reauth_tokens')->insert([
            'usuario_id' => $usuarioId,
            'token' => hash('sha256', $token),
            'expira_en' => now()->addMinutes(10),
            'creado_en' => now(),
        ]);

        return $token;
    }

    /**
     * Registrar intento de re-autenticación en auditoría
     *
     * @param bool $exitoso
     * @param int $usuarioId
     * @param string $razon
     * @return void
     */
    private function registrarIntento(bool $exitoso, int $usuarioId, string $razon): void
    {
        \DB::table('auditoria_reauthenticacion')->insert([
            'usuario_id' => $usuarioId,
            'exitoso' => $exitoso,
            'razon' => $razon,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'timestamp' => now(),
        ]);

        // Log también en sistema log
        Log::channel('seguridad')->info('Re-autenticación ' . ($exitoso ? 'exitosa' : 'fallida'), [
            'usuario_id' => $usuarioId,
            'razon' => $razon,
            'ip' => request()->ip(),
        ]);
    }

    /**
     * Verificar si un token de re-autenticación es válido
     *
     * @param string $token
     * @return bool
     */
    public static function verificarTokenReauth(string $token): bool
    {
        $record = \DB::table('reauth_tokens')
            ->where('token', hash('sha256', $token))
            ->where('expira_en', '>', now())
            ->where('usado', false)
            ->first();

        if ($record) {
            // Marcar token como usado
            \DB::table('reauth_tokens')
                ->where('id', $record->id)
                ->update(['usado' => true, 'usado_en' => now()]);

            return true;
        }

        return false;
    }
}
