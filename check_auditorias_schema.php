<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select("
    SELECT COLUMN_NAME, IS_NULLABLE, DATA_TYPE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'auditorias_carga_material'
    AND TABLE_SCHEMA = 'dbo'
");

echo "Estructura: auditorias_carga_material\n";
echo "======================================\n\n";

foreach ($columns as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? '✓ NULLABLE' : '✗ NOT NULL';
    echo "$col->COLUMN_NAME | $col->DATA_TYPE | $nullable\n";
}
?>
