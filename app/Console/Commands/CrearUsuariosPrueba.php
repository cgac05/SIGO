<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class CrearUsuariosPrueba extends Command
{
    protected $signature = 'crear:usuarios-prueba';
    protected $description = 'Crea usuarios de prueba para testing (Beneficiario, Admin, Directivo)';

    public function handle()
    {
        $this->info("\n=== CREANDO USUARIOS DE PRUEBA PARA SIGO ===\n");

        try {
            // 1. BENEFICIARIO CON GMAIL
            $this->line("1️⃣  Creando Beneficiario (Gmail)...");
            
            $emailBeneficiario = '5j3sus01@gmail.com';
            $passwordBeneficiario = 'Beneficiario@2026!999';
            $curpBeneficiario = 'GALJ990101HDFRRS01';
            
            $existeBeneficiario = DB::table('Usuarios')->where('email', $emailBeneficiario)->first();
            
            if ($existeBeneficiario) {
                $this->warn("   ⚠️  Ya existe. Actualizando contraseña...");
                DB::table('Usuarios')
                    ->where('email', $emailBeneficiario)
                    ->update([
                        'password_hash' => Hash::make($passwordBeneficiario),
                        'debe_cambiar_password' => 0
                    ]);
                $beneficiarioId = $existeBeneficiario->id_usuario;
            } else {
                $beneficiarioId = DB::table('Usuarios')->insertGetId([
                    'email' => $emailBeneficiario,
                    'password_hash' => Hash::make($passwordBeneficiario),
                    'tipo_usuario' => 'Beneficiario',
                    'activo' => 1,
                    'debe_cambiar_password' => 0,
                    'fecha_creacion' => now()
                ]);
                
                // Crear registro en Beneficiarios
                DB::table('Beneficiarios')->insert([
                    'curp' => $curpBeneficiario,
                    'fk_id_usuario' => $beneficiarioId,
                    'nombre' => 'Jesús',
                    'apellido_paterno' => 'García',
                    'apellido_materno' => 'López',
                    'fecha_registro' => now(),
                    'acepta_privacidad' => 1
                ]);
            }
            $this->info("   ✅ Email: $emailBeneficiario");
            $this->info("   ✅ Contraseña: $passwordBeneficiario\n");

            // 2. ADMINISTRATIVO
            $this->line("2️⃣  Creando Administrativo...");
            
            $emailAdmin = 'admin@injuve.gob.mx';
            $passwordAdmin = 'AdminSIGO@2026!724';
            
            $existeAdmin = DB::table('Usuarios')->where('email', $emailAdmin)->first();
            
            if ($existeAdmin) {
                $this->warn("   ⚠️  Ya existe. Actualizando contraseña...");
                DB::table('Usuarios')
                    ->where('email', $emailAdmin)
                    ->update([
                        'password_hash' => Hash::make($passwordAdmin),
                        'debe_cambiar_password' => 0
                    ]);
                $adminId = $existeAdmin->id_usuario;
            } else {
                $adminId = DB::table('Usuarios')->insertGetId([
                    'email' => $emailAdmin,
                    'password_hash' => Hash::make($passwordAdmin),
                    'tipo_usuario' => 'Personal',
                    'activo' => 1,
                    'debe_cambiar_password' => 0,
                    'fecha_creacion' => now()
                ]);
            }
            
            $personalAdmin = DB::table('Personal')->where('fk_id_usuario', $adminId)->first();
            if (!$personalAdmin) {
                DB::table('Personal')->insert([
                    'numero_empleado' => 'ADM-' . $adminId,
                    'fk_id_usuario' => $adminId,
                    'nombre' => 'Admin',
                    'apellido_paterno' => 'INJUVE',
                    'apellido_materno' => 'Test',
                    'fk_rol' => 1 // Administrativo
                ]);
            }
            
            $this->info("   ✅ Email: $emailAdmin");
            $this->info("   ✅ Contraseña: $passwordAdmin\n");

            // 3. DIRECTIVO
            $this->line("3️⃣  Creando Directivo...");
            
            $emailDirectivo = 'directivo@test.local';
            $passwordDirectivo = 'DirectivoSIGO@2026!587';
            
            $existeDirectivo = DB::table('Usuarios')->where('email', $emailDirectivo)->first();
            
            if ($existeDirectivo) {
                $this->warn("   ⚠️  Ya existe. Actualizando contraseña...");
                DB::table('Usuarios')
                    ->where('email', $emailDirectivo)
                    ->update([
                        'password_hash' => Hash::make($passwordDirectivo),
                        'debe_cambiar_password' => 0
                    ]);
                $directivoId = $existeDirectivo->id_usuario;
            } else {
                $directivoId = DB::table('Usuarios')->insertGetId([
                    'email' => $emailDirectivo,
                    'password_hash' => Hash::make($passwordDirectivo),
                    'tipo_usuario' => 'Personal',
                    'activo' => 1,
                    'debe_cambiar_password' => 0,
                    'fecha_creacion' => now()
                ]);
            }
            
            $personalDirectivo = DB::table('Personal')->where('fk_id_usuario', $directivoId)->first();
            if (!$personalDirectivo) {
                DB::table('Personal')->insert([
                    'numero_empleado' => 'DIR-' . $directivoId,
                    'fk_id_usuario' => $directivoId,
                    'nombre' => 'Test',
                    'apellido_paterno' => 'Directivo',
                    'apellido_materno' => 'Usuario',
                    'fk_rol' => 2, // Directivo
                    'puesto' => 'Director de Prueba'
                ]);
            }
            
            $this->info("   ✅ Email: $emailDirectivo");
            $this->info("   ✅ Contraseña: $passwordDirectivo\n");

            $this->line("\n" . str_repeat("═", 60));
            $this->info("✅ USUARIOS CREADOS EXITOSAMENTE");
            $this->line(str_repeat("═", 60) . "\n");

            $this->info("📋 CREDENCIALES PARA LOGIN:\n");

            $this->line("┌────────────────────────────────────────────────────┐");
            $this->line("│ 👤 BENEFICIARIO (Gmail)                            │");
            $this->line("├────────────────────────────────────────────────────┤");
            $this->line("│ Email:      5j3sus01@gmail.com                     │");
            $this->line("│ Contraseña: Beneficiario@2026!999                  │");
            $this->line("└────────────────────────────────────────────────────┘\n");

            $this->line("┌────────────────────────────────────────────────────┐");
            $this->line("│ 🔑 ADMINISTRATIVO                                  │");
            $this->line("├────────────────────────────────────────────────────┤");
            $this->line("│ Email:      admin@injuve.gob.mx                    │");
            $this->line("│ Contraseña: AdminSIGO@2026!724                     │");
            $this->line("└────────────────────────────────────────────────────┘\n");

            $this->line("┌────────────────────────────────────────────────────┐");
            $this->line("│ 👔 DIRECTIVO                                       │");
            $this->line("├────────────────────────────────────────────────────┤");
            $this->line("│ Email:      directivo@test.local                   │");
            $this->line("│ Contraseña: DirectivoSIGO@2026!587                 │");
            $this->line("└────────────────────────────────────────────────────┘\n");

            $this->info("🌐 Accede a: http://localhost/SIGO/public/login");
            $this->info("📍 Luego a: /solicitudes/proceso (Directivo)");
            $this->info("           /admin/solicitudes (Administrativo)\n");

        } catch (\Exception $e) {
            $this->error("\n❌ ERROR: " . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
