<?php

require_once 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECKING FIRMAS_ELECTRONICAS TABLE STRUCTURE ===\n\n";

// 1. Check if table exists
$tableExists = DB::select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_NAME = 'firmas_electronicas' AND TABLE_SCHEMA = 'dbo'
");

if (empty($tableExists)) {
    echo "❌ TABLE DOES NOT EXIST! Need to create firmas_electronicas table.\n";
    exit(1);
}

echo "✅ Table firmas_electronicas exists\n\n";

// 2. Get all columns
echo "Columns in firmas_electronicas:\n";
echo "================================\n";

$columns = DB::select("
    SELECT 
        COLUMN_NAME,
        DATA_TYPE,
        IS_NULLABLE,
        COLUMN_DEFAULT,
        CHARACTER_MAXIMUM_LENGTH
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'firmas_electronicas' 
    ORDER BY ORDINAL_POSITION
");

foreach ($columns as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
    echo "  • {$col->COLUMN_NAME}: {$col->DATA_TYPE}({$col->CHARACTER_MAXIMUM_LENGTH}) {$nullable}\n";
}

echo "\n";

// 3. Check for specific columns mentioned in the error
$requiredColumns = ['folio_solicitud', 'tipo_firma', 'estado', 'cuv', 'fecha_firma'];
echo "Checking for required columns:\n";
echo "================================\n";

foreach ($requiredColumns as $colName) {
    $exists = DB::select("
        SELECT 1 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'firmas_electronicas' AND COLUMN_NAME = ?
    ", [$colName]);
    
    echo ($exists ? "✅" : "❌") . " {$colName}\n";
}

echo "\nSample data from table:\n";
echo "=======================\n";

$data = DB::table('firmas_electronicas')->limit(3)->get();
echo "Row count: " . DB::table('firmas_electronicas')->count() . "\n";
echo "Sample rows: " . count($data) . "\n";
if (count($data) > 0) {
    echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}
