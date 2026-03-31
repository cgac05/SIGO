<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

echo "=== Verificando Configuración Google OAuth ===\n";

echo "\n1. Valores en .env:\n";
echo "   CLIENT_ID: " . env('GOOGLE_CLIENT_ID') . "\n";
echo "   CLIENT_SECRET: " . env('GOOGLE_CLIENT_SECRET') . "\n";
echo "   REDIRECT_URI: " . env('GOOGLE_REDIRECT_URI') . "\n";

echo "\n2. Valores desde config/services.php:\n";
echo "   client_id: " . config('services.google.client_id') . "\n";
echo "   client_secret: " . config('services.google.client_secret') . "\n";
echo "   redirect: " . config('services.google.redirect') . "\n";

echo "\n3. Inicializando Google Client:\n";
try {
    $googleClient = new \Google\Client();
    $googleClient->setClientId(config('services.google.client_id'));
    $googleClient->setClientSecret(config('services.google.client_secret'));
    $googleClient->setRedirectUri(config('services.google.redirect'));
    
    echo "   ✅ Google Client inicializado correctamente\n";
    echo "   Client ID en cliente: " . $googleClient->getClientId() . "\n";
    echo "   Redirect URI en cliente: " . $googleClient->getRedirectUri() . "\n";
} catch (\Exception $e) {
    echo "   ❌ Error: " . $e->getMessage() . "\n";
}

echo "\n4. Verificando si hay credenciales en application/x-www-form-urlencoded:\n";
$clientId = config('services.google.client_id');
$clientSecret = config('services.google.client_secret');

if (strpos($clientId, ' ') !== false || strpos($clientSecret, ' ') !== false) {
    echo "   ⚠️ Advertencia: Hay espacios en las credenciales\n";
} else {
    echo "   ✅ Sin espacios extraños\n";
}

// Longitud de credenciales
echo "\n5. Longitud de credenciales:\n";
echo "   CLIENT_ID length: " . strlen($clientId) . "\n";
echo "   CLIENT_SECRET length: " . strlen($clientSecret) . "\n";

echo "\nNota: Si ves '❌' arriba o credenciales vacías, ese es el problema.\n";
