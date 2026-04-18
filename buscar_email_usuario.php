<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BUSCANDO EMAIL EN TABLA USUARIOS/PERSONAL ===\n\n";

// El beneficiario tiene fk_id_usuario = 1
$usuario = DB::table('Usuarios')
    ->where('id_usuario', 1)
    ->first();

if ($usuario) {
    echo "✓ Usuario encontrado en tabla Usuarios:\n";
    $props = (array)$usuario;
    foreach ($props as $key => $value) {
        echo "  $key: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "✗ No encontrado en Usuarios\n";
}

echo "\n=== BUSCANDO EN TABLA PERSONAL ===\n";

$personal = DB::table('Personal')
    ->where('id_personal', 1)
    ->first();

if ($personal) {
    echo "✓ Encontrado en tabla Personal:\n";
    $props = (array)$personal;
    foreach ($props as $key => $value) {
        echo "  $key: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "✗ No encontrado en Personal\n";
}

// Mostrar todas las columnas email disponibles en la BD
echo "\n=== BUSCANDO TODAS LAS COLUMNAS 'EMAIL' EN LA BD ===\n";
$emailColumns = DB::select("
    SELECT TABLE_NAME, COLUMN_NAME
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE COLUMN_NAME LIKE '%email%' OR COLUMN_NAME LIKE '%correo%'
    ORDER BY TABLE_NAME
");

if (count($emailColumns) > 0) {
    foreach ($emailColumns as $col) {
        echo "  Tabla: " . $col->TABLE_NAME . " -> Columna: " . $col->COLUMN_NAME . "\n";
    }
} else {
    echo "  No se encontraron columnas con 'email' o 'correo'.\n";
}
?>
