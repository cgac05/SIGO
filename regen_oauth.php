<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\DB;

echo "🔄 Regenerando token de Google OAuth...\n\n";

// PASO 1: Generar URL de autenticación
$service = app(GoogleCalendarService::class);

// Generar state
$state = "6_" . bin2hex(random_bytes(16));
echo "1️⃣  State generado: $state\n";

// Guardar state en BD
DB::table('oauth_states')->insert([
    'state' => $state,
    'directivo_id' => 6,
    'created_at' => DB::raw('GETDATE()'),
    'expires_at' => DB::raw('DATEADD(MINUTE, 10, GETDATE())'),
]);
echo "   State guardado en BD\n";

// Generar URL
try {
    $authUrl = $service->generarUrlAutenticacion($state);
    echo "\n2️⃣  URL de autenticación generada:\n";
    echo "   " . substr($authUrl, 0, 100) . "...\n";
    echo "   LONGITUD: " . strlen($authUrl) . "\n";
    
    // Para testing, vamos a obtener el auth_code manualmente 
    // En producción, el usuario haría click y sería redirigido
    echo "\n3️⃣  NOTA: Necesitas abrir esta URL en tu navegador y hacer clic en 'Permitir'\n";
    echo "   URL: $authUrl\n";
    
    // Luego de que el usuario haga clic y sea redirigido,recibiremos:
    // http://localhost:8000/admin/calendario/callback?code=XXXXX&state=$state
    // 
    // Para automatizar, usaré este auth_code de prueba (puede no funcionar después de expirar):
    // PERO lo que realmente necesitamos es que el usuario haga click en el navegador.
    
    echo "\n⚠️  El sistema necesita que hagas click en la URL anterior\n";
    echo "   para completar la autenticación de Google.\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
