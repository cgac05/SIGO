<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICAR COLUMNA cuv ===\n\n";

// Ver estructura de columna cuv
$columns = DB::select(
    "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
     FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE TABLE_NAME = 'Solicitudes' AND COLUMN_NAME = 'cuv' AND TABLE_SCHEMA = 'BD_SIGO'"
);

if (count($columns) > 0) {
    $col = $columns[0];
    echo "Columna encontrada:\n";
    echo "  - Nombre: {$col->COLUMN_NAME}\n";
    echo "  - Tipo: {$col->DATA_TYPE}\n";
    echo "  - Tamaño máximo: {$col->CHARACTER_MAXIMUM_LENGTH} caracteres\n\n";
    
    // El SHA-256 tiene 64 caracteres
    $tamanoCUV = 64;
    echo "Tamaño necesario para SHA-256: {$tamanoCUV} caracteres\n";
    
    if ($col->CHARACTER_MAXIMUM_LENGTH < $tamanoCUV) {
        echo "\n⚠️ La columna es muy pequeña ({$col->CHARACTER_MAXIMUM_LENGTH} < {$tamanoCUV})\n";
        echo "\nAmpliando columna a 255 caracteres...\n";
        
        try {
            DB::statement("ALTER TABLE Solicitudes ALTER COLUMN cuv NVARCHAR(255)");
            echo "✓ Columna ampliada exitosamente\n";
        } catch (\Exception $e) {
            echo "Error al ampliar: " . $e->getMessage() . "\n";
        }
    } else {
        echo "✓ Columna tiene tamaño suficiente\n";
    }
} else {
    echo "❌ Columna 'cuv' no encontrada\n";
}
