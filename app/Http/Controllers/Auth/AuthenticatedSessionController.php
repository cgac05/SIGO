<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->ensureIsNotRateLimited();

        $user = User::with(['personal', 'beneficiario'])
            ->where('email', $request->string('email'))
            ->first();

        if (! $user || ! $user->activo || empty($user->password_hash) || ! Hash::check($request->string('password'), $user->password_hash)) {
            RateLimiter::hit($request->throttleKey());

            return back()->withInput($request->only('email', 'remember'))
                ->withErrors(['email' => 'Las credenciales proporcionadas son incorrectas o el usuario se encuentra inactivo.'])
                ->with('auth_error', 'Las credenciales proporcionadas son incorrectas o el usuario se encuentra inactivo.');
        }

        RateLimiter::clear($request->throttleKey());

        $user->forceFill(['ultima_conexion' => now()])->save();

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        return redirect()->intended($this->redirectPath($user));
    }

    protected function redirectPath(User $user): string
    {
        if ($user->isBeneficiario() && ! $user->hasCompleteBeneficiarioProfile()) {
            return route('registro.completar-perfil.show');
        }

        return route('dashboard');
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
