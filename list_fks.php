<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$fks = DB::select("
    SELECT CONSTRAINT_NAME
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_NAME = 'claves_seguimiento_privadas'
    AND CONSTRAINT_TYPE = 'FOREIGN KEY'
");

echo "Foreign Keys en claves_seguimiento_privadas:\n";
foreach ($fks as $fk) {
    echo "  - " . $fk->CONSTRAINT_NAME . "\n";
}
?>
