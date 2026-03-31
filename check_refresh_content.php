<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Crypt;

echo "🔍 Verificar contenido del refresh token...\n\n";

$perm = DB::table('directivos_calendario_permisos')->where('id_permiso', 7)->first();
if ($perm) {
    try {
        $refreshToken = Crypt::decrypt($perm->google_refresh_token);
        echo "Token decryptado:\n";
        echo "  Full content: '" . $refreshToken . "'\n";
        echo "  Length: " . strlen($refreshToken) . "\n";
        echo "  Is empty: " . ($refreshToken === '' ? 'YES' : 'NO') . "\n";
        
        if (!empty($refreshToken)) {
            echo "  First 100 chars: " . substr($refreshToken, 0, 100) . "\n";
        }
    } catch (\Exception $e) {
        echo "Error decrypt: " . $e->getMessage() . "\n";
    }
} else {
    echo "No encontrado\n";
}
?>
