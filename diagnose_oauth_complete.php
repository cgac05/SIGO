<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "════════════════════════════════════════════════════════════\n";
echo "🔍 DIAGNÓSTICO COMPLETO DE GOOGLE OAUTH\n";
echo "════════════════════════════════════════════════════════════\n\n";

$permiso = \App\Models\DirectivoCalendarioPermiso::find(7);

if (!$permiso) {
    echo "❌ No se encontró permiso ID 7\n";
    exit(1);
}

echo "📊 ESTADO ACTUAL\n";
echo "───────────────────────────────────────────────────────────\n";
echo "Email: {$permiso->email_directivo}\n";
echo "Activo: " . ($permiso->activo ? 'SÍ ✅' : 'NO ❌') . "\n";
echo "Token Expira: {$permiso->token_expiracion}\n";
echo "Expirado: " . (now() > $permiso->token_expiracion ? 'SÍ ❌' : 'NO ✅') . "\n";
echo "Última Sincronización: " . ($permiso->ultima_sincronizacion ? $permiso->ultima_sincronizacion->format('d/m/Y H:i:s') : 'NUNCA') . "\n\n";

// TEST 1: Access Token
echo "TEST 1: Access Token\n";
echo "───────────────────────────────────────────────────────────\n";
try {
    $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
    $hasRefreshInAccess = isset($tokenCompleto['refresh_token']) && !empty($tokenCompleto['refresh_token']);
    echo "✅ Desencriptado correctamente\n";
    echo "   Estructura: " . implode(', ', array_keys($tokenCompleto)) . "\n";
    echo "   refresh_token en access_token: " . ($hasRefreshInAccess ? 'SÍ' : 'NO') . "\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}

echo "\n";

// TEST 2: Refresh Token
echo "TEST 2: Refresh Token Field\n";
echo "───────────────────────────────────────────────────────────\n";
if (!$permiso->google_refresh_token) {
    echo "❌ Campo google_refresh_token es NULL en BD\n";
} else {
    try {
        $refreshDecrypted = decrypt($permiso->google_refresh_token);
        if (empty($refreshDecrypted)) {
            echo "❌ Refresh Token VACÍO después de desencriptar\n";
            echo "   → Google probablemente NO devolvió refresh_token en OAuth\n";
        } else {
            echo "✅ Refresh Token válido\n";
            echo "   Longitud: " . strlen($refreshDecrypted) . " chars\n";
            echo "   Primeros 30 chars: " . substr($refreshDecrypted, 0, 30) . "...\n";
        }
    } catch (\Exception $e) {
        echo "❌ Error desencriptando: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// TEST 3: Comprobar logs
echo "TEST 3: Revisar Logs Recientes\n";
echo "───────────────────────────────────────────────────────────\n";
$logFile = storage_path('logs/laravel.log');
$logs = file_get_contents($logFile);
$lines = explode("\n", $logs);
$recentLogs = array_slice($lines, -100);
$googleLogs = array_filter($recentLogs, function($line) {
    return stripos($line, 'refresh') !== false || stripos($line, 'oauth') !== false;
});

if (!empty($googleLogs)) {
    echo "Últimas menciones de refresh_token:\n";
    foreach (array_slice($googleLogs, -5) as $log) {
        echo "  " . trim($log) . "\n";
    }
} else {
    echo "No hay logs recientes sobre refresh_token\n";
}

echo "\n";

// DIAGNÓSTICO FINAL
echo "════════════════════════════════════════════════════════════\n";
echo "🎯 DIAGNÓSTICO FINAL\n";
echo "════════════════════════════════════════════════════════════\n\n";

try {
    $refreshDecrypted = decrypt($permiso->google_refresh_token);
    $refreshOk = !empty($refreshDecrypted);
} catch (\Exception $e) {
    $refreshOk = false;
}

if ($refreshOk) {
    echo "✅ TODO OK\n";
    echo "   El refresh_token está válido\n";
    echo "   La sincronización debería funcionar\n";
} else {
    echo "❌ PROBLEMA DETECTADO\n";
    echo "\n   Google NO devolvió un refresh_token válido\n";
    echo "   Esto ocurre cuando el usuario ya autorizó la app\n\n";
    echo "📋 SOLUCIÓN:\n";
    echo "   1. Abre: https://myaccount.google.com/permissions\n";
    echo "   2. Busca tu app (SIGO o Localhost 8000)\n";
    echo "   3. Click en ella\n";
    echo "   4. Click en 'Remove Access'\n";
    echo "   5. Ejecuta: php reset_and_reauth_oauth.php\n";
    echo "   6. Abre el link en el navegador\n";
    echo "   7. Click 'Permitir'\n\n";
    echo "   Después ejecuta este script de nuevo\n";
}

echo "\n";
