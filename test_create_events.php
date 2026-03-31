<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;
use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\DB;

echo "🎯 Probando creación de eventos para Apoyo #24...\n\n";

// Verificar apoyo
$apoyo = DB::table('Apoyos')->find(24);
if ($apoyo) {
    echo "✅ Apoyo encontrado: {$apoyo->nombre_apoyo}\n";
    $hitos = DB::table('hitos_apoyo')->where('fk_id_apoyo', 24)->get();
    echo "   Hitos: " . count($hitos) . "\n";
    foreach ($hitos as $h) {
        echo "     - {$h->nombre_hito} (ID: {$h->id_hito})\n";
    }
} else {
    echo "❌ Apoyo no encontrado\n";
}

echo "\n📝 Permisos activos:\n";
$permisos = DB::table('directivos_calendario_permisos')->where('activo', 1)->get();
foreach ($permisos as $p) {
    echo "  - Directivo {$p->fk_id_directivo}, Email: {$p->email_directivo}\n";
}

echo "\n🔄 Intentando crear eventos...\n";
$service = app(GoogleCalendarService::class);

try {
    $resultado = $service->crearEventosApoyo(24);
    echo "✅ Eventos creados exitosamente!\n";
    print_r($resultado);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Código heredado de erro:\n";
    echo get_class($e) . "\n";
}
