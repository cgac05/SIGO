<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "=== Searching for politica/retencion tables ===\n";
$tables = DB::select('SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = "BASE TABLE" ORDER BY TABLE_NAME');
foreach($tables as $t) {
    if(stripos($t->TABLE_NAME, 'politica') !== false || stripos($t->TABLE_NAME, 'retencion') !== false) {
        echo $t->TABLE_NAME . "\n";
    }
}

echo "\n=== All tables in database ===\n";
foreach($tables as $t) {
    echo $t->TABLE_NAME . "\n";
}
?>
