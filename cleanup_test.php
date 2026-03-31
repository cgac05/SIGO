<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;

$count = Apoyo::where('nombre_apoyo', 'like', '%PRUEBA%')->delete();
echo "✅ Eliminados {$count} apoyos de prueba\n";
