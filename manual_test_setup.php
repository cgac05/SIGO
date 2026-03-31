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
    
    $apoyo Columns = [
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

    echo "\n3. Preparando datos de prueba...\n\n";

    // 3. Crear usuario directivo de prueba
    echo "   3.1. Creando usuario Directivo de prueba...\n";
    $directivo = User::firstOrCreate(
        ['nombre_usuario' => 'directivo_test'],
        [
            'correo_electronico' => 'directivo.test@injuve.local',
            'tipo_usuario' => 'Directivo',
            'id_rol' => 3,
            'estado' => 'Activo',
        ]
    );
    echo "        ✅ Directivo: ID=" . $directivo->id_usuario . "\n";
    echo "        📧 Email: " . $directivo->correo_electronico . "\n\n";

    // 4. Crear Permiso de Calendario
    echo "   3.2. Creando permiso de calendario...\n";
    $permiso = DirectivoCalendarioPermiso::firstOrCreate(
        ['fk_id_directivo' => $directivo->id_usuario],
        [
            'activo' => 0,  // Desactivado hasta OAuth completo
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
            'estado' => 'Activo',
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
    echo "   - Acceder a: https://console.cloud.google.com\n";
    echo "   - Crear proyecto nuevo\n";
    echo "   - Habilitar Google Calendar API\n";
    echo "   - Crear credenciales OAuth 2.0 (Aplicación Web)\n";
    echo "   - Configurar en .env:\n";
    echo "     GOOGLE_CLIENT_ID=xxx\n";
    echo "     GOOGLE_CLIENT_SECRET=xxx\n";
    echo "     GOOGLE_REDIRECT_URI=http://localhost:8000/calendario/callback\n\n";

    echo "2. EJECUTAR SERVIDOR LOCAL:\n";
    echo "   php artisan serve\n\n";

    echo "3. INICIAR PRUEBAS MANUALES:\n";
    echo "   Abrir: http://localhost:8000/calendario/configuracion\n";
    echo "   Seguir: LOCAL_QA_TESTING_GUIDE.md → Parte 3 (Tests Manuales)\n\n";

    echo "=== DATOS DE PRUEBA CREADOS ===\n";
    echo "Directivo ID:    " . $directivo->id_usuario . "\n";
    echo "Apoyo ID:        " . $apoyo->id_apoyo . "\n";
    echo "Hito ID:         " . $hito->id_hito . "\n";
    echo "Permisos ID:     " . $permiso->id . "\n\n";

} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

echo "✅ Setup completado exitosamente\n\n";
