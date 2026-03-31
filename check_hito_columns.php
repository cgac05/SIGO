<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📊 Columnas en tabla hitos_apoyo:\n";
$result = DB::connection()->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hitos_apoyo'");
foreach ($result as $col) {
    echo "  - " . $col->COLUMN_NAME . "\n";
}
?>
