<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\HitosApoyo;
use App\Models\Apoyo;

echo "🔍 Verificando fechas de hitos para Google Calendar Event\n\n";

$apoyo = Apoyo::with('hitos')->findOrFail(24);
echo "Apoyo: {$apoyo->nombre_apoyo}\n\n";

foreach ($apoyo->hitos as $h) {
    echo "Hito: {$h->nombre_hito}\n";
    echo "  fecha_inicio (raw): " . var_export($h->fecha_inicio, true) . "\n";
    echo "  fecha_inicio (type): " . gettype($h->fecha_inicio) . "\n";
    
    if ($h->fecha_inicio) {
        try {
            $dt = $h->fecha_inicio->toDateTime();
            echo "  toDateTime(): " . $dt->format(\DateTime::RFC3339) . "\n";
        } catch (\Exception $e) {
            echo "  toDateTime() ERROR: " . $e->getMessage() . "\n";
        }
    }
    echo "\n";
}
