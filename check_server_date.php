<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$serverDate = DB::selectOne('SELECT GETDATE() as fecha, CONVERT(datetime2, GETDATE()) as fecha2');
echo "Fecha servidor: " . json_encode($serverDate) . "\n";

$now = \Carbon\Carbon::now();
echo "Fecha local PHP: " . $now->format('Y-m-d H:i:s') . "\n";
?>
