<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiario;
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
        'name' => ['required', 'string', 'max:50'],
        'apellido_paterno' => ['required', 'string', 'max:50'],
        'apellido_materno' => ['required', 'string', 'max:50'],
        'fecha_nacimiento' => ['required', 'date'],
        'genero' => ['required', 'string', 'in:H,M'],
        'telefono' => ['required', 'string', 'regex:/^\(\d{3}\) \d{3}-\d{4}$/'], // Formato (311) 123-4567
        'curp' => ['required', 'string', 'size:18'],
        'email' => ['required', 'string', 'lowercase', 'email', 'max:100'],
        'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
    ]);

    try {
        // 2. Ejecutar el SP y recibir el status_code
        // Usamos SET NOCOUNT ON para que PHP no se confunda con mensajes internos de SQL
        $resultado = \DB::select('SET NOCOUNT ON; EXEC sp_RegistrarBeneficiario ?, ?, ?, ?, ?, ?, ?, ?, ?', [
            $request->name,
            $request->apellido_paterno,
            $request->apellido_materno,
            $request->curp,
            $request->fecha_nacimiento, // Viene de tu script de CURP
            $request->genero,           // Viene de tu campo oculto
            $request->telefono,         // Viene con la máscara (311)...
            $request->email,
            $request->password
        ]);

        $respuesta = $resultado[0];

        // 3. Evaluar el status_code (Lógica de Robustez)
        if ($respuesta->status_code == 0) {
            // ÉXITO: Buscamos al beneficiario por su CURP (PK en Beneficiarios)
            $user = Beneficiario::find($respuesta->curp);

            if (!$user) {
                return back()->withErrors([
                    'error' => 'Registro creado, pero no se pudo recuperar el beneficiario para iniciar sesion.',
                ])->withInput();
            }
            
            // Iniciamos sesión con el guard de beneficiarios
            \Auth::guard('beneficiario')->login($user);

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
