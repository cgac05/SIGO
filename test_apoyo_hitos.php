<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;
use Illuminate\Support\Facades\DB;

echo "🎯 Testing Apoyo::with('hitos')->findOrFail(24)\n\n";

echo "📝 Habilitando query log...\n";
DB::enableQueryLog();

try {
    // Exactamente como lo hace crearEventosApoyo()
    $apoyo = Apoyo::with('hitos')->findOrFail(24);
    
    echo "✅ Apoyo obtenido: " . $apoyo->nombre_apoyo . "\n";
    echo "   Hitos: " . count($apoyo->hitos) . "\n";
    foreach ($apoyo->hitos as $h) {
        echo "     - {$h->nombre_hito}\n";
    }
    
    echo "\n📊 Queries:\n";
    $logs = DB::getQueryLog();
    foreach ($logs as $i => $query) {
        echo "   {$i}. " . $query['query'] . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\n📊 Queries hasta el error:\n";
    $logs = DB::getQueryLog();
    foreach ($logs as $i => $query) {
        echo "   {$i}. " . $query['query'] . "\n";
    }
}
