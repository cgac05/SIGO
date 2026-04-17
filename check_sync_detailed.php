<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📊 ESTADO DE SINCRONIZACIÓN\n";
echo "═════════════════════════════════════════\n\n";

// Verificar permisos
$permiso = \App\Models\DirectivoCalendarioPermiso::where('email_directivo', 'guillermoavilamora2@gmail.com')->first();
echo "PERMISOS:\n";
if ($permiso) {
    echo "  ✅ Activo: " . ($permiso->activo ? 'SÍ' : 'NO') . "\n";
    echo "  ✅ Email: {$permiso->email_directivo}\n";
    echo "  ✅ Calendar ID: {$permiso->google_calendar_id}\n";
} else {
    echo "  ❌ NO HAY PERMISOS\n";
    exit(1);
}

echo "\n";

// Verificar apoyos con sincronización habilitada  
echo "APOYOS CON SINCRONIZACIÓN HABILITADA:\n";
$apoyos = \App\Models\Apoyo::where('sincronizar_calendario', 1)->get();
echo "  Total: " . $apoyos->count() . "\n";
foreach ($apoyos as $apoyo) {
    // Contar hitos de este apoyo
    $hitos = $apoyo->hitos;
    $conGoogle = $apoyo->hitos->filter(fn($h) => !empty($h->google_calendar_event_id))->count();
    echo "    - {$apoyo->nombre_apoyo}: {$hitos->count()} hitos ({$conGoogle} con Google ID)\n";
    
    foreach ($hitos as $hito) {
        $googleId = $hito->google_calendar_event_id ?? 'SIN CREAR';
        $sync = $hito->google_calendar_sync ? 'SÍ' : 'NO';
        echo "       • {$hito->nombre_hito}: sync={$sync}, google_id=$googleId\n";
    }
}

echo "\n";

// Ver logs de sincronización
echo "ÚLTIMOS LOGS DE SINCRONIZACIÓN:\n";
$logs = \App\Models\CalendarioSincronizacionLog::latest('fecha_cambio')->limit(15)->get();
if ($logs->count() > 0) {
    foreach ($logs as $log) {
        $tipo = $log->tipo_cambio;
        $origen = $log->origen_cambio;
        echo "  {$log->fecha_cambio} | $tipo | from $origen\n";
    }
} else {
    echo "  SIN LOGS\n";
}

echo "\n";

// Ver log de laravel
echo "ERRORES EN LARAVEL LOG:\n";
$logFile = storage_path('logs/laravel.log');
if (file_exists($logFile)) {
    $lines = file($logFile);
    $recentLines = array_slice($lines, -100);
    $errors = array_filter($recentLines, fn($l) => stripos($l, 'error') !== false);
    if (!empty($errors)) {
        foreach (array_slice($errors, -10) as $line) {
            echo "  " . trim($line) . "\n";
        }
    } else {
        echo "  NO HAY ERRORES RECIENTES\n";
    }
}
