<?php

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

echo "=== VERIFICACIÓN TABLA presupuesto_apoyos ===\n\n";

// 1. ¿Existe la tabla?
$existe = Schema::hasTable('presupuesto_apoyos');
echo "✅ Tabla existe: " . ($existe ? "SÍ" : "NO") . "\n";

if ($existe) {
    // 2. Obtener columnas
    echo "\n📋 COLUMNAS:\n";
    $columns = Schema::getColumns('presupuesto_apoyos');
    foreach ($columns as $col) {
        echo "  - {$col['name']}: {$col['type']}\n";
    }
    
    // 3. Verificar datos
    echo "\n📊 DATOS (primeros 3 registros):\n";
    $data = DB::table('presupuesto_apoyos')->limit(3)->get();
    if ($data->count() > 0) {
        foreach ($data as $row) {
            echo json_encode((array)$row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
        }
    } else {
        echo "  (vacía)\n";
    }
    
    // 4. Estructura en SQL
    echo "\n🔧 INDICES:\n";
    $indexes = DB::select("SELECT name, type FROM sys.indexes 
                          WHERE object_id = OBJECT_ID('presupuesto_apoyos')");
    foreach ($indexes as $idx) {
        echo "  - {$idx->name} ({$idx->type})\n";
    }
}

echo "\n✅ VERIFICACIÓN COMPLETADA\n";
