<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE TABLA Hitos_Apoyo ===\n\n";

$columns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Hitos_Apoyo'
    ORDER BY ORDINAL_POSITION
");

foreach ($columns as $col) {
    echo $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ") - Nullable: " . $col->IS_NULLABLE . "\n";
}
?>
