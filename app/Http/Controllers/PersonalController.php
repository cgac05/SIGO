<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Personal;

class PersonalController extends Controller
{

    public function store(Request $request)
    {
        $data = $request->validate([
            'numero_empleado'  => 'required|string|max:20|unique:Personal,numero_empleado',
            'nombre'           => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'email'            => 'required|email|max:100|unique:Usuarios,email,NULL,id_usuario,activo,1',
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
            return redirect()->route('personal.create')
                ->with('exito', '¡Personal registrado correctamente!');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error al registrar: ' . $e->getMessage());
        }
    }

    public function index() 
    {
        $empleados = \App\Models\Personal::all();
        return view('personal.index', compact('empleados'));
    }
    
    public function create()
{
    $roles = \App\Models\Role::all(); 
    return view('personal.crear', compact('roles'));
}
    public function edit($id)
    {
        $personal = Personal::findOrFail($id);
        $roles = \App\Models\Role::all(); 
        return view('personal.editar', compact('personal', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $personal = Personal::findOrFail($id);
        $data = $request->validate([
            'nombre'           => 'required|string|max:100',
            'apellido_paterno' => 'required|string|max:100',
            'apellido_materno' => 'nullable|string|max:100',
            'email'            => 'required|email|max:100|unique:Usuarios,email,' . $personal->fk_id_usuario . ',id_usuario,activo,1',
            'password'         => 'nullable|string|min:8|confirmed',
            'fk_rol'           => 'required|integer',
        ]);

        DB::beginTransaction();
        try {
            $personal->update([
                'nombre'           => strtoupper($data['nombre']),
                'apellido_paterno' => strtoupper($data['apellido_paterno']),
                'apellido_materno' => strtoupper($data['apellido_materno'] ?? ''),
                'fk_rol'           => $data['fk_rol'],
            ]);

            $userData = [
                'email' => strtolower($data['email']),
            ];
            if (!empty($data['password'])) {
                $userData['password_hash'] = Hash::make($data['password']);
            }
            if ($personal->user) {
                $personal->user->update($userData);
            }

            DB::commit();
            return redirect()->route('personal.index')->with('exito', '¡Personal actualizado correctamente!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withInput()->with('error', 'Error al actualizar: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        $personal = Personal::findOrFail($id);
        DB::beginTransaction();
        try {
            $user = $personal->user;
            $personal->delete();
            if ($user) {
                $user->delete();
            }
            DB::commit();
            return redirect()->route('personal.index')->with('exito', '¡Personal eliminado correctamente!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error al eliminar: ' . $e->getMessage());
        }
    }
} 