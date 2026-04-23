<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BUSCAR TABLA DE CATEGORÍAS ===\n\n";

// Buscar tablas con "categoria" en el nombre
$tables = DB::select("
    SELECT TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME LIKE '%categ%' OR TABLE_NAME LIKE '%presupuesto%'
    ORDER BY TABLE_NAME
");

echo "Tablas encontradas:\n";
foreach ($tables as $table) {
    echo "  - " . $table->TABLE_NAME . "\n";
}

// Ver estructura de presupuesto_categorias
echo "\n=== ESTRUCTURA: presupuesto_categorias ===\n";
$columns = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'presupuesto_categorias'
    ORDER BY ORDINAL_POSITION
");

if (count($columns) > 0) {
    echo "Columnas:\n";
    foreach ($columns as $col) {
        echo "  - " . $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ") Nullable: " . $col->IS_NULLABLE . "\n";
    }
} else {
    echo "Tabla no encontrada\n";
}

// Ver datos de ejemplo
echo "\n=== DATOS DE EJEMPLO ===\n";
$data = DB::select("SELECT TOP 5 * FROM presupuesto_categorias");
foreach ($data as $row) {
    echo "\nRegistro:\n";
    $props = (array)$row;
    foreach ($props as $k => $v) {
        echo "  $k: " . ($v ?? 'NULL') . "\n";
    }
}

// Ver cómo están relacionadas
echo "\n=== RELACIÓN APOYO - CATEGORÍA ===\n";
$relation = DB::select("
    SELECT TOP 5
        a.id_apoyo,
        a.nombre_apoyo,
        a.id_categoria,
        pc.nombre_categoria,
        pc.monto_disponible
    FROM Apoyos a
    LEFT JOIN presupuesto_categorias pc ON a.id_categoria = pc.id_categoria
    WHERE a.activo = 1
");

foreach ($relation as $row) {
    echo "Apoyo: " . $row->nombre_apoyo . "\n";
    echo "  - ID Categoría: " . $row->id_categoria . "\n";
    echo "  - Nombre Categoría: " . ($row->nombre_categoria ?? 'SIN CATEGORÍA') . "\n";
    echo "\n";
}
?>
