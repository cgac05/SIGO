<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;

class TestDirectivoMiddleware extends Command
{
    protected $signature = 'test:directivo-middleware {email}';
    protected $description = 'Test directivo middleware for a specific user';

    public function handle()
    {
        $email = $this->argument('email');
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ Usuario no encontrado: {$email}");
            return;
        }

        $this->info("\n=== TESTING MIDDLEWARE PARA: {$email} ===\n");

        $this->line("1️⃣  Info del Usuario:");
        $this->line("  - ID: {$user->id_usuario}");
        $this->line("  - Email: {$user->email}");
        $this->line("  - Tipo: {$user->tipo_usuario}");
        $this->line("  - isPersonal(): " . ($user->isPersonal() ? 'SÍ ✅' : 'NO ❌'));

        $this->line("\n2️⃣  Info del Registro Personal:");

        if ($user->relationLoaded('personal')) {
            $this->line("  - Relación YA CARGADA");
        } else {
            $this->line("  - Relación NO cargada (será cargada ahora)");
            $user->load('personal');
        }

        if ($user->personal) {
            $this->line("  - Número empleado: {$user->personal->numero_empleado}");
            $this->line("  - Nombre: {$user->personal->nombre}");
            $this->line("  - fk_rol: " . ($user->personal->fk_rol ?? 'NULL ❌'));
            
            if ($user->personal->fk_rol) {
                $rolesMap = [1 => 'Administrativo', 2 => 'Directivo', 3 => 'Finanzas'];
                $rolNombre = $rolesMap[$user->personal->fk_rol] ?? 'DESCONOCIDO';
                $this->line("  - Rol nombre: {$rolNombre}");
            }
        } else {
            $this->error("  ❌ NO HAY REGISTRO PERSONAL");
        }

        $this->line("\n3️⃣  TEST DE MIDDLEWARE:");
        $this->line("  Rutas que requieren role:2 /admin/presupuesto/*");
        
        $userRole = null;
        if ($user->isPersonal() && $user->personal && isset($user->personal->fk_rol)) {
            $userRole = (int) $user->personal->fk_rol;
        }

        $rolesRequeridos = [2];
        $tieneAcceso = $userRole !== null && in_array($userRole, $rolesRequeridos);

        $this->line("  - Rol encontrado: " . ($userRole ?? 'NULL'));
        $this->line("  - Roles requeridos: " . implode(', ', $rolesRequeridos));
        $this->line("  - ¿Tiene acceso?: " . ($tieneAcceso ? '✅ SÍ' : '❌ NO'));

        if ($tieneAcceso) {
            $this->info("\n✅ RESULTADO: El usuario DEBERÍA tener acceso al dashboard");
        } else {
            $this->error("\n❌ RESULTADO: El usuario NO tiene acceso al dashboard");
            $this->error("   Razón: " . ($userRole === null ? 'Sin rol asignado' : "Rol {$userRole} no permitido"));
        }

        $this->line("\n4️⃣  COMANDO PARA REVISAR LOGS:");
        $this->line("  tail -f storage/logs/laravel.log | grep CheckRole\n");
    }
}
