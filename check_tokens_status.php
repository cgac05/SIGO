<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "🔍 Verificando estado de tokens OAuth...\n\n";

$permisos = \App\Models\DirectivoCalendarioPermiso::all();
echo "Total permisos en BD: " . count($permisos) . "\n\n";

if (count($permisos) == 0) {
    echo "❌ NO HAY PERMISOS GUARDADOS EN LA BD\n";
    exit(1);
}

foreach ($permisos as $p) {
    $refresh = $p->google_refresh_token ? 'SÍ (' . strlen($p->google_refresh_token) . ' chars)' : 'NULL/VACÍO';
    $access = $p->google_access_token ? 'SÍ (' . strlen($p->google_access_token) . ' chars)' : 'NULL/VACÍO';
    $activo = $p->activo ? 'SÍ' : 'NO';
    
    echo "═══════════════════════════════════════════════════\n";
    echo "ID Permiso: {$p->id_permiso}\n";
    echo "Email: {$p->email_directivo}\n";
    echo "Directivo ID: {$p->fk_id_directivo}\n";
    echo "Activo: $activo\n";
    echo "Access Token: $access\n";
    echo "Refresh Token: $refresh\n";
    echo "Última Sincronización: " . ($p->ultima_sincronizacion ? $p->ultima_sincronizacion->format('Y-m-d H:i:s') : 'NUNCA') . "\n";
    echo "Token Expira: " . ($p->token_expiracion ? $p->token_expiracion->format('Y-m-d H:i:s') : 'DESCONOCIDO') . "\n";
    echo "═══════════════════════════════════════════════════\n\n";
}

echo "\n✅ Verificación completa.\n";
