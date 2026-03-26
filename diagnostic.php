<?php
// Quick DB Diagnostic Script
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSIS DE LA BASE DE DATOS ===\n\n";

// 1. List all tables
echo "1. TABLAS EN LA BASE DE DATOS:\n";
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME");
foreach ($tables as $table) {
    echo "   - {$table->TABLE_NAME}\n";
}

echo "\n2. ESTRUCTURA DE LA TABLA 'Apoyos':\n";
$columns = DB::select("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Apoyos' ORDER BY ORDINAL_POSITION");
foreach ($columns as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
    echo "   - {$col->COLUMN_NAME} ({$col->DATA_TYPE}) {$nullable}\n";
}

echo "\n3. CONTENIDO DE LA TABLA 'Apoyos' (primeros 5):\n";
$apoyos = DB::table('Apoyos')->select('id_apoyo', 'nombre_apoyo', 'tipo_apoyo', 'fecha_inicio', 'fecha_fin')->limit(5)->get();
foreach ($apoyos as $apoyo) {
    echo "   ID: {$apoyo->id_apoyo}, Nombre: {$apoyo->nombre_apoyo}, Tipo: {$apoyo->tipo_apoyo}\n";
    echo "       Fecha Inicio: {$apoyo->fecha_inicio}, Fecha Fin: {$apoyo->fecha_fin}\n";
}

// 4. Check if Comments table exists
echo "\n4. BÚSQUEDA DE TABLAS DE COMENTARIOS:\n";
$commentTables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%omenta%' OR TABLE_NAME LIKE '%Comments%'");
if (count($commentTables) > 0) {
    foreach ($commentTables as $table) {
        echo "   - {$table->TABLE_NAME}\n";
    }
} else {
    echo "   ⚠ NO SE ENCONTRARON TABLAS DE COMENTARIOS\n";
}

// 5. Check related tables
echo "\n5. TABLAS RELACIONADAS CON APOYOS:\n";
$related = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME LIKE '%Apoyo%' OR TABLE_NAME LIKE '%apoyo%'");
foreach ($related as $table) {
    echo "   - {$table->TABLE_NAME}\n";
}

echo "\n";
