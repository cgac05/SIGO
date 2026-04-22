<?php
// Script para verificar apoyos disponibles
require 'vendor/autoload.php';
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\Apoyo;

$apoyos = Apoyo::select('id_apoyo', 'nombre_apoyo')->limit(3)->get();
foreach($apoyos as $a) {
    echo "ID: {$a->id_apoyo} - {$a->nombre_apoyo}\n";
}
