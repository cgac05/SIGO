<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Get columns of Solicitudes
$cols = DB::select('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = \'Solicitudes\' ORDER BY ORDINAL_POSITION');

echo "Columnas de tabla Solicitudes:\n";
foreach ($cols as $c) {
    echo "  - " . $c->COLUMN_NAME . "\n";
}

// Get sample data to see structure
$sample = DB::selectOne('SELECT TOP 1 * FROM Solicitudes');
if ($sample) {
    echo "\nDatos de ejemplo (primer registro):\n";
    $obj = (array)$sample;
    foreach ($obj as $key => $val) {
        echo "  $key = " . substr((string)$val, 0, 50) . "\n";
    }
}
?>
