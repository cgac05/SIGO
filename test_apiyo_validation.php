<?php
/**
 * Script para probar la validación de apoyos
 */

// Obtener CSRF token primero
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/apoyos/create");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');

$response = curl_exec($ch);
curl_close($ch);

// Extraer CSRF token
preg_match('/<meta name="csrf-token" content="([^"]+)"/', $response, $matches);
$token = $matches[1] ?? null;

echo "CSRF Token: " . ($token ? '✅ Encontrado' : '❌ No encontrado') . "\n";

if (!$token) {
    echo "❌ No se pudo obtener el token CSRF\n";
    exit(1);
}

// Hacer POST en blanco
echo "\n📤 Enviando formulario en blanco...\n";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://127.0.0.1:8000/apoyos");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, 'cookies.txt');
curl_setopt($ch, CURLOPT_COOKIEJAR, 'cookies.txt');
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "X-Requested-With: XMLHttpRequest",
    "X-CSRF-TOKEN: $token"
]);

// Datos vacíos
$data = [
    '_token' => $token,
];

curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $httpCode\n";
echo "Response:\n";
echo $response . "\n";

// Parsear JSON
$json = json_decode($response, true);
if ($json) {
    echo "\n✅ Respuesta JSON:\n";
    echo "  Success: " . ($json['success'] ? 'true' : 'false') . "\n";
    echo "  Message: " . ($json['message'] ?? 'N/A') . "\n";
    if (!empty($json['errors'])) {
        echo "  Errors:\n";
        foreach ($json['errors'] as $field => $messages) {
            echo "    - $field: " . implode(', ', $messages) . "\n";
        }
    }
} else {
    echo "\n❌ Respuesta no es JSON\n";
}
?>
