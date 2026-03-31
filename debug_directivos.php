<?php
/**
 * Script para debuggear los directivos
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\DirectivoCalendarioPermiso;

echo "=== DEBUG: Directivos Activos ===\n\n";

$directivos = DirectivoCalendarioPermiso::where('activo', 1)
    ->with('directivo')
    ->get();

echo "Total de directivos activos: " . count($directivos) . "\n\n";

foreach ($directivos as $idx => $permiso) {
    echo "[$idx] Permiso ID: " . $permiso->id_permiso . "\n";
    echo "     Directivo ID: " . $permiso->fk_id_directivo . "\n";
    echo "     Email: " . $permiso->email_directivo . "\n";
    echo "     Token vence pronto: " . ($permiso->tokenVencePronto() ? 'Sí' : 'No') . "\n";
}

echo "\nSi no hay directivos, crear uno...\n";

if (count($directivos) == 0) {
    echo "❌ No hay directivos activos!\n";
    echo "El token probablemente expiró.\n";
}

?>
