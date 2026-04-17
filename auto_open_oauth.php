<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;
use App\Models\OAuthState;
use Illuminate\Support\Facades\DB;

echo "════════════════════════════════════════════════════════════\n";
echo "🔐 RE-AUTENTICACIÓN GOOGLE CALENDAR OAUTH\n";
echo "════════════════════════════════════════════════════════════\n\n";

try {
    $service = app(GoogleCalendarService::class);
    
    // Generar state único
    $directivo_id = 6;
    $state = base64_encode(random_bytes(32));
    
    // Guardar state en BD
    DB::table('oauth_states')->insert([
        'state' => $state,
        'directivo_id' => $directivo_id,
        'created_at' => DB::raw('GETDATE()'),
        'expires_at' => DB::raw('DATEADD(MINUTE, 15, GETDATE())'),
    ]);
    
    // Generar URL
    $authUrl = $service->generarUrlAutenticacion($state);
    
    echo "✅ State generado y guardado en BD\n";
    echo "✅ Vehí abriendo el navegador...\n\n";
    
    // Mostrar URL para copiar
    echo "📋 Si el navegador NO se abre, copia este link:\n";
    echo "────────────────────────────────────────────────────────────\n";
    echo $authUrl . "\n";
    echo "────────────────────────────────────────────────────────────\n\n";
    
    // Intentar abrir en Windows
    $osType = strtoupper(substr(PHP_OS, 0, 3));
    if ($osType === 'WIN') {
        // Windows
        $cmd = "start " . escapeshellarg($authUrl);
        shell_exec($cmd);
        echo "🌐 El navegador debería abrir en 2 segundos...\n";
    } elseif ($osType === 'DAR') {
        // macOS
        shell_exec("open " . escapeshellarg($authUrl));
        echo "🌐 El navegador debería abrir en 2 segundos...\n";
    } else {
        // Linux/Otros
        shell_exec("xdg-open " . escapeshellarg($authUrl));
        echo "🌐 Intenta abrir en tu navegador...\n";
    }
    
    echo "\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "📌 INSTRUCCIONES:\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "1. Inicia sesión con: guillermoavilamora2@gmail.com\n";
    echo "2. Haz click en 'Permitir' cuando Google pida permisos\n";
    echo "3. Serás redirigido a: http://localhost:8000/admin/calendario\n";
    echo "4. ✅ Listo! Los tokens se actualizar automáticamente\n\n";
    
    echo "⏱️  Estado: VÁLIDO por 15 minutos\n";
    echo "⚠️  Si el link expira, ejecuta este script de nuevo\n\n";
    
    // Verificar estado actual
    $permiso = \App\Models\DirectivoCalendarioPermiso::find(7);
    echo "════════════════════════════════════════════════════════════\n";
    echo "📊 ESTADO ACTUAL DE TOKENS\n";
    echo "════════════════════════════════════════════════════════════\n";
    echo "Directivo: {$permiso->email_directivo}\n";
    echo "Access Token Expirado: " . (now() > $permiso->token_expiracion ? 'SÍ ❌' : 'NO ✅') . "\n";
    echo "Refresh Token: " . ($permiso->google_refresh_token ? 'Existe' : 'VACÍO ❌') . "\n";
    echo "Activo: " . ($permiso->activo ? 'SÍ' : 'NO') . "\n";
    echo "Última Sincronización: " . ($permiso->ultima_sincronizacion ? $permiso->ultima_sincronizacion->format('d/m/Y H:i:s') : 'NUNCA') . "\n";
    echo "\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
