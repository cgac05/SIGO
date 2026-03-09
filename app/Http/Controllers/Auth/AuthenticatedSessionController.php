<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiario; // Importa tu modelo de Beneficiario
use App\Models\User; // Importa tu modelo de User
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
    $resultado = \DB::select('SET NOCOUNT ON; EXEC sp_LoginUniversal ?, ?', [
        $request->email,
        $request->password
    ]);

    $usuarioLogueado = $resultado[0];

    if ($usuarioLogueado->tipo == 'personal') {
        $user = \App\Models\User::find($usuarioLogueado->id);
        \Auth::guard('web')->login($user);
        return redirect()->intended(route('dashboard'));
    } 
    
    if ($usuarioLogueado->tipo == 'beneficiario') {
        $user = \App\Models\Beneficiario::find($usuarioLogueado->id);
        \Auth::guard('beneficiario')->login($user);
        return redirect()->intended(route('dashboard'));
    }

    return back()->withErrors(['email' => 'Credenciales incorrectas.']);
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
