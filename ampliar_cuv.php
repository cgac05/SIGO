<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== AMPLIAR COLUMNA cuv ===\n\n";

// Intentar ampliar la columna a NVARCHAR(100)
try {
    echo "Ampliando columna cuv a NVARCHAR(100)...\n";
    
    DB::statement("ALTER TABLE Solicitudes ALTER COLUMN cuv NVARCHAR(100)");
    
    echo "✓ Columna ampliada exitosamente\n\n";
    
    // Verificar el cambio
    $columns = DB::select(
        "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH 
         FROM INFORMATION_SCHEMA.COLUMNS 
         WHERE TABLE_NAME = 'Solicitudes' AND COLUMN_NAME = 'cuv'"
    );
    
    if (count($columns) > 0) {
        echo "Nueva configuración:\n";
        echo "  - Tipo: {$columns[0]->DATA_TYPE}\n";
        echo "  - Tamaño máximo: {$columns[0]->CHARACTER_MAXIMUM_LENGTH}\n";
    }
    
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    
    // Intentar con ALTER COLUMN más específico
    echo "\nIntentando con ALTER COLUMN...\n";
    try {
        DB::statement("ALTER TABLE [Solicitudes] ALTER COLUMN [cuv] NVARCHAR(100) NULL");
        echo "✓ Columnaamaliada con NULL\n";
    } catch (\Exception $e2) {
        echo "Error: " . $e2->getMessage() . "\n";
    }
}

echo "\n✅ Proceso completado\n";
