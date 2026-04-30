<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;

class GoogleAuthController extends Controller
{
    private const OAUTH_CONTEXT_SESSION_KEY = 'google_oauth_context';
    private const OAUTH_LINK_SESSION_KEY = 'google_oauth_link_user_id';
    private const OAUTH_DELETE_ACCOUNT_SESSION_KEY = 'google_oauth_delete_account_user_id';
    private const OAUTH_CONTEXT_LINK = 'link';
    private const OAUTH_CONTEXT_DELETE_ACCOUNT = 'delete_account';

    public function redirect(Request $request): RedirectResponse
    {
        $isLinkingRequest = $request->routeIs('auth.google') || $request->boolean('link');
        $isDeleteAccountRequest = $request->boolean('delete_account');

        $this->clearGoogleOAuthContext($request);

        $stateData = ['context' => 'login'];

        if ($isLinkingRequest) {
            if (! Auth::check()) {
                return redirect()->route('login')->with('auth_error', 'Debes iniciar sesión para vincular tu cuenta de Google.');
            }

            $stateData = ['context' => 'link', 'user_id' => $request->user()->getAuthIdentifier()];
            
            $request->session()->put(self::OAUTH_CONTEXT_SESSION_KEY, self::OAUTH_CONTEXT_LINK);
            $request->session()->put(self::OAUTH_LINK_SESSION_KEY, $request->user()->getAuthIdentifier());
        }

        if ($isDeleteAccountRequest) {
            if (! Auth::check()) {
                return redirect()->route('login')->with('auth_error', 'Debes iniciar sesión para confirmar la eliminación de tu cuenta.');
            }

            $stateData = ['context' => 'delete_account', 'user_id' => $request->user()->getAuthIdentifier()];

            $request->session()->put(self::OAUTH_CONTEXT_SESSION_KEY, self::OAUTH_CONTEXT_DELETE_ACCOUNT);
            $request->session()->put(self::OAUTH_DELETE_ACCOUNT_SESSION_KEY, $request->user()->getAuthIdentifier());
        }

        $state = encrypt(json_encode($stateData));

        return Socialite::driver('google')
            ->scopes([
                'https://www.googleapis.com/auth/drive.file',
                'https://www.googleapis.com/auth/userinfo.email',
                'https://www.googleapis.com/auth/userinfo.profile',
            ])
            ->redirectUrl(route('auth.google.callback'))
            ->stateless()
            ->with(['state' => $state])
            ->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $stateStr = decrypt($request->query('state'));
            $stateData = json_decode($stateStr, true) ?? [];
        } catch (\Exception $e) {
            $stateData = [];
        }

        $isLinkingRequest = ($stateData['context'] ?? '') === 'link' || $this->isGoogleLinkingRequest($request);
        $isDeleteAccountRequest = ($stateData['context'] ?? '') === 'delete_account' || $this->isGoogleDeleteAccountRequest($request);
        $stateUserId = $stateData['user_id'] ?? null;

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

            return $this->failedGoogleOAuthRedirect(
                $request,
                'No fue posible completar la autenticación con Google.',
                $isLinkingRequest
            );
        }

        if ($isDeleteAccountRequest) {
            return $this->confirmAccountDeletion($request, $googleUser, $stateUserId);
        }

        return $isLinkingRequest
            ? $this->linkGoogleAccount($request, $googleUser, $stateUserId)
            : $this->authenticateGoogleAccount($request, $googleUser);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }

    private function authenticateGoogleAccount(Request $request, object $googleUser): RedirectResponse
    {
        $user = User::with('beneficiario')
            ->where('google_id', $googleUser->getId())
            ->orWhere('email', $googleUser->getEmail())
            ->first();

        if ($user && ! $user->activo) {
            return $this->failedGoogleOAuthRedirect(
                $request,
                'Tu cuenta ha sido desactivada. No puedes iniciar sesión.',
                false
            );
        }

        if (! $user) {
            $user = User::create([
                'email' => $googleUser->getEmail(),
                'tipo_usuario' => 'Beneficiario',
                'activo' => true,
            ]);
        }

        // If the user exists but was deactivated, do not reactivate on Google login.
        if ($user && $user->exists && ! $user->activo) {
            return $this->failedGoogleOAuthRedirect(
                $request,
                'Esta cuenta ha sido desactivada y no puede iniciar sesión.',
                false
            );
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

    private function linkGoogleAccount(Request $request, object $googleUser, ?int $stateUserId = null): RedirectResponse
    {
        $user = $this->resolveLinkTargetUser($request, $stateUserId);

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

    private function confirmAccountDeletion(Request $request, object $googleUser, ?int $stateUserId = null): RedirectResponse
    {
        $user = $this->resolveDeleteAccountTargetUser($request, $stateUserId);

        if (! $user) {
            return $this->failedGoogleOAuthRedirect(
                $request,
                'Tu sesión expiró antes de confirmar la eliminación de la cuenta.',
                true
            );
        }

        $currentGoogleId = trim((string) ($user->google_id ?? ''));
        $currentEmail = mb_strtolower(trim((string) ($user->email ?? '')));
        $googleId = trim((string) ($googleUser->getId() ?? ''));
        $googleEmail = mb_strtolower(trim((string) ($googleUser->getEmail() ?? '')));

        if ($currentGoogleId !== '' && $currentGoogleId !== $googleId) {
            return $this->failedGoogleOAuthRedirect(
                $request,
                'La cuenta de Google utilizada no coincide con la cuenta que se quiere eliminar.',
                true
            );
        }

        if ($currentEmail !== '' && $googleEmail !== '' && $currentEmail !== $googleEmail) {
            return $this->failedGoogleOAuthRedirect(
                $request,
                'El correo de Google no coincide con el usuario autenticado.',
                true
            );
        }

        DB::transaction(function () use ($user): void {
            $user->forceFill([
                'activo' => 0,
            ])->save();
        });

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Tu cuenta fue desactivada correctamente.');
    }

    private function syncGoogleIdentity(User $user, object $googleUser): void
    {
        $updates = [
            'google_id' => $googleUser->getId(),
            'google_token' => json_encode($googleUser->token ?? null),
            'google_token_expires_at' => now()->addSeconds($googleUser->expiresIn ?? 3600),
            'ultima_conexion' => now(),
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

    private function resolveLinkTargetUser(Request $request, ?int $stateUserId = null): ?User
    {
        $userId = $stateUserId ?? $request->session()->pull(self::OAUTH_LINK_SESSION_KEY);

        if (! $userId) {
            return $request->user();
        }

        return User::find($userId);
    }

    private function resolveDeleteAccountTargetUser(Request $request, ?int $stateUserId = null): ?User
    {
        $userId = $stateUserId ?? $request->session()->pull(self::OAUTH_DELETE_ACCOUNT_SESSION_KEY);

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

    private function isGoogleDeleteAccountRequest(Request $request): bool
    {
        return $request->session()->get(self::OAUTH_CONTEXT_SESSION_KEY) === self::OAUTH_CONTEXT_DELETE_ACCOUNT
            && $request->session()->has(self::OAUTH_DELETE_ACCOUNT_SESSION_KEY);
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
            self::OAUTH_DELETE_ACCOUNT_SESSION_KEY,
        ]);
    }
}
