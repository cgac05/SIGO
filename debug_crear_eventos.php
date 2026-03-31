<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\DB;

echo "🔍 Modo Debug: Replicando exactamente lo que hace crearEventosApoyo...\n\n";

$service = app(GoogleCalendarService::class);

// Habilitar query log para ver exactamente qué está ocurriendo
DB::enableQueryLog();

// Capturar output con buffer
ob_start();

try {
    $resultado = $service->crearEventosApoyo(24);
} catch (\Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    $resultado = [];
}

$output = ob_get_clean();
if ($output) {
    echo "📝 Output del método:\n$output\n";
}

echo "\n✅ Resultado:\n";
print_r($resultado);

echo "\n📊 Queries ejecutadas:\n";
$logs = DB::getQueryLog();
echo "Total: " . count($logs) . " queries\n";
foreach ($logs as $i => $query) {
    if (stripos($query['query'], 'INSERT') !== false) {
        echo "  {$i}. INSERT: " . substr($query['query'], 0, 80) . "...\n";
    }
}
