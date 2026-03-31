<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';
$db = $app->make('db');

$columns = $db->select("
    SELECT COLUMN_NAME, DATA_TYPE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'calendario_sincronizacion_log' 
    ORDER BY ORDINAL_POSITION
");

echo "Columnas en calendario_sincronizacion_log:\n";
foreach ($columns as $col) {
    echo "  {$col->COLUMN_NAME} ({$col->DATA_TYPE})\n";
}
