<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
{
    // 1. Validación inicial en Laravel (Filtro rápido)
    $request->validate([
        'nombre' => ['required', 'string', 'max:50'],
        'apellido_paterno' => ['required', 'string', 'max:50'],
        'curp' => ['required', 'string', 'size:18'],
        'correo_inst' => ['required', 'string', 'lowercase', 'email', 'max:100'],
        'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
    ]);

    try {
        // 2. Ejecutar el SP y recibir el status_code
        // Usamos SET NOCOUNT ON para que PHP no se confunda con mensajes internos de SQL
        $resultado = \DB::select('SET NOCOUNT ON; EXEC sp_RegistrarBeneficiario ?, ?, ?, ?, ?, ?, ?, ?, ?', [
            $request->nombre,
            $request->apellido_paterno,
            $request->apellido_materno,
            $request->curp,
            $request->fecha_nacimiento, // Viene de tu script de CURP
            $request->genero,           // Viene de tu campo oculto
            $request->telefono,         // Viene con la máscara (311)...
            $request->correo,
            $request->password
        ]);

        $respuesta = $resultado[0];

        // 3. Evaluar el status_code (Lógica de Robustez)
        if ($respuesta->status_code == 0) {
            // ÉXITO: Buscamos al usuario por su CURP (que es la PK)
            $user = \App\Models\User::find($respuesta->curp);
            
            // Iniciamos sesión automáticamente
            \Auth::login($user);

            return redirect(route('dashboard', absolute: false));
        } 
        
        // ERRORES DE NEGOCIO (-1 CURP duplicada, -2 Correo duplicado)
        return back()->withErrors([
            'curp' => $respuesta->message,
        ])->withInput();

    } catch (\Exception $e) {
        // ERROR DE SISTEMA (Falla de conexión, error de sintaxis SQL, etc.)
        return back()->withErrors([
            'error' => 'Error crítico en el servidor: ' . $e->getMessage(),
        ])->withInput();
    }
}
}
