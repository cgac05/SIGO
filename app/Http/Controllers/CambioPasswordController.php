<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class CambioPasswordController extends Controller
{
    public function update(Request $request)
    {
        $request->validate([
            'password'              => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
        ], [
            'password.required'   => 'La contraseña es obligatoria.',
            'password.min'        => 'La contraseña debe tener al menos 8 caracteres.',
            'password.confirmed'  => 'Las contraseñas no coinciden.',
        ]);

        $user = Auth::user();

        // Verificar que la nueva contraseña sea diferente a la actual
        if (Hash::check($request->password, $user->password_hash)) {
            return back()->with('error_password', 'La nueva contraseña debe ser diferente a la actual.');
        }

        // Actualizar contraseña y marcar como cambiada
        $user->password_hash         = Hash::make($request->password);
        $user->debe_cambiar_password = false;
        $user->save();

        // Limpiar la sesión
        session()->forget('forzar_cambio_password');

        return redirect()->route('dashboard')
            ->with('exito', '¡Contraseña actualizada correctamente!');
    }
}