<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ForzarCambioPassword
{
    public function handle(Request $request, Closure $next)
    {
        $user = Auth::user();

        // Solo aplica a usuarios autenticados de tipo personal
        if (
            $user &&
            $user->tipo_usuario === 'personal' &&
            $user->debe_cambiar_password &&
            !$request->routeIs('password.forzar.update') &&
            !$request->routeIs('logout')
        ) {
            // Si es una petición AJAX devolvemos JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'debe_cambiar_password' => true,
                    'message' => 'Debes cambiar tu contraseña antes de continuar.'
                ], 403);
            }

            // Inyectamos la variable en la sesión para que el modal aparezca
            session(['forzar_cambio_password' => true]);
        }

        return $next($request);
    }
}