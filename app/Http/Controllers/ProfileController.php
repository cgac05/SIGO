<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use JsonSerializable;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        return view('profile.edit', [
            'user' => $request->user(),
            'activeSessions' => $this->getActiveSessions($request),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $user->loadMissing(['personal', 'beneficiario']);

            $user->fill([
                'email' => $validated['email'],
            ]);

            if ($user->isDirty('email')) {
                $user->email_verified_at = null;
            }

            $this->updateProfileFullName($user, $validated['display_name']);

            $user->save();
        });

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Update the user's full name in the related profile table.
     */
    private function updateProfileFullName(User $user, string $fullName): void
    {
        [$nombre, $apellidoPaterno, $apellidoMaterno] = $this->splitFullName($fullName);

        if ($user->personal) {
            $user->personal->fill([
                'nombre' => $nombre,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
            ])->save();

            return;
        }

        if ($user->beneficiario) {
            $user->beneficiario->fill([
                'nombre' => $nombre,
                'apellido_paterno' => $apellidoPaterno,
                'apellido_materno' => $apellidoMaterno,
            ])->save();
        }
    }

    /**
     * Split a full name into the columns used by the profile tables.
     */
    private function splitFullName(string $fullName): array
    {
        $parts = preg_split('/\s+/u', trim($fullName), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return ['', '', ''];
        }

        if (count($parts) === 1) {
            return [$this->normalizeNamePart($parts[0], 150), '', ''];
        }

        if (count($parts) === 2) {
            return [
                $this->normalizeNamePart($parts[0], 150),
                $this->normalizeNamePart($parts[1], 50),
                '',
            ];
        }

        $apellidoMaterno = array_pop($parts) ?: '';
        $apellidoPaterno = array_pop($parts) ?: '';
        $nombre = implode(' ', $parts);

        if ($nombre === '') {
            $nombre = $apellidoPaterno;
            $apellidoPaterno = $apellidoMaterno;
            $apellidoMaterno = '';
        }

        return [
            $this->normalizeNamePart($nombre, 150),
            $this->normalizeNamePart($apellidoPaterno, 50),
            $this->normalizeNamePart($apellidoMaterno, 50),
        ];
    }

    /**
     * Normalize a name segment and keep it within the target column size.
     */
    private function normalizeNamePart(string $value, int $limit): string
    {
        return Str::limit(Str::squish($value), $limit, '');
    }

    /**
     * Upload profile photo.
     */
    public function uploadPhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'foto_perfil' => 'required|image|mimes:jpeg,png,gif|max:5120',
        ], [
            'foto_perfil.required' => 'Selecciona una foto',
            'foto_perfil.image' => 'El archivo debe ser una imagen',
            'foto_perfil.mimes' => 'Solo se permiten JPG, PNG y GIF',
            'foto_perfil.max' => 'La foto no puede pesar más de 5 MB',
        ]);

        // Delete old photo if exists
        if ($request->user()->foto_perfil) {
            Storage::disk('public')->delete($request->user()->foto_perfil);
        }

        // Store new photo
        $photoPath = $request->file('foto_perfil')->store('profile-photos', 'public');

        $request->user()->update(['foto_perfil' => $photoPath]);

        return Redirect::route('profile.edit')->with('status', 'Photo updated successfully');
    }

    /**
     * Disconnect Google account.
     */
    public function googleDisconnect(Request $request): RedirectResponse
    {
        $request->user()->update([
            'google_id' => null,
            'google_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_avatar' => null,
        ]);

        return Redirect::route('profile.edit')->with('status', 'Google account disconnected');
    }

    /**
     * Download user data (ARCO - Acceso).
     */
    public function arcoDownload(Request $request)
    {
        $user = $request->user();
        $data = [
            'usuario' => [
                'id' => $user->id_usuario,
                'email' => $user->email,
                'tipo_usuario' => $user->tipo_usuario,
                'activo' => $user->activo,
                'fecha_creacion' => $user->fecha_creacion,
                'ultima_conexion' => $user->ultima_conexion,
                'google_vinculado' => (bool) $user->google_id,
            ]
        ];

        if ($user->personal) {
            $data['personal'] = $user->personal->toArray();
        }

        if ($user->beneficiario) {
            $data['beneficiario'] = $user->beneficiario->toArray();
        }

        $filename = 'mis-datos-' . date('Y-m-d-His') . '.json';
        return response()->json($data, 200, [
            'Content-Disposition' => "attachment; filename=$filename"
        ]);
    }

    /**
     * Request account cancellation (ARCO - Cancelación).
     */
    public function arcoCancel(Request $request): RedirectResponse
    {
        $request->validate([
            'razon' => 'required|string|min:10|max:500',
        ]);

        $request->user()->update([
            'arco_cancelacion_solicitada' => true,
            'arco_cancelacion_fecha' => now(),
            'arco_cancelacion_razon' => $request->input('razon'),
        ]);

        // TODO: Send notification email to data protection officer
        // TODO: Create audit log entry

        return Redirect::route('profile.edit')->with('status', 'Solicitud de cancelación enviada. Tu cuenta será eliminada en 30 días.');
    }

    /**
     * Update notification preferences (ARCO - Oposición).
     */
    public function updateNotificationPreferences(Request $request): RedirectResponse
    {
        $request->user()->update([
            'notif_email_news' => (bool) $request->input('notif_email_news'),
            'notif_email_apoyos' => (bool) $request->input('notif_email_apoyos'),
            'notif_email_status' => (bool) $request->input('notif_email_status'),
            'notif_email_marketing' => (bool) $request->input('notif_email_marketing'),
        ]);

        return Redirect::route('profile.edit')->with('status', 'Preferencias de notificación actualizadas');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Enable 2FA (Two Factor Authentication).
     */
    public function enable2fa(Request $request): RedirectResponse
    {
        // TODO: Generar secret y QR para Google Authenticator
        // Por ahora, simplemente marcar como habilitado
        $request->user()->update([
            'two_factor_enabled' => true,
            'two_factor_secret' => null, // TODO: Generar secret real
        ]);

        return Redirect::route('profile.edit')->with('status', '2FA temporalmente habilitado. (Implementación incompleta)');
    }

    /**
     * Disable 2FA.
     */
    public function disable2fa(Request $request): RedirectResponse
    {
        $request->user()->update([
            'two_factor_enabled' => false,
            'two_factor_secret' => null,
        ]);

        return Redirect::route('profile.edit')->with('status', '2FA deshabilitado');
    }

    /**
     * Logout all other sessions.
     */
    public function logoutAllSessions(Request $request): RedirectResponse
    {
        $user = $request->user();

        DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        return Redirect::route('profile.edit')->with('status', 'Se cerraron las demás sesiones activas');
    }

    /**
     * Obtener sesiones activas del usuario autenticado.
     */
    private function getActiveSessions(Request $request): array
    {
        if (config('session.driver') !== 'database') {
            return [];
        }

        $user = $request->user();
        $currentSessionId = $request->session()->getId();
        $activeSince = now()->subMinutes((int) config('session.lifetime', 120))->timestamp;

        return DB::table(config('session.table', 'sessions'))
            ->where('user_id', $user->getAuthIdentifier())
            ->where('last_activity', '>=', $activeSince)
            ->orderByDesc('last_activity')
            ->get()
            ->map(function ($session) use ($currentSessionId) {
                $userAgent = (string) ($session->user_agent ?? '');

                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address ?: 'No disponible',
                    'user_agent' => $userAgent,
                    'user_agent_preview' => Str::limit($userAgent ?: 'Navegador no identificado', 110),
                    'device_type' => $this->detectDeviceType($userAgent),
                    'browser' => $this->detectBrowser($userAgent),
                    'operating_system' => $this->detectOperatingSystem($userAgent),
                    'summary' => $this->formatSessionSummary($userAgent),
                    'last_activity_human' => Carbon::createFromTimestamp((int) $session->last_activity)->diffForHumans(),
                    'is_current' => $session->id === $currentSessionId,
                ];
            })
            ->all();
    }

    private function formatSessionSummary(string $userAgent): string
    {
        $parts = array_filter([
            $this->detectDeviceType($userAgent),
            $this->detectBrowser($userAgent),
            $this->detectOperatingSystem($userAgent),
        ], fn ($part) => ! in_array($part, ['Desconocido', 'Navegador desconocido'], true));

        return implode(' · ', $parts) ?: 'Sesión activa';
    }

    private function detectDeviceType(string $userAgent): string
    {
        $normalized = mb_strtolower($userAgent);

        if (str_contains($normalized, 'ipad') || str_contains($normalized, 'tablet')) {
            return 'Tablet';
        }

        if (str_contains($normalized, 'mobile') || str_contains($normalized, 'iphone') || str_contains($normalized, 'android')) {
            return 'Móvil';
        }

        return 'Escritorio';
    }

    private function detectBrowser(string $userAgent): string
    {
        $normalized = mb_strtolower($userAgent);

        return match (true) {
            str_contains($normalized, 'edg/') => 'Edge',
            str_contains($normalized, 'opr/') || str_contains($normalized, 'opera') => 'Opera',
            str_contains($normalized, 'chrome/') && ! str_contains($normalized, 'edg/') && ! str_contains($normalized, 'opr/') => 'Chrome',
            str_contains($normalized, 'firefox/') => 'Firefox',
            str_contains($normalized, 'safari/') && ! str_contains($normalized, 'chrome/') => 'Safari',
            str_contains($normalized, 'trident/') || str_contains($normalized, 'msie') => 'Internet Explorer',
            default => 'Navegador desconocido',
        };
    }

    private function detectOperatingSystem(string $userAgent): string
    {
        $normalized = mb_strtolower($userAgent);

        return match (true) {
            str_contains($normalized, 'windows nt 11.0') => 'Windows 11',
            str_contains($normalized, 'windows nt 10.0') => 'Windows 10',
            str_contains($normalized, 'windows nt 6.3') => 'Windows 8.1',
            str_contains($normalized, 'windows nt 6.2') => 'Windows 8',
            str_contains($normalized, 'windows nt 6.1') => 'Windows 7',
            str_contains($normalized, 'windows nt') => 'Windows',
            str_contains($normalized, 'android') => 'Android',
            str_contains($normalized, 'iphone') || str_contains($normalized, 'ipad') || str_contains($normalized, 'ios') => 'iOS',
            str_contains($normalized, 'mac os x') || str_contains($normalized, 'macintosh') => 'macOS',
            str_contains($normalized, 'linux') => 'Linux',
            default => 'Desconocido',
        };
    }
}
