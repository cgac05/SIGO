<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

echo "🔍 Checker refresh token encriptado...\n\n";

$perm = DB::table('directivos_calendario_permisos')->where('id_permiso', 7)->first();
if ($perm) {
    echo "google_refresh_token (encriptado):\n";
    echo "  Primeros 50 chars: " . substr($perm->google_refresh_token, 0, 50) . "\n";
    echo "  Total length: " . strlen($perm->google_refresh_token) . "\n\n";
    
    echo "Intentando decrypt...\n";
    try {
        $refreshToken = Crypt::decrypt($perm->google_refresh_token);
        echo "  ✅ Decrypted token: " . substr($refreshToken, 0, 50) . "...\n";
    } catch (\Exception $e) {
        echo "  ❌ Error al decrypt: " . $e->getMessage() . "\n";
    }
} else {
    echo "No encontrado\n";
}
