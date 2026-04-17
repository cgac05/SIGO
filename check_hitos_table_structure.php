<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "📊 ESTRUCTURA DE TABLA hitos_apoyo\n";
echo "═════════════════════════════════════════\n\n";

// Obtener columnas
$columns = DB::connection('sqlsrv')->getSchemaBuilder()->getColumnListing('hitos_apoyo');

echo "Columnas disponibles:\n";
echo "─────────────────────────────────────────\n";
foreach ($columns as $col) {
    echo "  - $col\n";
}

echo "\n";

// Buscar columnas relacionadas con calendario/sincronización
echo "Columnas relacionadas con sincronización:\n";
echo "─────────────────────────────────────────\n";
$syncCols = array_filter($columns, function($col) {
    return stripos($col, 'calendario') !== false || stripos($col, 'sincron') !== false || stripos($col, 'google') !== false;
});

if (!empty($syncCols)) {
    foreach ($syncCols as $col) {
        echo "  ✅ $col\n";
    }
} else {
    echo "  ❌ No hay columnas de sincronización\n";
}

echo "\n";

// Ver estructura completa
echo "ESTRUCTURA DETALLADA:\n";
echo "─────────────────────────────────────────\n";
$table = DB::connection('sqlsrv')->selectOne("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'hitos_apoyo'
    ORDER BY ORDINAL_POSITION
");

// Usar query directa
$result = DB::connection('sqlsrv')->select("
    SELECT TOP 20 COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'hitos_apoyo'
    ORDER BY ORDINAL_POSITION
");

foreach ($result as $row) {
    $nullable = $row->IS_NULLABLE === 'YES' ? '(nullable)' : '(required)';
    echo "  {$row->COLUMN_NAME}: {$row->DATA_TYPE} $nullable\n";
}
