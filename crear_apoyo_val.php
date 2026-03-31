<?php
/**
 * Crear Apoyo de Validación
 * Uso: php crear_apoyo_val.php
 */

// Autoload de composer
require __DIR__ . '/vendor/autoload.php';

// Bootear Laravel
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "\n";
echo str_repeat("=", 60) . "\n";
echo "CREANDO APOYO DE VALIDACIÓN\n";
echo str_repeat("=", 60) . "\n\n";

try {
    // 1. Insertar Apoyo
    echo "[1/3] Insertando apoyo...\n";
    
    $apoyo_id = DB::connection('sqlsrv')->table('Apoyos')->insertGetId([
        'nombre_apoyo' => '✅ VALIDACIÓN - Test Evento Simple',
        'descripcion' => 'Apoyo para validar creación en Google Calendar en fecha correcta',
        'sincronizar_calendario' => 1,
        'recordatorio_dias' => 1,
        'activo' => 1,
        'anio_fiscal' => 2026,
        'tipo_apoyo' => 'Económico',
        'cupo_limite' => 1,
        'fecha_inicio' => Carbon::tomorrow(),
        'fecha_fin' => Carbon::tomorrow()->addDays(30)
    ]);
    
    echo "   ✅ Apoyo creado: ID = $apoyo_id\n";
    echo "      Nombre: ✅ VALIDACIÓN - Test Evento Simple\n";
    echo "      Sincronizar: Sí\n";
    echo "      Recordatorio: 1 día antes\n\n";
    
    // 2. Insertar Hito para mañana
    echo "[2/3] Insertando hito para mañana...\n";
    
    $fecha_inicio = Carbon::tomorrow()->startOfDay();
    $fecha_fin = Carbon::tomorrow()->endOfDay();
    
    $hito_id = DB::connection('sqlsrv')->table('hitos_apoyo')->insertGetId([
        'fk_id_apoyo' => $apoyo_id,
        'clave_hito' => 'VALIDACION_HITO_01',
        'nombre_hito' => '🎯 Evento de Validación',
        'orden_hito' => 1,
        'fecha_inicio' => $fecha_inicio,
        'fecha_fin' => $fecha_fin,
        'activo' => 1,
        'fecha_creacion' => Carbon::now(),
        'fecha_actualizacion' => Carbon::now(),
        'es_base' => 0,
        'google_calendar_sync' => 1
    ]);
    
    echo "   ✅ Hito creado: ID = $hito_id\n";
    echo "      Nombre: 🎯 Evento de Validación\n";
    echo "      Fecha: " . $fecha_inicio->format('Y-m-d H:i') . " (mañana)\n\n";
    
    // 3. Crear evento en Google Calendar
    echo "[3/3] Creando evento en Google Calendar...\n\n";
    
    $googleCalendarService = new \App\Services\GoogleCalendarService();
    $resultado = $googleCalendarService->crearEventosApoyo($apoyo_id);
    
    echo "   Resultado:\n";
    echo "      Eventos creados: " . ($resultado['eventos_creados'] ?? 0) . "\n";
    
    if (!empty($resultado['eventos_ids'])) {
        echo "      ✅ Event IDs creados:\n";
        foreach ($resultado['eventos_ids'] as $id) {
            echo "         - $id\n";
        }
    }
    
    if (!empty($resultado['errores'])) {
        echo "      ⚠️  Errores:\n";
        foreach ($resultado['errores'] as $error) {
            echo "         - $error\n";
        }
    }
    
    // 4. Verificar que se registró en BD
    echo "\n";
    echo str_repeat("-", 60) . "\n";
    echo "VERIFICACIÓN FINAL\n";
    echo str_repeat("-", 60) . "\n\n";
    
    $hito = DB::connection('sqlsrv')
        ->table('hitos_apoyo')
        ->where('id_hito', $hito_id)
        ->first();
    
    echo "Hito en BD:\n";
    echo "  ID: " . $hito->id_hito . "\n";
    echo "  Nombre: " . $hito->nombre_hito . "\n";
    echo "  Fecha: " . $hito->fecha_inicio . "\n";
    echo "  Google Event ID: " . ($hito->google_calendar_event_id ?? "No registrado") . "\n\n";
    
    if ($hito->google_calendar_event_id) {
        echo "✅ VALIDACIÓN EXITOSA\n";
        echo "   El evento se creó en Google Calendar y se registró en BD.\n\n";
        echo "Próximo paso: Verificar en Google Calendar\n";
        echo "  URL: https://calendar.google.com\n";
        echo "  Usuario: guillermoavilamora2@gmail.com\n";
        echo "  Buscar fecha: " . $fecha_inicio->format('Y-m-d (D)') . "\n";
        echo "  Evento: INJUVE - ✅ VALIDACIÓN - Test Evento Simple - 🎯 Evento de Validación\n";
    } else {
        echo "❌ ERROR\n";
        echo "   El evento no se registró en BD.\n";
        echo "   Ver logs: storage/logs/laravel.log\n";
    }
    
    echo "\n" . str_repeat("=", 60) . "\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

?>
