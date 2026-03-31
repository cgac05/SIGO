<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$result = \Illuminate\Support\Facades\DB::select('
    SELECT COLUMN_NAME, DATA_TYPE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = \'hitos_apoyo\' 
    AND TABLE_SCHEMA = \'dbo\'
    ORDER BY ORDINAL_POSITION
');

echo "=== Estructura de hitos_apoyo ===\n";
foreach ($result as $col) {
    echo "{$col->COLUMN_NAME} ({$col->DATA_TYPE})\n";
}
