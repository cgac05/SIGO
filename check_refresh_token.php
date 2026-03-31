<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "🔍 Verificando tokens guardados...\n\n";

$perm = DB::table('directivos_calendario_permisos')->where('id_permiso', 7)->first();
if ($perm) {
    echo "Permiso ID 7:\n";
    echo "  google_refresh_token: " . ($perm->google_refresh_token ? "SÍ (" . strlen($perm->google_refresh_token) . " chars)" : "NULL") . "\n";
    echo "  token_expiracion: " . ($perm->token_expiracion ?? 'NULL') . "\n";
    echo "  google_access_token: " . (strlen($perm->google_access_token) . " chars") . "\n";
} else {
    echo "Permiso no encontrado\n";
}
