<?php
require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Verificar estructura de tabla Solicitudes
echo "=== Solicitudes ===\n";
$columnas = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Solicitudes' 
    AND COLUMN_NAME IN ('folio', 'fk_curp', 'beneficiario_id')
");

foreach ($columnas as $col) {
    echo $col->COLUMN_NAME . ': ' . $col->DATA_TYPE . "\n";
}

// Verificar estructura de tabla claves_seguimiento_privadas
echo "\n=== claves_seguimiento_privadas ===\n";
$columnas2 = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'claves_seguimiento_privadas' 
    AND COLUMN_NAME IN ('folio', 'beneficiario_id')
");

foreach ($columnas2 as $col) {
    echo $col->COLUMN_NAME . ': ' . $col->DATA_TYPE . "\n";
}

// Obtener un ejemplo de folio
echo "\n=== Ejemplo ===\n";
$ejemplo = DB::table('claves_seguimiento_privadas')->first();
if ($ejemplo) {
    echo 'Folio de clave: ' . $ejemplo->folio . ' (tipo: ' . gettype($ejemplo->folio) . ")\n";
    
    $solicitud = DB::table('Solicitudes')->where('folio', $ejemplo->folio)->first();
    if ($solicitud) {
        echo 'Solicitud encontrada con folio: ' . $solicitud->folio . "\n";
    } else {
        echo 'NO hay Solicitud con folio: ' . $ejemplo->folio . "\n";
        
        // Intentar buscar por diferentes tipos
        $solicitudes = DB::table('Solicitudes')->limit(3)->get();
        echo "\nPrimeros folios en Solicitudes:\n";
        foreach ($solicitudes as $s) {
            echo '- ' . $s->folio . ' (tipo: ' . gettype($s->folio) . ")\n";
        }
    }
} else {
    echo "No hay claves_seguimiento_privadas\n";
}
