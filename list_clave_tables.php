<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tables = DB::select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'dbo' 
    AND (TABLE_NAME LIKE '%clave%' OR TABLE_NAME LIKE '%Clave%')
");

echo "Tablas con 'clave' en el nombre:\n";
foreach ($tables as $t) {
    echo "  - " . $t->TABLE_NAME . "\n";
}
?>
