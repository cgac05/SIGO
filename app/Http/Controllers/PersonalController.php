<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Personal;

class PersonalController extends Controller
{
    public function create()
    {
        $roles = DB::table('Cat_Roles')->select('id_rol', 'nombre_rol')->get();
        return view('personal.crear', compact('roles'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_empleado'  => 'required|string|max:20|unique:Personal,numero_empleado',
            'nombre'           => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'email'            => 'required|email|max:100|unique:Usuarios,email',
            'password'         => 'required|string|min:8|confirmed',
            'fk_rol'           => 'required|integer|exists:Cat_Roles,id_rol',
        ], [
            'numero_empleado.required'  => 'El número de empleado es obligatorio.',
            'numero_empleado.unique'    => 'Ese número de empleado ya está registrado.',
            'nombre.required'           => 'El nombre es obligatorio.',
            'apellido_paterno.required' => 'El apellido paterno es obligatorio.',
            'email.required'            => 'El correo institucional es obligatorio.',
            'email.unique'              => 'Ese correo ya está en uso.',
            'email.email'               => 'Ingresa un correo válido.',
            'password.required'         => 'La contraseña es obligatoria.',
            'password.min'              => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'        => 'Las contraseñas no coinciden.',
            'fk_rol.required'           => 'Selecciona un rol.',
            'fk_rol.exists'             => 'El rol seleccionado no es válido.',
        ]);

        DB::beginTransaction();
        try {
            $usuario = User::create([
                'email'                  => strtolower($data['email']),
                'password_hash'          => Hash::make($data['password']),
                'tipo_usuario'           => 'personal',
                'activo'                 => true,
                'debe_cambiar_password'  => true,
            ]);

            Personal::create([
                'numero_empleado'  => $data['numero_empleado'],
                'fk_id_usuario'    => $usuario->id_usuario,
                'nombre'           => strtoupper($data['nombre']),
                'apellido_paterno' => strtoupper($data['apellido_paterno']),
                'apellido_materno' => strtoupper($data['apellido_materno'] ?? ''),
                'fk_rol'           => $data['fk_rol'],
            ]);

            DB::commit();
            return redirect()->route('personal.crear')
                ->with('exito', '¡Personal registrado correctamente!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }
}