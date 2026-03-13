<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PersonalController extends Controller
{
    public function create()
    {
        return view('personal.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'numero_empleado'  => 'required|string|max:15',
            'nombre'           => 'required|string|max:150',
            'apellido_paterno' => 'required|string|max:50',
            'apellido_materno' => 'nullable|string|max:50',
            'puesto'           => 'nullable|string|max:100',
            'email'            => 'required|email|max:100',
            'password'         => 'required|string|min:6',
            'fk_rol'           => 'required|integer',
        ]);

        DB::transaction(function () use ($request) {
            // 1. Crear usuario en tabla Usuarios
            $idUsuario = DB::table('Usuarios')->insertGetId([
                'email'         => $request->email,
                'password_hash' => Hash::make($request->password),
                'tipo_usuario'  => 'Personal',
                'activo'        => 1,
            ]);

            // 2. Crear registro en tabla Personal
            DB::table('Personal')->insert([
                'numero_empleado'  => $request->numero_empleado,
                'fk_id_usuario'    => $idUsuario,
                'nombre'           => $request->nombre,
                'apellido_paterno' => $request->apellido_paterno,
                'apellido_materno' => $request->apellido_materno,
                'fk_rol'           => $request->fk_rol,
                'puesto'           => $request->puesto,
            ]);
        });

        return redirect()->route('personal.create')->with('success', true);
    }
}