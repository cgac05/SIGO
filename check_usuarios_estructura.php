<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Get table structure
$columns = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' ORDER BY ORDINAL_POSITION");

echo "Estructura de tabla Usuarios:\n";
foreach ($columns as $col) {
    echo "  - " . $col->COLUMN_NAME . "\n";
}
?>
