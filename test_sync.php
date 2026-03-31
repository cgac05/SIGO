<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;

echo "🔄 Intentando sincronizar para directivo_id=6...\n\n";

$service = app(GoogleCalendarService::class);

try {
    $resultado = $service->sincronizarDesdeGoogle(6);
    echo "✅ Sincronización exitosa!\n";
    echo "Resultado:\n";
    print_r($resultado);
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "Trace:\n" . $e->getTraceAsString() . "\n";
}
