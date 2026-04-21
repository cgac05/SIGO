<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECK: Beneficiarios table structure ===\n\n";

$results = DB::select("
    SELECT 
        COLUMN_NAME, 
        DATA_TYPE, 
        IS_NULLABLE, 
        COLUMN_DEFAULT,
        CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Beneficiarios' 
    AND TABLE_SCHEMA = 'dbo'
    ORDER BY ORDINAL_POSITION
");

foreach ($results as $col) {
    echo sprintf(
        "%-25s | %-20s | Nullable: %-3s | Len: %-4s | Default: %s\n",
        $col->COLUMN_NAME,
        $col->DATA_TYPE,
        $col->IS_NULLABLE,
        $col->CHARACTER_MAXIMUM_LENGTH ?? 'N/A',
        $col->COLUMN_DEFAULT ?? 'NULL'
    );
}

echo "\n=== CHECK: Foreign Keys in Beneficiarios ===\n\n";

$fks = DB::select("
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
        ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
        AND rc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
    WHERE rc.TABLE_NAME = 'Beneficiarios'
    AND rc.TABLE_SCHEMA = 'dbo'
");

foreach ($fks as $fk) {
    echo "  {$fk->COLUMN_NAME} → {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})\n";
}
?>
