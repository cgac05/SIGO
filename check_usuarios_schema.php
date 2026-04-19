<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$columns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Usuarios'
    AND TABLE_SCHEMA = 'dbo'
");

echo "Estructura: Usuarios\n";
echo "====================\n\n";

foreach ($columns as $col) {
    echo "$col->COLUMN_NAME | $col->DATA_TYPE\n";
}
?>
