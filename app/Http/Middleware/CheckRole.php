<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // Verificar que el usuario esté autenticado
        if (!auth()->check()) {
            return redirect('login');
        }

        $user = auth()->user();
        
        // Si no se especifican roles, permitir acceso
        if (empty($roles)) {
            return $next($request);
        }

        // Obtener el rol del usuario
        // El rol está en: Personal.fk_rol si es personal, o tipo_usuario si es beneficiario
        $userRole = null;

        if ($user->isPersonal()) {
            // Si es personal, obtener el id_rol de la tabla Personal
            $personal = $user->personal;
            if ($personal) {
                $userRole = $personal->fk_rol;
            }
        }

        // Convertir los roles a enteros para comparación
        $rolesRequeridos = array_map('intval', $roles);

        // Si el usuario tiene rol y está en los roles requeridos
        if ($userRole !== null && in_array($userRole, $rolesRequeridos)) {
            return $next($request);
        }

        // Si no tiene el rol requerido, denegar acceso
        return response()->view('errors.403', [
            'message' => 'No tienes permiso para acceder a este recurso. Rol requerido: ' . implode(', ', $rolesRequeridos)
        ], 403);
    }
}
