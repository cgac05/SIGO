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
        $userRole = null;

        if ($user->isPersonal()) {
            // Cargar la relación personal si aún no está cargada
            if (!$user->relationLoaded('personal')) {
                $user->load('personal');
            }
            
            $personal = $user->personal;
            
            if ($personal && isset($personal->fk_rol)) {
                $userRole = (int) $personal->fk_rol;
                
                \Log::debug("CheckRole: Usuario {$user->id_usuario} ({$user->email}) Personal encontrado", [
                    'numero_empleado' => $personal->numero_empleado ?? 'NULL',
                    'fk_rol' => $userRole,
                    'rolesRequeridos' => $roles
                ]);
            } else {
                \Log::warning("CheckRole: Usuario {$user->id_usuario} ({$user->email}) Personal NO encontrado o SIN fk_rol");
            }
        } else {
            \Log::warning("CheckRole: Usuario {$user->id_usuario} ({$user->email}) NO es Personal (tipo: {$user->tipo_usuario})");
        }

        // Convertir los roles a enteros para comparación
        $rolesRequeridos = array_map('intval', $roles);

        // Si el usuario tiene rol y está en los roles requeridos
        if ($userRole !== null && in_array($userRole, $rolesRequeridos)) {
            \Log::debug("CheckRole: ✅ Acceso PERMITIDO para usuario {$user->id_usuario}");
            return $next($request);
        }

        // Si no tiene el rol requerido, denegar acceso
        $errorMsg = "No tienes permiso para acceder a este recurso. Rol requerido: " . implode(', ', $rolesRequeridos);
        if (config('app.debug')) {
            $errorMsg .= " (Tu rol actual: " . ($userRole ?? 'NULL') . ")";
        }
        
        \Log::warning("CheckRole: ❌ Acceso DENEGADO para usuario {$user->id_usuario} ({$user->email})", [
            'rolEncontrado' => $userRole,
            'rolesRequeridos' => $rolesRequeridos
        ]);
        
        return response()->view('errors.403', [
            'message' => $errorMsg
        ], 403);
    }
}
