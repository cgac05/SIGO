<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECK: fk_curp column definition ===\n\n";

$results = DB::select("
    SELECT 
        COLUMN_NAME, 
        DATA_TYPE, 
        IS_NULLABLE, 
        COLUMN_DEFAULT,
        CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Solicitudes' 
    AND TABLE_SCHEMA = 'dbo'
    AND COLUMN_NAME IN ('fk_curp', 'beneficiario_id', 'folio')
");

foreach ($results as $col) {
    echo "Column: {$col->COLUMN_NAME}\n";
    echo "  Type: {$col->DATA_TYPE}";
    if ($col->CHARACTER_MAXIMUM_LENGTH) {
        echo "({$col->CHARACTER_MAXIMUM_LENGTH})";
    }
    echo "\n";
    echo "  Nullable: {$col->IS_NULLABLE}\n";
    echo "  Default: {$col->COLUMN_DEFAULT}\n\n";
}

echo "\n=== CHECK: Foreign Keys ===\n\n";

$fks = DB::select("
    SELECT 
        CONSTRAINT_NAME,
        TABLE_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS rc
    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
        ON rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
    WHERE rc.TABLE_NAME = 'Solicitudes'
    AND rc.CONSTRAINT_SCHEMA = 'dbo'
");

foreach ($fks as $fk) {
    echo "FK: {$fk->CONSTRAINT_NAME}\n";
    echo "  Column: {$fk->COLUMN_NAME}\n";
    echo "  References: {$fk->REFERENCED_TABLE_NAME}({$fk->REFERENCED_COLUMN_NAME})\n\n";
}
?>
