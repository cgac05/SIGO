<?php
require 'vendor/autoload.php';

use App\Models\Estado;

$app = require_once __DIR__ . '/bootstrap/app.php';

\Illuminate\Support\Facades\DB::connection()->getPdo();

$estados = \Illuminate\Support\Facades\DB::table('Cat_EstadosSolicitud')->get();

echo "=== Estados disponibles en BD ===\n";
foreach ($estados as $estado) {
    echo "ID {$estado->id_estado}: {$estado->nombre_estado}\n";
}
