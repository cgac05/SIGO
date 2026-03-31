<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\DB;

echo "🎯 Testing crearEventosApoyo(24) - COMPLETE FLOW\n\n";

$service = app(GoogleCalendarService::class);

echo "📝 Habilitando query log...\n";
DB::enableQueryLog();

try {
    $resultado = $service->crearEventosApoyo(24);
    
    echo "✅ Éxito!\n";
    echo "Resultado:\n";
    print_r($resultado);
    
    echo "\n📊 Queries ejecutadas:\n";
    $logs = DB::getQueryLog();
    foreach ($logs as $i => $query) {
        echo "   {$i}. " . substr($query['query'], 0, 100) . "...\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nTipo: " . get_class($e) . "\n";
    echo "\nCode: " . $e->getCode() . "\n";
}
