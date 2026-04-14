<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR COLUMNAS DE SOLICITUDES ===\n\n";

// 1. Ver lista de columnas
$columns = DB::select(
    "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'Solicitudes' AND TABLE_SCHEMA = 'BD_SIGO'
     ORDER BY ORDINAL_POSITION"
);

echo "Columnas en Solicitudes:\n";
$columnNames = [];
foreach ($columns as $col) {
    echo "  - {$col->COLUMN_NAME}\n";
    $columnNames[] = strtolower($col->COLUMN_NAME);
}

// 2. Verificar timestamp columns
echo "\n=== BUSCANDO COLUMNAS DE TIMESTAMP ===\n";
$timestamps = ['fecha_actualizacion', 'updated_at', 'fecha_modificacion', 'fecha_creacion'];

foreach ($timestamps as $ts) {
    $exists = in_array(strtolower($ts), $columnNames);
    echo ($exists ? "✓" : "✗") . " {$ts}\n";
}
