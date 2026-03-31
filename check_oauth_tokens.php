<?php
require 'bootstrap/app.php';

use App\Models\DirectivoCalendarioPermiso;

$app = require 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "📋 Estado de Tokens OAuth\n";
echo "=========================\n\n";

$directives = DirectivoCalendarioPermiso::all();

if ($directives->isEmpty()) {
    echo "❌ No hay registros de permisos de calendario\n";
    exit;
}

foreach ($directives as $d) {
    echo "Directivo: {$d->email} (ID: {$d->fk_id_directivo})\n";
    echo "  Access token:  " . (strlen($d->access_token ?? '') > 0 ? "✅ Presente" : "❌ Vacío") . "\n";
    echo "  Refresh token: " . (strlen($d->refresh_token ?? '') > 0 ? "✅ Presente" : "❌ Vacío") . "\n";
    echo "  Expires at: " . ($d->expires_at ?? "SIN DATA") . "\n";
    echo "  Activo: " . ($d->activo == 1 ? "✅" : "❌") . "\n";
    echo "\n";
}
?>
