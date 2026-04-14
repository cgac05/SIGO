<?php

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VERIFICACIÓN ESTRUCTURA presupuesto_apoyos (Azure) ===\n\n";

try {
    // 1. ¿Existe la tabla?
    $existe = Schema::hasTable('presupuesto_apoyos');
    echo "✅ Tabla existe: " . ($existe ? "SÍ" : "NO") . "\n";

    if ($existe) {
        // 2. Obtener columnas
        echo "\n📋 COLUMNAS:\n";
        $columns = Schema::getColumns('presupuesto_apoyos');
        foreach ($columns as $col) {
            echo "  - {$col['name']}: {$col['type']}" . (isset($col['nullable']) && $col['nullable'] ? " (nullable)" : "") . "\n";
        }
        
        // 3. Intentar obtener estructura via SQL directo
        echo "\n🔍 ESTRUCTURA via SQL:\n";
        $sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
                FROM INFORMATION_SCHEMA.COLUMNS 
                WHERE TABLE_NAME = 'presupuesto_apoyos' 
                ORDER BY ORDINAL_POSITION";
        $result = DB::select($sql);
        
        foreach ($result as $row) {
            $nullable = $row->IS_NULLABLE === 'YES' ? ' (NULL)' : '';
            echo "  - {$row->COLUMN_NAME}: {$row->DATA_TYPE}{$nullable}\n";
        }
    }
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n✅ BÚSQUEDA COMPLETADA\n";
