<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$curps = DB::select("SELECT DISTINCT TOP 5 curp FROM Beneficiarios WHERE curp IS NOT NULL ORDER BY curp");

echo "CURPs disponibles en Beneficiarios:\n";
foreach ($curps as $row) {
    echo "  - " . $row->curp . "\n";
}
?>
