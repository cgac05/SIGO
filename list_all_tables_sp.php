<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$result = DB::select("EXEC sp_tables");
echo "Primeras 20 tablas disponibles:\n";
$count = 0;
foreach ($result as $table) {
    if ($count++ < 20) {
        echo $table->TABLE_NAME . "\n";
    }
}
?>
