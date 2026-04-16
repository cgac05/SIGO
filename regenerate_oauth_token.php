<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;
use App\Models\OAuthState;
use Illuminate\Support\Facades\DB;

echo "🔄 Re-autenticación de Google Calendar OAuth\n";
echo "═══════════════════════════════════════════════════\n\n";

try {
    $service = app(GoogleCalendarService::class);
    
    // Generar state único
    $directivo_id = 6; // El que se va a autenticar
    $state = base64_encode(random_bytes(32));
    
    // Guardar state en BD con expiración
    DB::table('oauth_states')->insert([
        'state' => $state,
        'directivo_id' => $directivo_id,
        'created_at' => DB::raw('GETDATE()'),
        'expires_at' => DB::raw('DATEADD(MINUTE, 15, GETDATE())'),
    ]);
    
    echo "✅ State generado: $state\n";
    echo "✅ State guardado en BD (válido por 15 minutos)\n\n";
    
    // Generar URL de autenticación
    $authUrl = $service->generarUrlAutenticacion($state);
    
    echo "📋 Pasos de re-autenticación:\n";
    echo "─────────────────────────────────────────────────\n";
    echo "1. Abre este enlace en el navegador:\n";
    echo "\n$authUrl\n\n";
    echo "2. Inicia sesión con: guillermoavilamora2@gmail.com\n";
    echo "3. Permite que SIGO acceda a Google Calendar\n";
    echo "4. Serás redirigido a: http://localhost:8000/admin/calendario/callback\n";
    echo "5. Los tokens se refrescarán automáticamente\n\n";
    
    echo "⚠️  IMPORTANTE: Este enlace expira en 15 minutos\n\n";
    
    // Mostrar estado actual
    $permiso = \App\Models\DirectivoCalendarioPermiso::find(7);
    echo "Estado actual de tokens:\n";
    echo "─────────────────────────────────────────────────\n";
    echo "Email: {$permiso->email_directivo}\n";
    echo "Access Token Expirado: " . (now() > $permiso->token_expiracion ? 'SÍ ❌' : 'NO ✅') . "\n";
    echo "Refresh Token: " . ($permiso->google_refresh_token ? 'Existe' : 'FALTA ❌') . "\n";
    echo "Última Sincronización: " . ($permiso->ultima_sincronizacion ? $permiso->ultima_sincronizacion->format('d/m/Y H:i') : 'NUNCA') . "\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
