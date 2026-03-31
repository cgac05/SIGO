<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DirectivoCalendarioPermiso;
use Illuminate\Support\Facades\DB;

echo "🔍 Probando relación con 'with()'\n\n";

echo "1️⃣ Sin with(''):\n";
$permisos = DirectivoCalendarioPermiso::where('activo', 1)->get();
echo "   Obtenidos: " . count($permisos) . "\n";
foreach ($permisos as $p) {
    echo "   - ID Permiso: {$p->id_permiso}, FK Directivo: {$p->fk_id_directivo}\n";
}

echo "\n2️⃣ Intentando with('directivo'):\n";
try {
    $permisos = DirectivoCalendarioPermiso::where('activo', 1)->with('directivo')->get();
    echo "   ✅ Exitoso\n";
    foreach ($permisos as $p) {
        if ($p->directivo) {
            echo "   - Directivo: {$p->directivo->email}\n";
        }
    }
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n3️⃣ Query log para with():\n";
DB::enableQueryLog();
DirectivoCalendarioPermiso::with('directivo')->get();
foreach (DB::getQueryLog() as $query) {
    echo "   " . $query['query'] . "\n";
}
