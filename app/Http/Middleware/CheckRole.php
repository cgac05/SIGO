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
        // PASO 1: Verificar autenticación
        \Log::info("=== CheckRole INICIO ===");
        \Log::info("Ruta: {$request->path()}");
        \Log::info("Middleware roles requeridos: " . json_encode($roles));
        
        if (!auth()->check()) {
            \Log::warning("CheckRole: Usuario NO autenticado - redirect a login");
            return redirect('login');
        }

        $user = auth()->user();
        \Log::info("PASO 1: Usuario autenticado", [
            'id' => $user->id_usuario,
            'email' => $user->email,
            'tipo_usuario' => $user->tipo_usuario
        ]);
        
        // Si no se especifican roles, permitir acceso
        if (empty($roles)) {
            \Log::info("CheckRole: PERMITIDO - no hay roles requeridos");
            return $next($request);
        }

        // PASO 2: Verificar si es Personal
        $isPersonal = $user->isPersonal();
        \Log::info("PASO 2: isPersonal() = " . ($isPersonal ? 'TRUE' : 'FALSE'));
        
        if (!$isPersonal) {
            \Log::error("CheckRole: Usuario NO es Personal, tipo_usuario = {$user->tipo_usuario}");
            return response()->view('errors.403', ['message' => 'Usuario no es Personal'], 403);
        }

        // PASO 3: Cargar relación personal si no está cargada
        \Log::info("PASO 3: Cargando relación Personal");
        
        if (!$user->relationLoaded('personal')) {
            \Log::info("  Relación NO cargada - ejecutando load()");
            $user->load('personal');
        } else {
            \Log::info("  Relación YA fue cargada antes");
        }

        // PASO 4: Verificar que personal existe
        $personal = $user->personal;
        \Log::info("PASO 4: Personal cargado", [
            'existe' => $personal ? 'SÍ' : 'NO',
            'numero_empleado' => $personal?->numero_empleado ?? 'NULL',
        ]);
        
        if (!$personal) {
            \Log::error("CheckRole: Personal NO ENCONTRADO en BD");
            return response()->view('errors.403', ['message' => 'Usuario sin registro Personal'], 403);
        }

        // PASO 5: Obtener rol
        $fkRolRaw = $personal->fk_rol;
        $userRole = $fkRolRaw !== null ? (int) $fkRolRaw : null;
        
        \Log::info("PASO 5: Rol obtenido", [
            'fk_rol_raw' => $fkRolRaw,
            'fk_rol_type' => gettype($fkRolRaw),
            'userRole_int' => $userRole,
            'userRole_type' => gettype($userRole),
        ]);
        
        if ($userRole === null) {
            \Log::error("CheckRole: fk_rol es NULL ❌");
            return response()->view('errors.403', ['message' => 'Usuario sin rol asignado'], 403);
        }

        // PASO 6: Convertir roles requeridos a enteros
        $rolesRequeridos = array_map('intval', $roles);
        \Log::info("PASO 6: Comparación de roles", [
            'userRole' => $userRole,
            'userRole_type' => gettype($userRole),
            'rolesRequeridos' => $rolesRequeridos,
            'rolesRequeridos_type' => implode(',', array_map('gettype', $rolesRequeridos)),
        ]);

        // PASO 7: Verificar si está en los roles requeridos
        $tieneAcceso = in_array($userRole, $rolesRequeridos, true); // strict comparison
        
        \Log::info("PASO 7: Verificación in_array", [
            'in_array_result' => $tieneAcceso ? 'TRUE' : 'FALSE',
            'in_array_strict' => in_array($userRole, $rolesRequeridos, true) ? 'TRUE' : 'FALSE',
        ]);

        if ($tieneAcceso) {
            \Log::info("✅ CheckRole: ACCESO PERMITIDO");
            return $next($request);
        }

        // ACCESO DENEGADO
        \Log::error("❌ CheckRole: ACCESO DENEGADO", [
            'usuario' => $user->email,
            'rol_usuario' => $userRole,
            'roles_requeridos' => $rolesRequeridos,
        ]);
        
        return response()->view('errors.403', [
            'message' => 'No tienes permiso. Rol requerido: ' . implode(', ', $rolesRequeridos) . " (Tu rol: {$userRole})"
        ], 403);
    }
}
