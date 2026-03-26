<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiario;
use App\Models\User;
use App\Rules\CurpValida;
use App\Rules\Recaptcha;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
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
        $captchaRules = app()->environment('testing') ? ['nullable'] : ['required', new Recaptcha];

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['required', 'string', 'max:50'],
            'telefono' => ['required', 'string', 'max:15'],
            'curp' => ['required', 'string', 'size:18', new CurpValida, Rule::unique('Beneficiarios', 'curp')],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:100', Rule::unique('Usuarios', 'email')],
            'password' => ['required', 'confirmed', \Illuminate\Validation\Rules\Password::defaults()],
            'acepta_privacidad' => ['required', 'accepted'],
            'g-recaptcha-response' => $captchaRules,
        ]);

        $curp = mb_strtoupper($data['curp']);
        $birthDate = CurpValida::extractBirthDate($curp);

        $user = DB::transaction(function () use ($data, $curp, $birthDate) {
            $user = User::create([
                'email' => $data['email'],
                'password_hash' => Hash::make($data['password']),
                'tipo_usuario' => 'Beneficiario',
                'activo' => true,
            ]);

            Beneficiario::create([
                'curp' => $curp,
                'fk_id_usuario' => $user->id_usuario,
                'nombre' => $data['name'],
                'apellido_paterno' => $data['apellido_paterno'],
                'apellido_materno' => $data['apellido_materno'],
                'telefono' => preg_replace('/\D+/', '', $data['telefono']) ?: null,
                'fecha_nacimiento' => $birthDate,
                'genero' => substr($curp, 10, 1),
                'acepta_privacidad' => true,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', 'Usuario registrado con éxito');
    }
}
