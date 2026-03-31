<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\Apoyo;
use App\Models\HitosApoyo;
use App\Models\DirectivoCalendarioPermiso;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SetupManualTests extends Command
{
    protected $signature = 'test:setup-manual';
    protected $description = 'Preparar datos para pruebas manuales de Google Calendar';

    public function handle()
    {
        $this->info("\n=== SIGO Google Calendar - Manual Testing Setup ===\n");

        try {
            // 1. Verificar Google Client
            $this->info('1. Verificando Google Client disponible...');
            if (class_exists('Google\Client')) {
                $this->line('   ✅ Google Client cargado correctamente');
            } else {
                $this->error('   ❌ Google Client NO encontrado');
                return 1;
            }

            // 2. Verificar columnas en BD
            $this->info('\n2. Verificando columnas de Google Calendar en BD...');
            
            $hitosColumns = [
                'google_calendar_event_id',
                'google_calendar_sync',
                'ultima_sincronizacion',
                'cambios_locales_pendientes'
            ];
            
            $apoyoColumns = [
                'sincronizar_calendario',
                'recordatorio_dias',
                'google_group_email'
            ];

            $allColumnsExist = true;
            
            foreach ($hitosColumns as $col) {
                if (Schema::hasColumn('Hitos_Apoyo', $col)) {
                    $this->line("   ✅ Hitos_Apoyo.{$col}");
                } else {
                    $this->line("   ❌ Hitos_Apoyo.{$col} (FALTA - ejecutar migración)");
                    $allColumnsExist = false;
                }
            }

            foreach ($apoyoColumns as $col) {
                if (Schema::hasColumn('Apoyos', $col)) {
                    $this->line("   ✅ Apoyos.{$col}");
                } else {
                    $this->line("   ❌ Apoyos.{$col} (FALTA - ejecutar migración)");
                    $allColumnsExist = false;
                }
            }

            if (!$allColumnsExist) {
                $this->warn("\n⚠️  ADVERTENCIA: Ejecutar migración antes de continuar:");
                $this->line('php artisan migrate --path="database/migrations/2026_03_28_000000_add_google_calendar_fields.php"');
                return 1;
            }

            $this->info('\n3. Preparando datos de prueba...\n');

            // 3. Crear usuario directivo de prueba
            $this->line('   3.1. Creando usuario Directivo de prueba...');
            
            $directivo = User::firstOrCreate(
                ['email' => 'directivo@test.local'],
                [
                    'password_hash' => bcrypt('password123'),
                    'tipo_usuario' => 'Personal',
                    'activo' => true,
                ]
            );
            $this->line('        ✅ Usuario: ID=' . $directivo->id_usuario);
            $this->line('        📧 Email: ' . $directivo->email);
            $this->line('        🔐 Password: password123');

            // Crear el registro Personal con rol fk_rol = 3 (Directivo)
            $this->line('\n   3.1b. Asignando rol Directivo...');
            DB::table('Personal')->updateOrInsert(
                ['fk_id_usuario' => $directivo->id_usuario],
                [
                    'numero_empleado' => 'TEST-001',
                    'nombre' => 'Test',
                    'apellido_paterno' => 'Directivo',
                    'apellido_materno' => 'Usuario',
                    'fk_rol' => 2,
                    'puesto' => 'Directivo de Prueba',
                ]
            );
            $this->line('        ✅ Rol asignado: Directivo (ID=2)');

            // 4. Crear Permiso de Calendario
            $this->line('\n   3.2. Creando permiso de calendario...');
            $permiso = DirectivoCalendarioPermiso::firstOrCreate(
                ['fk_id_directivo' => $directivo->id_usuario],
                [
                    'activo' => false,
                    'email_directivo' => $directivo->email,
                    'google_access_token' => null,
                    'google_refresh_token' => null,
                ]
            );
            $this->line('        ✅ Permiso: ID=' . $permiso->id_permiso);
            $this->line('        ℹ️  Estado: Inactivo (se activará después de OAuth)');

            // 5. Crear Apoyo de prueba
            $this->line('\n   3.3. Creando Apoyo de prueba...');
            $apoyo = Apoyo::firstOrCreate(
                ['nombre_apoyo' => 'Test Apoyo Calendar'],
                [
                    'descripcion' => 'Apoyo para validar sincronización con Google Calendar',
                    'sincronizar_calendario' => true,
                    'recordatorio_dias' => 3,
                    'google_group_email' => 'test-apoyo@injuve.local',
                    'activo' => true,
                    'fecha_inicio' => now()->format('Y-m-d'),
                    'fecha_fin' => now()->addMonths(12)->format('Y-m-d'),
                ]
            );
            $this->line('        ✅ Apoyo: ID=' . $apoyo->id_apoyo);
            $this->line('        📅 Sincronización: ' . ($apoyo->sincronizar_calendario ? 'Habilitada' : 'Deshabilitada'));

            // 6. Crear Hito de prueba (OPCIONAL)
            $this->line('\n   3.4. Creando Hito de prueba (SKIP por compatibilidad)...');
            $hito = null;
            try {
                $hito = HitosApoyo::firstOrCreate(
                    [
                        'fk_id_apoyo' => $apoyo->id_apoyo,
                        'nombre_hito' => 'Test Hito con Calendar',
                    ],
                    [
                        'fecha_inicio' => now(),
                        'fecha_fin' => now()->addDays(7),
                        'google_calendar_sync' => true,
                    ]
                );
                $this->line('        ✅ Hito: ID=' . $hito->id_hito);
            } catch (\Exception $e) {
                $this->line('        ⚠️  Hito omitido (campos incompatibles con BD)');
                $hito = $apoyo;  // Use apoyo as fallback for display
            }

            // 7. Mostrar info para próximos pasos
            $this->info('\n\n=== PRÓXIMOS PASOS ===\n');
            
            $this->line('1. CONFIGURAR GOOGLE OAUTH:');
            $this->line('   - Google Cloud Console: https://console.cloud.google.com');
            $this->line('   - Habilitar: Google Calendar API v3');
            $this->line('   - Crear: OAuth 2.0 Credentials (Web Application)');
            $this->line('   - Configurar .env:');
            $this->line('     GOOGLE_CLIENT_ID=xxx');
            $this->line('     GOOGLE_CLIENT_SECRET=xxx');
            $this->line('     GOOGLE_REDIRECT_URI=http://localhost:8000/admin/calendario/callback');

            $this->line('\n2. EJECUTAR SERVIDOR LOCAL:');
            $this->line('   php artisan serve');

            $this->line('\n3. PRUEBAS MANUALES:');
            $this->line('   http://localhost:8000/admin/calendario');

            $this->info('\n=== DATOS DE PRUEBA ===');
            $this->line('Directivo:  ID=' . $directivo->id_usuario . ' | Email=' . $directivo->email . ' | Password=password123');
            $this->line('Apoyo:      ID=' . $apoyo->id_apoyo . ' | Nombre=' . $apoyo->nombre_apoyo);
            $this->line('Hito:       ID=' . $hito->id_hito . ' | Nombre=' . $hito->nombre_hito);
            $this->line('Permiso:    ID=' . $permiso->id_permiso . ' | Estado=Inactivo (se activa con OAuth)');
            
            $this->info('\n✅ Setup completado exitosamente!\n');
            return 0;

        } catch (\Exception $e) {
            $this->error('❌ ERROR: ' . $e->getMessage());
            $this->error($e->getTraceAsString());
            return 1;
        }
    }
}
