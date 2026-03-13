<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    public function redirect(): RedirectResponse
    {
        return Socialite::driver('google')
            ->redirectUrl(route('auth.google.callback'))
            ->stateless()
            ->redirect();
    }

    public function callback(): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('auth.google.callback'))
                ->stateless()
                ->user();
        } catch (\Throwable $exception) {
            Log::error('Google OAuth callback failed.', [
                'message' => $exception->getMessage(),
                'type' => $exception::class,
                'callback_url' => route('auth.google.callback'),
            ]);

            return redirect()->route('login')->with('auth_error', 'No fue posible completar la autenticación con Google.');
        }

        $user = User::with('beneficiario')
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if (! $user) {
            $user = User::create([
                'email' => $googleUser->getEmail(),
                'tipo_usuario' => 'Beneficiario',
                'activo' => true,
            ]);
        }

        $user->forceFill([
            'email' => $googleUser->getEmail(),
            'tipo_usuario' => $user->tipo_usuario ?: 'Beneficiario',
            'google_id' => $googleUser->getId(),
            'google_token' => $googleUser->token,
            'google_refresh_token' => $googleUser->refreshToken,
            'google_avatar' => $googleUser->getAvatar(),
            'ultima_conexion' => now(),
            'activo' => true,
        ])->save();

        Auth::login($user, true);
        request()->session()->regenerate();

        if ($user->isBeneficiario() && ! $user->hasCompleteBeneficiarioProfile()) {
            return redirect()->route('registro.completar-perfil.show');
        }

        return redirect()->route('dashboard');
    }
}