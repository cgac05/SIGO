<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiario;
use App\Models\User;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
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
    try {
        $resultado = DB::select('SET NOCOUNT ON; EXEC sp_LoginUniversal ?, ?', [
            $request->email,
            $request->password,
        ]);

        if (empty($resultado)) {
            return back()->withInput()
                ->withErrors(['email' => 'Credenciales incorrectas.'])
                ->with('auth_error', 'Credenciales incorrectas.');
        }

        $usuarioLogueado = $resultado[0];

    } catch (\Exception $e) {
        return back()->withInput()
            ->withErrors(['email' => 'Error en el sistema: ' . $e->getMessage()])
            ->with('auth_error', 'Error en el sistema.');
    }

    // ── Personal ───────────────────────────────────────────────────
    if (in_array($usuarioLogueado->tipo, ['personal', 'administrativo'], true)) {

        $user = User::where('correo_inst', $request->email)->first();

        if (!$user) {
            return back()->withInput()
                ->withErrors(['email' => 'Usuario no encontrado.'])
                ->with('auth_error', 'Usuario no encontrado.');
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    // ── Beneficiario ───────────────────────────────────────────────
    if ($usuarioLogueado->tipo === 'beneficiario') {
        $emailColumn = 'correo';

        foreach (['correo_electronico', 'correo', 'email'] as $candidateColumn) {
            if (Schema::hasColumn('Beneficiarios', $candidateColumn)) {
                $emailColumn = $candidateColumn;
                break;
            }
        }

        $user = Beneficiario::where($emailColumn, $request->email)->first();

        if (!$user) {
            return back()->withInput()
                ->withErrors(['email' => 'Beneficiario no encontrado.'])
                ->with('auth_error', 'Beneficiario no encontrado.');
        }

        Auth::guard('beneficiario')->login($user);
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    return back()->withInput()
        ->withErrors(['email' => 'Tipo de usuario no permitido.'])
        ->with('auth_error', 'Tipo de usuario no permitido.');
}

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        Auth::guard('beneficiario')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
