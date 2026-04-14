<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Check if Personal table has directivos
$personalColums = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Personal' ORDER BY ORDINAL_POSITION");

echo "Estructura de tabla Personal:\n";
if ($personalColums) {
    foreach ($personalColums as $col) {
        echo "  - " . $col->COLUMN_NAME . "\n";
    }
} else {
    echo "  No existe tabla Personal\n";
}

// Check Directivos table
$directivosColumns = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Directivos' ORDER BY ORDINAL_POSITION");

echo "\nEstructura de tabla Directivos:\n";
if ($directivosColumns) {
    foreach ($directivosColumns as $col) {
        echo "  - " . $col->COLUMN_NAME . "\n";
    }
} else {
    echo "  No existe tabla Directivos\n";
}

// Check existing Users in Personal table
$users = DB::select("SELECT TOP 3 id_usuario, email, nombre FROM Personal");
echo "\nEjemplos de Personal:\n";
foreach ($users as $u) {
    echo "  - ID: " . $u->id_usuario . " | Email: " . $u->email . " | Nombre: " . $u->nombre . "\n";
}
?>
