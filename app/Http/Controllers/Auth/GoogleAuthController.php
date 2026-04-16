<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const OAUTH_CONTEXT_SESSION_KEY = 'google_oauth_context';
    private const OAUTH_LINK_SESSION_KEY = 'google_oauth_link_user_id';
    private const OAUTH_CONTEXT_LINK = 'link';

    public function redirect(Request $request): RedirectResponse
    {
        $this->clearGoogleOAuthContext($request);

        if ($request->boolean('link')) {
            if (! Auth::check()) {
                return redirect()->route('login')->with('auth_error', 'Debes iniciar sesión para vincular tu cuenta de Google.');
            }

            $request->session()->put(self::OAUTH_CONTEXT_SESSION_KEY, self::OAUTH_CONTEXT_LINK);
            $request->session()->put(self::OAUTH_LINK_SESSION_KEY, $request->user()->getAuthIdentifier());
        }

        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/drive.file',
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
            ])
            ->redirectUrl(route('auth.google.callback'))
            ->stateless()
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = Socialite::driver('google')
                ->redirectUrl(route('auth.google.callback'))
                ->stateless()
                ->user();
        } catch (\Throwable $exception) {
            Log::error('Google OAuth callback failed.', [
                'message' => $exception->getMessage(),
                'callback_url' => route('auth.google.callback'),
            ]);

            return redirect()->route('login')->with('auth_error', 'No fue posible completar la autenticación con Google.');
        }

        return $this->isGoogleLinkingRequest($request)
            ? $this->linkGoogleAccount($request, $googleUser)
            : $this->authenticateGoogleAccount($request, $googleUser);
    }

    private function authenticateGoogleAccount(Request $request, object $googleUser): RedirectResponse
    {
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

        $this->syncGoogleIdentity($user, $googleUser);
        $this->clearGoogleOAuthContext($request);

        Auth::login($user, true);
        $request->session()->regenerate();

        if ($user->isBeneficiario() && ! $user->hasCompleteBeneficiarioProfile()) {
            return redirect()->route('registro.completar-perfil.show');
        }

        return redirect()->route('dashboard');
    }

    private function linkGoogleAccount(Request $request, object $googleUser): RedirectResponse
    {
        $user = $this->resolveLinkTargetUser($request);

        if (! $user) {
            return $this->failedGoogleOAuthRedirect(
                $request,
                'Tu sesión expiró antes de completar la vinculación con Google.',
                true
            );
        }

        $linkedUser = User::query()
            ->where('google_id', $googleUser->getId())
            ->first();

        if ($linkedUser && (int) $linkedUser->getKey() !== (int) $user->getKey()) {
            return $this->failedGoogleOAuthRedirect(
                $request,
                'Esta cuenta de Google ya está vinculada a otra cuenta SIGO.',
                true
            );
        }

        $this->syncGoogleIdentity($user, $googleUser);
        $this->clearGoogleOAuthContext($request);

        Auth::login($user, true);
        $request->session()->regenerate();

        return redirect()->to(route('profile.edit') . '#google')
            ->with('status', 'Cuenta de Google vinculada correctamente.');
    }

    private function syncGoogleIdentity(User $user, object $googleUser): void
    {
        $updates = [
            'google_id' => $googleUser->getId(),
            'google_token' => json_encode($googleUser->token ?? null),
            'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn ?? 3600),
            'ultima_conexion' => now(),
            'activo' => true,
        ];

        if (! empty($googleUser->refreshToken)) {
            $updates['google_refresh_token'] = $googleUser->refreshToken;
        }

        $avatar = trim((string) ($googleUser->getAvatar() ?? ''));
        if ($avatar !== '') {
            $updates['google_avatar'] = $avatar;
        }

        $user->forceFill($updates)->save();
    }

    private function resolveLinkTargetUser(Request $request): ?User
    {
        $userId = $request->session()->pull(self::OAUTH_LINK_SESSION_KEY);

        if (! $userId) {
            return $request->user();
        }

        return User::find($userId);
    }

    private function isGoogleLinkingRequest(Request $request): bool
    {
        return $request->session()->get(self::OAUTH_CONTEXT_SESSION_KEY) === self::OAUTH_CONTEXT_LINK
            && $request->session()->has(self::OAUTH_LINK_SESSION_KEY);
    }

    private function failedGoogleOAuthRedirect(Request $request, string $message, bool $isLinkingRequest): RedirectResponse
    {
        $this->clearGoogleOAuthContext($request);

        if ($isLinkingRequest) {
            return redirect()->route('profile.edit')->with('auth_error', $message);
        }

        return redirect()->route('login')->with('auth_error', $message);
    }

    private function clearGoogleOAuthContext(Request $request): void
    {
        $request->session()->forget([
            self::OAUTH_CONTEXT_SESSION_KEY,
            self::OAUTH_LINK_SESSION_KEY,
        ]);
    }
}