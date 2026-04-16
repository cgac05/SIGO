<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "📊 ESTADO DE EVENTOS EN BD\n";
echo "═════════════════════════════════════════\n\n";

// Contar eventos sin sincronizar
$hitos = \App\Models\HitosApoyo::where('sincronizar_calendario', 1)->count();
$conGoogle = \App\Models\HitosApoyo::whereNotNull('google_calendar_event_id')->count();

echo "Total hitos para sincronizar: $hitos\n";
echo "Con Google Event ID: $conGoogle\n";
echo "Sin Google Event ID (PENDIENTES): " . ($hitos - $conGoogle) . "\n\n";

// Ver últimos hitos
echo "Últimos 5 hitos creados:\n";
echo "─────────────────────────────────────────\n";
$ultimos = \App\Models\HitosApoyo::latest('id_hito')->limit(5)->get();
foreach ($ultimos as $h) {
    $apoyo = $h->apoyo;
    $googleId = $h->google_calendar_event_id ?? 'NULL';
    $sincronizar = $h->sincronizar_calendario ?? 'NULL';
    echo "ID: {$h->id_hito} | Nombre: {$h->nombre_hito} | Apoyo: {$apoyo->nombre_apoyo} | Google ID: $googleId | Sincronizar: $sincronizar\n";
}

echo "\n";

// Verificar permisos de directivo
echo "PERMISOS DE DIRECTIVO\n";
echo "─────────────────────────────────────────\n";
$permiso = \App\Models\DirectivoCalendarioPermiso::where('email_directivo', 'guillermoavilamora2@gmail.com')->first();
if ($permiso) {
    echo "Activo: " . ($permiso->activo ? 'SÍ ✅' : 'NO ❌') . "\n";
    echo "Access Token: " . ($permiso->google_access_token ? 'Guardado ✅' : 'NULL ❌') . "\n";
    echo "Refresh Token: " . (!empty(decrypt($permiso->google_refresh_token)) ? 'Válido ✅' : 'VACÍO ❌') . "\n";
} else {
    echo "❌ Permiso no encontrado\n";
}

echo "\n";

// Ver síncronización log
echo "ÚLTIMOS INTENTOS DE SINCRONIZACIÓN\n";
echo "─────────────────────────────────────────\n";
$logs = \App\Models\CalendarioSincronizacionLog::latest('fecha_cambio')->limit(10)->get();
if ($logs->count() > 0) {
    foreach ($logs as $log) {
        echo "{$log->fecha_cambio} | Tipo: {$log->tipo_cambio} | Origen: {$log->origen_cambio} | Estado: " . (isset($log->resultado) ? json_encode($log->resultado) : 'OK') . "\n";
    }
} else {
    echo "Sin registros de sincronización\n";
}
