<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TODAS LAS COLUMNAS DE Solicitudes ===\n";

$columns = DB::select(
    "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'Solicitudes' AND TABLE_SCHEMA = 'BD_SIGO'
     ORDER BY ORDINAL_POSITION"
);

if (count($columns) > 0) {
    foreach ($columns as $col) {
        $size = $col->CHARACTER_MAXIMUM_LENGTH ?? 'N/A';
        echo "  - {$col->COLUMN_NAME} ({$col->DATA_TYPE}, tamaño: {$size})\n";
    }
} else {
    echo "No se encontraron columnas (posible problema de conexión)\n";
}

echo "\n=== DATOS ACTUALES DE FOLIO 1008 ===\n";
$solicitud = DB::table('Solicitudes')->where('folio', 1008)->first();
if ($solicitud) {
    echo "CUV actual: " . ($solicitud->cuv ?? 'NULL') . "\n";
    echo "Estado: {$solicitud->fk_id_estado}\n";
}
