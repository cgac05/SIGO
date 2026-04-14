<?php
require 'vendor/autoload.php';
use Illuminate\Database\Capsule\Manager as DB;

$config = require 'config/database.php';
$db = new DB;
$db->addConnection($config['connections']['sqlsrv']);
$db->bootEloquent();

$columns = DB::selectOne("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Solicitudes'
    ORDER BY ORDINAL_POSITION
");

$result = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Solicitudes'
    ORDER BY ORDINAL_POSITION
");

echo "=== SOLICITUDES COLUMNS ===\n";
foreach ($result as $col) {
    echo "{$col->COLUMN_NAME} ({$col->DATA_TYPE}) - Nullable: {$col->IS_NULLABLE}\n";
}
