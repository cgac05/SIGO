<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\Personal;
use Illuminate\Support\Facades\DB;

class FixDirectivoAccess extends Command
{
    protected $signature = 'fix:directivo-access {email?}';
    protected $description = 'Fix directivo access by ensuring they have Personal record with rol:2';

    public function handle()
    {
        $email = $this->argument('email');

        if (!$email) {
            $this->info('=== Usuarios SIN Personal o SIN rol asignado ===\n');
            
            $usersWithoutPersonal = DB::select(
                'SELECT u.id_usuario, u.email, u.tipo_usuario
                FROM Usuarios u
                LEFT JOIN Personal p ON u.id_usuario = p.fk_id_usuario
                WHERE p.fk_id_usuario IS NULL'
            );

            if (empty($usersWithoutPersonal)) {
                $this->info('✅ Todos los usuarios Personal tienen registro en Personal.');
                return;
            }

            $this->error('❌ Usuarios sin Personal:');
            foreach ($usersWithoutPersonal as $user) {
                $this->line("  - ID {$user->id_usuario}: {$user->email} ({$user->tipo_usuario})");
            }
            $this->newLine();
            
            $this->info('Ejecute: php artisan fix:directivo-access <email>');
            return;
        }

        // Buscar usuario por email
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("❌ Usuario no encontrado: {$email}");
            return;
        }

        $this->info("\n=== Información de Usuario ===");
        $this->line("ID: {$user->id_usuario}");
        $this->line("Email: {$user->email}");
        $this->line("Tipo: {$user->tipo_usuario}");

        // Revisar si tiene Personal
        if ($user->personal) {
            $this->info("\n✅ Usuario ya tiene registro en Personal");
            $this->line("  - ID Personal: {$user->personal->id_personal}");
            $this->line("  - Número empleado: {$user->personal->numero_empleado}");
            $this->line("  - fk_rol actual: {$user->personal->fk_rol}");

            if ($user->personal->fk_rol == 2) {
                $this->info("\n✅ Usuario YA tiene rol 2 (Directivo)");
                $this->line("Debería tener acceso a /admin/presupuesto/dashboard");
                return;
            } else {
                if ($this->confirm("¿Actualizar rol a 2 (Directivo)?", true)) {
                    $user->personal->update(['fk_rol' => 2]);
                    $this->info("✅ Rol actualizado a: 2 (Directivo)");
                }
                return;
            }
        }

        // Sin Personal - crear uno
        $this->error("\n❌ Usuario NO tiene registro en Personal");

        if (!$this->confirm('¿Crear registro en Personal con rol 2 (Directivo)?', true)) {
            return;
        }

        $nombre = $this->ask('Nombre completo', 'Directivo');
        $numero_empleado = $this->ask('Número de empleado', 'DIR-' . $user->id_usuario);

        Personal::create([
            'numero_empleado' => $numero_empleado,
            'fk_id_usuario' => $user->id_usuario,
            'nombre' => $nombre,
            'apellido_paterno' => 'Test',
            'apellido_materno' => 'User',
            'fk_rol' => 2, // Directivo
            'puesto' => 'Directivo',
        ]);

        $this->info("\n✅ Registro Personal creado exitosamente");
        $this->line("  - Número empleado: {$numero_empleado}");
        $this->line("  - Nombre: {$nombre}");
        $this->line("  - Rol: 2 (Directivo)");

        $this->newLine();
        $this->info("El usuario ahora debería tener acceso a /admin/presupuesto/dashboard");
    }
}
