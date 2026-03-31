<?php
/**
 * Script para preparar el entorno de pruebas manuales
 * Ejecutar con: php manual_test_setup.php
 */

require 'vendor/autoload.php';
require 'bootstrap/app.php';

use App\Models\User;
use App\Models\Apoyo;
use App\Models\HitosApoyo;
use App\Models\DirectivoCalendarioPermiso;
use Illuminate\Support\Facades\Schema;

echo "\n=== SIGO Google Calendar - Manual Testing Setup ===\n\n";

try {
    // 1. Verificar Google Client
    echo "1. Verificando Google Client disponible...\n";
    if (class_exists('Google\Client')) {
        echo "   ✅ Google Client cargado correctamente\n\n";
    } else {
        echo "   ❌ Google Client NO encontrado\n\n";
    }

    // 2. Verificar columnas en BD
    echo "2. Verificando columnas de Google Calendar en BD...\n";
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
            echo "   ✅ Hitos_Apoyo.$col\n";
        } else {
            echo "   ❌ Hitos_Apoyo.$col (FALTA - ejecutar migración)\n";
            $allColumnsExist = false;
        }
    }

    foreach ($apoyoColumns as $col) {
        if (Schema::hasColumn('Apoyos', $col)) {
            echo "   ✅ Apoyos.$col\n";
        } else {
            echo "   ❌ Apoyos.$col (FALTA - ejecutar migración)\n";
            $allColumnsExist = false;
        }
    }

    if (!$allColumnsExist) {
        echo "\n⚠️  ADVERTENCIA: Ejecutar migración antes de continuar:\n";
        echo "php artisan migrate --path=\"database/migrations/2026_03_28_000000_add_google_calendar_fields.php\"\n\n";
        exit(1);
    }

    echo "\n\n3. Preparando datos de prueba...\n\n";

    // 3. Crear usuario directivo de prueba
    echo "   3.1. Creando usuario Directivo de prueba...\n";
    
    // Primero crear el usuario en Usuarios
    $directivo = User::firstOrCreate(
        ['email' => 'directivo@test.local'],
        [
            'password_hash' => bcrypt('password123'),  // Test password
            'tipo_usuario' => 'Personal',  // Es personal, no beneficiario
            'activo' => true,
        ]
    );
    echo "        ✅ Usuario: ID=" . $directivo->id_usuario . "\n";
    echo "        📧 Email: " . $directivo->email . "\n";
    echo "        🔐 Password: password123\n\n";

    // Luego crear el registro Personal con rol fk_rol = 3 (Directivo)
    echo "   3.1b. Asignando rol Directivo...\n";
    \DB::table('Personal')->updateOrInsert(
        ['fk_id_usuario' => $directivo->id_usuario],
        [
            'numero_empleado' => 'TEST-001',
            'nombre' => 'Test',
            'apellido_paterno' => 'Directivo',
            'apellido_materno' => 'Usuario',
            'fk_rol' => 3,  // Rol Directivo
            'puesto' => 'Directivo de Prueba',
        ]
    );
    echo "        ✅ Rol asignado: Directivo (ID=3)\n\n";

    // 4. Crear Permiso de Calendario
    echo "   3.2. Creando permiso de calendario...\n";
    $permiso = DirectivoCalendarioPermiso::firstOrCreate(
        ['fk_id_directivo' => $directivo->id_usuario],
        [
            'activo' => 0,
            'access_token' => null,
            'refresh_token' => null,
            'token_expiracion' => null,
        ]
    );
    echo "        ✅ Permiso: ID=" . $permiso->id . "\n";
    echo "        ℹ️  Estado: Inactivo (se activará después de OAuth)\n\n";

    // 5. Crear Apoyo de prueba
    echo "   3.3. Creando Apoyo de prueba...\n";
    $apoyo = Apoyo::firstOrCreate(
        ['nombre_apoyo' => 'Test Apoyo Calendar'],
        [
            'descripcion' => 'Apoyo para validar sincronización con Google Calendar',
            'sincronizar_calendario' => true,
            'recordatorio_dias' => 3,
            'google_group_email' => 'test-apoyo@injuve.local',
            'activo' => true,
        ]
    );
    echo "        ✅ Apoyo: ID=" . $apoyo->id_apoyo . "\n";
    echo "        📅 Sincronización: " . ($apoyo->sincronizar_calendario ? 'Habilitada' : 'Deshabilitada') . "\n\n";

    // 6. Crear Hito de prueba
    echo "   3.4. Creando Hito de prueba...\n";
    $hito = HitosApoyo::firstOrCreate(
        [
            'fk_id_apoyo' => $apoyo->id_apoyo,
            'nombre_hito' => 'Test Hito con Calendar',
        ],
        [
            'descripcion' => 'Hito para validar creación automática en Google Calendar',
            'fecha_inicio' => now(),
            'fecha_fin' => now()->addDays(7),
            'google_calendar_sync' => true,
        ]
    );
    echo "        ✅ Hito: ID=" . $hito->id_hito . "\n";
    echo "        📅 Sincronización: " . ($hito->google_calendar_sync ? 'Habilitada' : 'Deshabilitada') . "\n\n";

    // 7. Mostrar info para próximos pasos
    echo "\n=== PRÓXIMOS PASOS ===\n\n";
    echo "1. CONFIGURAR GOOGLE OAUTH:\n";
    echo "   - Google Cloud Console: https://console.cloud.google.com\n";
    echo "   - Habilitar: Google Calendar API v3\n";
    echo "   - Crear: OAuth 2.0 Credentials (Web Application)\n";
    echo "   - Configurar .env:\n";
    echo "     GOOGLE_CLIENT_ID=xxx\n";
    echo "     GOOGLE_CLIENT_SECRET=xxx\n";
    echo "     GOOGLE_REDIRECT_URI=http://localhost:8000/admin/calendario/callback\n\n";

    echo "2. EJECUTAR SERVIDOR LOCAL:\n";
    echo "   php artisan serve\n\n";

    echo "3. PRUEBAS MANUALES:\n";
    echo "   http://localhost:8000/admin/calendario\n\n";

    echo "=== DATOS DE PRUEBA ===\n";
    echo "Directivo:  ID=" . $directivo->id_usuario . " | Email=" . $directivo->email . " | Password=password123\n";
    echo "Apoyo:      ID=" . $apoyo->id_apoyo . " | Nombre=" . $apoyo->nombre_apoyo . "\n";
    echo "Hito:       ID=" . $hito->id_hito . " | Nombre=" . $hito->nombre_hito . "\n";
    echo "Permiso:    ID=" . $permiso->id . " | Estado=Inactivo (se activa con OAuth)\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "✅ Setup completado\n\n";
