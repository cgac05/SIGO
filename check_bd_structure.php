<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

$app = app();

echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 ANÁLISIS DE ESTRUCTURA BD - SIGO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// 1. LISTAR TODAS LAS TABLAS
echo "✅ TABLAS EN BD_SIGO:\n";
$tables = DB::select("
    SELECT TABLE_NAME 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_CATALOG = 'BD_SIGO' 
    AND TABLE_TYPE = 'BASE TABLE' 
    ORDER BY TABLE_NAME
");

foreach ($tables as $table) {
    $count = DB::table($table->TABLE_NAME)->count();
    echo "   • {$table->TABLE_NAME} ({$count} registros)\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📋 ESTRUCTURA DE TABLA: Solicitudes\n";
echo "═══════════════════════════════════════════════════════════════\n";

$solicitudes_cols = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Solicitudes'
    ORDER BY ORDINAL_POSITION
");

foreach ($solicitudes_cols as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
    echo "   • {$col->COLUMN_NAME}: {$col->DATA_TYPE} | {$nullable}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📋 ESTRUCTURA DE TABLA: Documentos_Expediente\n";
echo "═══════════════════════════════════════════════════════════════\n";

$docs_cols = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Documentos_Expediente'
    ORDER BY ORDINAL_POSITION
");

foreach ($docs_cols as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
    echo "   • {$col->COLUMN_NAME}: {$col->DATA_TYPE} | {$nullable}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📋 ESTRUCTURA DE TABLA: Apoyos\n";
echo "═══════════════════════════════════════════════════════════════\n";

$apoyos_cols = DB::select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Apoyos'
    ORDER BY ORDINAL_POSITION
");

foreach ($apoyos_cols as $col) {
    $nullable = $col->IS_NULLABLE === 'YES' ? 'NULL' : 'NOT NULL';
    echo "   • {$col->COLUMN_NAME}: {$col->DATA_TYPE} | {$nullable}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🔗 FOREIGN KEYS EN Solicitudes\n";
echo "═══════════════════════════════════════════════════════════════\n";

$fks = DB::select("
    SELECT 
        CONSTRAINT_NAME,
        COLUMN_NAME,
        REFERENCED_TABLE_NAME,
        REFERENCED_COLUMN_NAME
    FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
    WHERE TABLE_NAME = 'Solicitudes'
");

if (empty($fks)) {
    echo "   (No FKs encontradas en tabla directa - revisar por nombre)\n";
    
    $fks2 = DB::select("
        SELECT 
            CONSTRAINT_NAME,
            TABLE_NAME,
            COLUMN_NAME
        FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
        WHERE TABLE_NAME = 'Solicitudes'
        AND REFERENCED_TABLE_NAME IS NOT NULL
    ");
    
    foreach ($fks2 as $fk) {
        echo "   • {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME}\n";
    }
} else {
    foreach ($fks as $fk) {
        echo "   • {$fk->CONSTRAINT_NAME}: {$fk->COLUMN_NAME}\n";
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📝 ESTADOS EN Cat_EstadosSolicitud\n";
echo "═══════════════════════════════════════════════════════════════\n";

$estados = DB::select("SELECT * FROM Cat_EstadosSolicitud");
foreach ($estados as $estado) {
    echo "   • ID {$estado->id_estado}: {$estado->nombre_estado}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ ANÁLISIS COMPLETADO\n";
echo "═══════════════════════════════════════════════════════════════\n";
