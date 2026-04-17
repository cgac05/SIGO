<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "════════════════════════════════════════════════════════════\n";
echo "🔍 DIAGNOSTICAR CONFIGURACIÓN DE GOOGLE OAUTH\n";
echo "════════════════════════════════════════════════════════════\n\n";

// 1. Verificar archivos
echo "PASO 1: Archivos Necesarios\n";
echo "───────────────────────────────────────────────────────────\n";

$files = [
    '.env' => '.env existe',
    'config/services.php' => 'Configuración de servicios',
    'app/Services/GoogleCalendarService.php' => 'Servicio de Google Calendar',
    'app/Http/Controllers/GoogleCalendarController.php' => 'Controlador de Google Calendar',
];

foreach ($files as $file => $desc) {
    $exists = file_exists($file);
    echo ($exists ? '✅' : '❌') . " $file ($desc)\n";
}

echo "\n";

// 2. Verificar credenciales en .env
echo "PASO 2: Credenciales en .env\n";
echo "───────────────────────────────────────────────────────────\n";

$clientId = config('services.google.client_id');
$clientSecret = config('services.google.client_secret');
$redirectUri = config('services.google.redirect');
$apiKey = config('services.google.api_key');

echo "CLIENT_ID: " . (substr($clientId, 0, 20) . "..." . substr($clientId, -10)) . "\n";
echo "CLIENT_SECRET: " . (substr($clientSecret, 0, 15) . "..." . substr($clientSecret, -5)) . "\n";
echo "REDIRECT_URI: $redirectUri\n";
echo "API_KEY: " . (substr($apiKey, 0, 15) . "..." . substr($apiKey, -5)) . "\n\n";

// 3. Validaciones
echo "PASO 3: Validaciones\n";
echo "───────────────────────────────────────────────────────────\n";

$issues = [];

if (empty($clientId) || strlen($clientId) < 10) {
    $issues[] = "❌ CLIENT_ID inválido o vacío";
} else {
    echo "✅ CLIENT_ID: OK\n";
}

if (empty($clientSecret) || strlen($clientSecret) < 10) {
    $issues[] = "❌ CLIENT_SECRET inválido o vacío";
} else {
    echo "✅ CLIENT_SECRET: OK (longitud: " . strlen($clientSecret) . ")\n";
}

if (empty($redirectUri)) {
    $issues[] = "❌ REDIRECT_URI vacío";
} else if (!strpos($redirectUri, 'localhost:8000')) {
    $issues[] = "⚠️  REDIRECT_URI no contiene localhost:8000\n   Valor: $redirectUri";
} else {
    echo "✅ REDIRECT_URI: OK\n";
}

if (empty($apiKey) || strlen($apiKey) < 10) {
    $issues[] = "⚠️  API_KEY inválido o vacío (pero esto es opcional)";
}

echo "\n";

// 4. Permisos de BD
echo "PASO 4: Permisos guardados en BD\n";
echo "───────────────────────────────────────────────────────────\n";

$permiso = \App\Models\DirectivoCalendarioPermiso::where('email_directivo', 'guillermoavilamora2@gmail.com')->first();

if ($permiso) {
    echo "✅ Permiso encontrado (ID: {$permiso->id_permiso})\n";
    echo "   Activo: " . ($permiso->activo ? 'SÍ' : 'NO') . "\n";
    echo "   Tokens guardados: " . (!empty($permiso->google_access_token) ? 'SÍ' : 'NO') . "\n";
} else {
    $issues[] = "⚠️  Permiso no encontrado en BD (esto es normal en primera autenticación)";
    echo "⚠️  Permiso no encontrado en BD\n";
}

echo "\n";

// 5. Resumen
echo "════════════════════════════════════════════════════════════\n";
echo "📋 RESUMEN\n";
echo "════════════════════════════════════════════════════════════\n\n";

if (empty($issues)) {
    echo "✅ TODO OK - La configuración parece correcta\n\n";
    echo "Si aún tienes problemas:\n";
    echo "1. Verifica en Google Cloud que el CLIENT_SECRET sea correcto\n";
    echo "2. Ejecuta: php reset_and_reauth_oauth.php\n";
    echo "3. Abre el link en navegador\n";
} else {
    echo "⚠️  PROBLEMAS ENCONTRADOS:\n\n";
    foreach ($issues as $issue) {
        echo $issue . "\n";
    }
    echo "\nAcciones necesarias:\n";
    echo "1. Actualiza .env con valores correctos de Google Cloud Console\n";
    echo "2. Ejecuta: php artisan config:clear\n";
    echo "3. Ejecuta: php reset_and_reauth_oauth.php\n";
}

echo "\n════════════════════════════════════════════════════════════\n";
