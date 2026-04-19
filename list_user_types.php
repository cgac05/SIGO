<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$tipos = DB::select("SELECT DISTINCT tipo_usuario FROM Usuarios ORDER BY tipo_usuario");

echo "Tipos de usuario existentes:\n";
foreach ($tipos as $tipo) {
    echo "  - " . $tipo->tipo_usuario . "\n";
}
?>
