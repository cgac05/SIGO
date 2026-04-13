<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Get columns of Cat_Roles
$cols = DB::select('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'Cat_Roles\' ORDER BY ORDINAL_POSITION');

echo "Columnas de Cat_Roles:\n";
foreach ($cols as $c) {
    echo "  - " . $c->COLUMN_NAME . "\n";
}

// Get sample data
$roles = DB::select('SELECT * FROM Cat_Roles');
echo "\nRoles disponibles (".count($roles)."):\n";
foreach ($roles as $r) {
    $obj = (array)$r;
    echo "  - ID: " . ($obj['id_rol'] ?? '?') . " | Nombre: " . ($obj['rol_nombre'] ?? $obj['nombre_rol'] ?? '?') . "\n";
}
?>
