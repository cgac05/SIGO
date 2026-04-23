<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA TABLA presupuesto_apoyos ===\n\n";

// Verificar estructura
$columns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'presupuesto_apoyos'
    ORDER BY ORDINAL_POSITION
");

echo "Columnas:\n";
foreach ($columns as $col) {
    echo "  - " . $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ") Nullable: " . $col->IS_NULLABLE . "\n";
}

echo "\n=== DATOS DE EJEMPLO ===\n";

$data = DB::select("SELECT TOP 5 * FROM presupuesto_apoyos");
foreach ($data as $row) {
    echo "\nRegistro:\n";
    $props = (array)$row;
    foreach ($props as $k => $v) {
        echo "  $k: " . ($v ?? 'NULL') . "\n";
    }
}

echo "\n";
?>
