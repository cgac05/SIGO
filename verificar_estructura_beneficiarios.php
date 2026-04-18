<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE TABLA BENEFICIARIOS ===\n\n";

// Get table columns
$columns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Beneficiarios'
    ORDER BY ORDINAL_POSITION
");

foreach ($columns as $col) {
    echo $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ") - Nullable: " . $col->IS_NULLABLE . "\n";
}

echo "\n=== DATOS ACTUALES DEL BENEFICIARIO ===\n\n";

$beneficiario = DB::table('Beneficiarios')
    ->where('curp', 'AICC050509HNTVMHA5')
    ->first();

if ($beneficiario) {
    $propiedades = (array)$beneficiario;
    foreach ($propiedades as $key => $value) {
        echo "$key: " . ($value ?? 'NULL') . "\n";
    }
} else {
    echo "Beneficiario no encontrado\n";
}
?>
