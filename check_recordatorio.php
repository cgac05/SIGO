<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;

$apoyo = Apoyo::findOrFail(24);
echo "recordatorio_dias: " . var_export($apoyo->recordatorio_dias, true) . "\n";
echo "Type: " . gettype($apoyo->recordatorio_dias) . "\n";
