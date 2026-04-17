<?php
echo "════════════════════════════════════════════════════════════\n";
echo "🔐 VERIFICAR Y ACTUALIZAR GOOGLE CLIENT SECRET\n";
echo "════════════════════════════════════════════════════════════\n\n";

$envFile = '.env';
$envContent = file_get_contents($envFile);

// Extraer valores actuales
preg_match('/GOOGLE_CLIENT_ID=(.+)/', $envContent, $clientIdMatch);
preg_match('/GOOGLE_CLIENT_SECRET=(.+)/', $envContent, $secretMatch);
preg_match('/GOOGLE_REDIRECT_URI=(.+)/', $envContent, $redirectMatch);

$currentClientId = trim($clientIdMatch[1] ?? '');
$currentSecret = trim($secretMatch[1] ?? '');
$currentRedirect = trim($redirectMatch[1] ?? '');

echo "📊 VALORES ACTUALES EN .env\n";
echo "───────────────────────────────────────────────────────────\n";
echo "CLIENT_ID: $currentClientId\n";
echo "CLIENT_SECRET: " . substr($currentSecret, 0, 10) . "..." . substr($currentSecret, -5) . "\n";
echo "REDIRECT_URI: $currentRedirect\n\n";

echo "════════════════════════════════════════════════════════════\n";
echo "📋 NECESITAMOS TU NUEVO GOOGLE CLIENT SECRET\n";
echo "════════════════════════════════════════════════════════════\n\n";

echo "INSTRUCCIONES PARA OBTENERLO:\n";
echo "1. Abre: https://console.cloud.google.com/apis/credentials\n";
echo "2. Busca y haz click en: OAuth 2.0 Client IDs\n";
echo "3. Busca el con ID: 523344188732-...\n";
echo "4. Haz click para ver detalles\n";
echo "5. Busca el campo 'Client secret' \n";
echo "6. Si no lo ves, haz click en el ojo (Show)\n";
echo "7. COPIA EL VALOR COMPLETO\n\n";

echo "Pegarlo aquí (todo lo que copiastes desde Google):\n";
echo ">>> ";

$newSecret = trim(fgets(STDIN));

if (empty($newSecret)) {
    echo "\n❌ No ingresaste nada. Abortado.\n";
    exit(1);
}

if (strlen($newSecret) < 10) {
    echo "\n❌ El secret parece muy corto. Verifica que copiaste correctamente.\n";
    exit(1);
}

echo "\n✅ Nuevo secret ingresado (primeros 20 chars): " . substr($newSecret, 0, 20) . "...\n\n";

// Actualizar .env
$newContent = preg_replace(
    '/GOOGLE_CLIENT_SECRET=.+/',
    'GOOGLE_CLIENT_SECRET=' . $newSecret,
    $envContent
);

file_put_contents($envFile, $newContent);

echo "════════════════════════════════════════════════════════════\n";
echo "✅ ACTUALIZACIÓN COMPLETADA\n";
echo "════════════════════════════════════════════════════════════\n\n";

echo "Cliente ID: $currentClientId\n";
echo "Secret (nuevo): " . substr($newSecret, 0, 10) . "..." . substr($newSecret, -5) . "\n";
echo "Redirect: $currentRedirect\n\n";

echo "📋 PRÓXIMOS PASOS:\n";
echo "1. Ejecuta: php reset_and_reauth_oauth.php\n";
echo "2. Abre el link en tu navegador\n";
echo "3. Completa la autenticación\n";
echo "4. Ejecuta: php diagnose_oauth_complete.php\n\n";

echo "✅ ¡Listo!\n";
