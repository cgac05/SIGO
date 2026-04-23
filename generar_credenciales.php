<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

echo "=== GENERANDO CONTRASEÑAS SEGURAS ===\n\n";

// Generar contraseñas seguras
$passwordDirectivo = 'DirectivoSIGO@2026!' . substr(str_shuffle('0123456789'), 0, 3);
$passwordAdmin = 'AdminSIGO@2026!' . substr(str_shuffle('0123456789'), 0, 3);

echo "Contraseñas generadas:\n";
echo "  Directivo: $passwordDirectivo\n";
echo "  Admin: $passwordAdmin\n\n";

// Hashear contraseñas
$hashDirectivo = Hash::make($passwordDirectivo);
$hashAdmin = Hash::make($passwordAdmin);

echo "=== ACTUALIZANDO EN BASE DE DATOS ===\n\n";

// Actualizar directivo@test.local (ID: 17)
$updated1 = DB::table('Usuarios')
    ->where('id_usuario', 17)
    ->update([
        'password_hash' => $hashDirectivo,
        'debe_cambiar_password' => 0
    ]);

echo "1. Usuario directivo@test.local (ID: 17)\n";
if ($updated1) {
    echo "   ✓ Contraseña actualizada\n";
    echo "   Email: directivo@test.local\n";
    echo "   Contraseña: $passwordDirectivo\n\n";
} else {
    echo "   ✗ Error al actualizar\n\n";
}

// Actualizar admin@injuve.gob.mx (ID: 6)
$updated2 = DB::table('Usuarios')
    ->where('id_usuario', 6)
    ->update([
        'password_hash' => $hashAdmin,
        'debe_cambiar_password' => 0
    ]);

echo "2. Usuario admin@injuve.gob.mx (ID: 6)\n";
if ($updated2) {
    echo "   ✓ Contraseña actualizada\n";
    echo "   Email: admin@injuve.gob.mx\n";
    echo "   Contraseña: $passwordAdmin\n\n";
} else {
    echo "   ✗ Error al actualizar\n\n";
}

echo "=== CREDENCIALES PARA LOGIN ===\n\n";

echo "┌─────────────────────────────────────────┐\n";
echo "│ DIRECTIVO                               │\n";
echo "├─────────────────────────────────────────┤\n";
echo "│ Email: directivo@test.local             │\n";
echo "│ Contraseña: $passwordDirectivo         │\n";
echo "└─────────────────────────────────────────┘\n\n";

echo "┌─────────────────────────────────────────┐\n";
echo "│ ADMINISTRATIVO                          │\n";
echo "├─────────────────────────────────────────┤\n";
echo "│ Email: admin@injuve.gob.mx              │\n";
echo "│ Contraseña: $passwordAdmin              │\n";
echo "└─────────────────────────────────────────┘\n";

echo "\n✓ ¡Credenciales listas para usar!\n";
?>
