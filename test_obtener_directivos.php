<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\DB;

echo "🔍 Testing obtenerDirectivosActivos() via Reflection\n\n";

$service = app(GoogleCalendarService::class);

// Usar reflection para acceder al método privado
$reflection = new ReflectionClass($service);
$method = $reflection->getMethod('obtenerDirectivosActivos');
$method->setAccessible(true);

echo "📝 Llamando al método via Reflection...\n";

try {
    DB::enableQueryLog();
    $directivos = $method->invoke($service);
    
    echo "✅ Exitoso, obtenidos: " . count($directivos) . "\n";
    foreach ($directivos as $d) {
        echo "   - ID: {$d->id_permiso}, Directivo FK: {$d->fk_id_directivo}\n";
        if ($d->directivo) {
            echo "     Email: {$d->directivo->email}\n";
        }
    }
    
    echo "\n📊 Queries ejecutadas:\n";
    $logs = DB::getQueryLog();
    foreach ($logs as $i => $query) {
        echo "   {$i}. " . $query['query'] . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTrace:\n" . $e->getTraceAsString() . "\n";
}
