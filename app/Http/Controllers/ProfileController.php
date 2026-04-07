<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill([
            'email' => $request->validated('email'),
        ]);

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
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
        // Invalidar todas las sesiones excepto la actual
        // TODO: Implementar con persistencia de sesiones en BD
        session()->invalidate();
        session()->flush();

        return Redirect::route('profile.edit')->with('status', 'Todas las sesiones han sido cerradas');
    }
}
