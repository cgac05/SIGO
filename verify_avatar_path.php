<?php
require_once __DIR__ . '/bootstrap/app.php';

$user = \App\Models\User::find(5);
if (!$user) {
    die("Usuario no encontrado\n");
}

$fotoUrl = $user->getFotoUrl();
$storagePath = storage_path('app/public/' . $user->foto_ruta);
$publicPath = public_path('storage/' . $user->foto_ruta);

echo "=== VERIFICACIÓN DE RUTA DE AVATAR ===\n";
echo "Usuario: {$user->email}\n";
echo "Foto URL (from getFotoUrl()): $fotoUrl\n";
echo "\nVerificación de archivos:\n";
echo "Storage path exists: " . (file_exists($storagePath) ? '✓ SÍ' : '✗ NO') . "\n";
echo "Public path exists: " . (file_exists($publicPath) ? '✓ SÍ' : '✗ NO') . "\n";
echo "Photo size: " . (file_exists($storagePath) ? filesize($storagePath) . ' bytes' : 'N/A') . "\n";
echo "\nURL should be accessible at:\n";
echo "http://localhost:8000" . $fotoUrl . "\n";
echo "\nHTML img tag:\n";
echo '<img src="' . $fotoUrl . '" alt="Avatar" style="width: 40px; height: 40px; border-radius: 50%;" />' . "\n";
