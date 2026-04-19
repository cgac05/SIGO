<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECK: claves_seguimiento_privadas structure ===\n\n";

$results = DB::select("
    SELECT 
        COLUMN_NAME, 
        DATA_TYPE, 
        IS_NULLABLE,
        CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'claves_seguimiento_privadas' 
    AND TABLE_SCHEMA = 'dbo'
    ORDER BY ORDINAL_POSITION
");

foreach ($results as $col) {
    echo sprintf(
        "%-25s | %-20s | Nullable: %-3s\n",
        $col->COLUMN_NAME,
        $col->DATA_TYPE,
        $col->IS_NULLABLE
    );
}
?>
