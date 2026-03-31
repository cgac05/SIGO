<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Get solicitudes with states
$solicitudes = \DB::select("
    SELECT TOP 20 
        s.folio,
        s.fk_curp,
        s.fk_id_estado,
        s.fecha_creacion,
        s.fecha_actualizacion,
        s.observaciones_internas,
        a.nombre_apoyo as apoyo_nombre
    FROM Solicitudes s
    LEFT JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
    ORDER BY s.fecha_creacion DESC
");

echo "=== SOLICITUDES DATA ===\n";
foreach ($solicitudes as $sol) {
    echo json_encode((array)$sol, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Get distinct estados
$estados = \DB::select("
    SELECT DISTINCT fk_id_estado FROM Solicitudes WHERE fk_id_estado IS NOT NULL
");

echo "\n=== DISTINCT ESTADOS ===\n";
foreach ($estados as $estado) {
    echo "Estado ID: " . $estado->fk_id_estado . "\n";
}

// Check all tables for estado reference
$tables = \DB::select("
    SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_NAME LIKE '%[Ee]stado%'
");

echo "\n=== TABLES WITH 'ESTADO' IN NAME ===\n";
if (empty($tables)) {
    echo "No tables found with 'estado' in name\n";
} else {
    foreach ($tables as $table) {
        echo $table->TABLE_NAME . "\n";
    }
}

// Get states from Cat_EstadosSolicitud
$estadosData = \DB::select("SELECT * FROM Cat_EstadosSolicitud ORDER BY id_estado");
echo "\n=== CAT_ESTADOSSOLICITUD CONTENT ===\n";
foreach ($estadosData as $estado) {
    echo json_encode((array)$estado, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
}

// Get table structure
$schema = \DB::select("
    SELECT COLUMN_NAME, DATA_TYPE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Solicitudes' 
    ORDER BY ORDINAL_POSITION
");

echo "\n=== SOLICITUDES TABLE STRUCTURE ===\n";
foreach ($schema as $col) {
    echo $col->COLUMN_NAME . " (" . $col->DATA_TYPE . ")\n";
}
