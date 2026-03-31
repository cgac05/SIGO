<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📊 Columnas en tabla Usuarios:\n";
$resultado = DB::connection()->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios'");
foreach ($resultado as $col) {
    foreach ((array)$col as $valor) {
        if (is_string($valor)) {
            echo "  - $valor\n";
        }
    }
}

echo "\n📊 Una fila de Usuarios:\n";
$usuarios = DB::table('Usuarios')->limit(1)->get();
foreach ($usuarios as $u) {
    echo json_encode($u) . "\n";
}
