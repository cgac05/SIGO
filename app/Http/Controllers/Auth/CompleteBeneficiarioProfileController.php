<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Beneficiario;
use App\Rules\CurpValida;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CompleteBeneficiarioProfileController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $user = $request->user()->loadMissing('beneficiario');

        if (! $user->isBeneficiario()) {
            return redirect()->route('dashboard');
        }

        if ($user->beneficiario) {
            return redirect()->route('dashboard');
        }

        return view('auth.complete-profile', [
            'user' => $user,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $user = $request->user()->loadMissing('beneficiario');

        if (! $user->isBeneficiario()) {
            return redirect()->route('dashboard');
        }

        if ($user->beneficiario) {
            return redirect()->route('dashboard');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'apellido_paterno' => ['required', 'string', 'max:50'],
            'apellido_materno' => ['required', 'string', 'max:50'],
            'telefono' => ['required', 'string', 'max:15'],
            'curp' => ['required', 'string', 'size:18', new CurpValida, Rule::unique('Beneficiarios', 'curp')],
            'acepta_privacidad' => ['required', 'accepted'],
        ]);

        $curp = mb_strtoupper($data['curp']);
        $birthDate = CurpValida::extractBirthDate($curp);

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

        return redirect()->route('dashboard')->with('status', 'profile-completed');
    }
}