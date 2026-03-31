<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📊 Verificando fechas de hitos del Apoyo 24:\n\n";

$hitos = DB::table('hitos_apoyo')->where('fk_id_apoyo', 24)->get([
    'id_hito', 'nombre_hito', 'fecha_hito_aproximada', 'fecha_inicio'
]);

foreach ($hitos as $h) {
    echo "ID {$h->id_hito}: {$h->nombre_hito}\n";
    echo "  fecha_hito_aproximada: " . ($h->fecha_hito_aproximada ?? 'NULL') . "\n";
    echo "  fecha_inicio: " . ($h->fecha_inicio ?? 'NULL') . "\n\n";
}
