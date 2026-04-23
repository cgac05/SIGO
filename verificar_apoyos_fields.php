<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA TABLA Apoyos ===\n\n";

$columns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Apoyos'
    ORDER BY ORDINAL_POSITION
");

echo "Columnas:\n";
foreach ($columns as $col) {
    echo "  - " . $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ") Nullable: " . $col->IS_NULLABLE . "\n";
}

echo "\n=== DATOS DE EJEMPLO (Apoyo ID 1) ===\n";

$apoyo = DB::select("SELECT TOP 1 * FROM Apoyos WHERE id_apoyo = 1");
if (count($apoyo) > 0) {
    $props = (array)$apoyo[0];
    foreach ($props as $k => $v) {
        echo "$k: " . ($v ?? 'NULL') . "\n";
    }
} else {
    echo "Buscando cualquier apoyo...\n";
    $apoyo = DB::select("SELECT TOP 1 * FROM Apoyos");
    if (count($apoyo) > 0) {
        $props = (array)$apoyo[0];
        foreach ($props as $k => $v) {
            echo "$k: " . ($v ?? 'NULL') . "\n";
        }
    }
}

echo "\n";
?>
