<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CrearAdministrativoPuro extends Command
{
    protected $signature = 'crear:admin-puro';
    protected $description = 'Crea usuario administrativo PURO sin permisos adicionales';

    public function handle()
    {
        $this->info("\n=== CREANDO USUARIO ADMINISTRATIVO PURO ===\n");

        try {
            $emailAdmin = 'administrativo@injuve.gob.mx';
            $passwordAdmin = 'AdminPuro@2026!111';
            
            $existeAdmin = DB::table('Usuarios')->where('email', $emailAdmin)->first();
            
            if ($existeAdmin) {
                $this->warn("⚠️  Usuario ya existe. Actualizando contraseña...");
                DB::table('Usuarios')
                    ->where('email', $emailAdmin)
                    ->update([
                        'password_hash' => Hash::make($passwordAdmin),
                        'debe_cambiar_password' => 0
                    ]);
                $adminId = $existeAdmin->id_usuario;
            } else {
                $this->line("Creando nuevo usuario administrativo puro...");
                
                $adminId = DB::table('Usuarios')->insertGetId([
                    'email' => $emailAdmin,
                    'password_hash' => Hash::make($passwordAdmin),
                    'tipo_usuario' => 'Personal',
                    'activo' => 1,
                    'debe_cambiar_password' => 0,
                    'fecha_creacion' => now()
                ]);
                
                $this->info("   ✅ Usuario creado en Usuarios (ID: $adminId)");
            }
            
            // Verificar registro en Personal
            $personalAdmin = DB::table('Personal')->where('fk_id_usuario', $adminId)->first();
            
            if (!$personalAdmin) {
                DB::table('Personal')->insert([
                    'numero_empleado' => 'ADM-PURO-' . $adminId,
                    'fk_id_usuario' => $adminId,
                    'nombre' => 'Administrativo',
                    'apellido_paterno' => 'Puro',
                    'apellido_materno' => 'Sistema',
                    'fk_rol' => 1, // SOLO Administrativo
                    'puesto' => 'Administrativo - Sin Permisos Adicionales'
                ]);
                $this->info("   ✅ Registro Personal creado con rol 1 (Administrativo)");
            } else {
                // Actualizar a rol 1 si tiene otro
                if ($personalAdmin->fk_rol != 1) {
                    DB::table('Personal')
                        ->where('fk_id_usuario', $adminId)
                        ->update(['fk_rol' => 1]);
                    $this->info("   ✅ Rol actualizado a 1 (Administrativo)");
                }
            }

            $this->line("\n" . str_repeat("═", 60));
            $this->info("✅ USUARIO ADMINISTRATIVO PURO CREADO");
            $this->line(str_repeat("═", 60) . "\n");

            $this->line("┌────────────────────────────────────────────────────┐");
            $this->line("│ 🔑 ADMINISTRATIVO PURO (Sin Finanzas)              │");
            $this->line("├────────────────────────────────────────────────────┤");
            $this->line("│ Email:      administrativo@injuve.gob.mx           │");
            $this->line("│ Contraseña: AdminPuro@2026!111                     │");
            $this->line("│ Rol:        Administrativo (ID: 1)                 │");
            $this->line("└────────────────────────────────────────────────────┘\n");

            $this->info("🌐 Accede a: http://localhost/SIGO/public/login");
            $this->info("📍 Este usuario SOLO verá funcionalidades de Administrativo\n");

        } catch (\Exception $e) {
            $this->error("\n❌ ERROR: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
