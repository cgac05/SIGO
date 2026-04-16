<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Diagnosticando problema de desencriptación de tokens...\n\n";

$permiso = \App\Models\DirectivoCalendarioPermiso::find(7);

echo "Permiso ID 7:\n";
echo "  Email: {$permiso->email_directivo}\n";
echo "  Activo: " . ($permiso->activo ? 'SÍ' : 'NO') . "\n\n";

// ========== TEST 1: Verificar Access Token ==========
echo "TEST 1: Desencriptación de Access Token\n";
echo "─────────────────────────────────────────────\n";
try {
    $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
    echo "✅ Access Token desencriptado correctamente\n";
    if ($tokenCompleto) {
        echo "   - Estructura: " . json_encode(array_keys($tokenCompleto)) . "\n";
        echo "   - access_token longitud: " . strlen($tokenCompleto['access_token'] ?? 'NULL') . " chars\n";
        echo "   - refresh_token: " . ($tokenCompleto['refresh_token'] ?? 'NULL') . "\n";
    }
} catch (\Exception $e) {
    echo "❌ Error desencriptando Access Token:\n";
    echo "   " . $e->getMessage() . "\n";
}

echo "\n";

// ========== TEST 2: Verificar Refresh Token ==========
echo "TEST 2: Desencriptación de Refresh Token\n";
echo "─────────────────────────────────────────────\n";
try {
    $refreshDecrypted = decrypt($permiso->google_refresh_token);
    echo "✅ Refresh Token desencriptado\n";
    echo "   Contenido: " . (empty($refreshDecrypted) ? 'VACÍO/NULL' : 'OK (' . strlen($refreshDecrypted) . ' chars)') . "\n";
    echo "   Primeros 50 chars: " . substr($refreshDecrypted, 0, 50) . "\n";
} catch (\Exception $e) {
    echo "❌ Error desencriptando Refresh Token:\n";
    echo "   " . $e->getMessage() . "\n";
}

echo "\n";

// ========== TEST 3: Verificar APP_KEY ==========
echo "TEST 3: Verificación de APP_KEY\n";
echo "─────────────────────────────────────────────\n";
echo "APP_KEY: " . config('app.key') . "\n";
echo "APP_CIPHER: " . config('app.cipher') . "\n";

echo "\n✅ Fin del diagnóstico.\n";
