<?php

require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n============================================\n";
echo "  SCHEMA DE DOCUMENTOS_EXPEDIENTE\n";
echo "============================================\n\n";

$columns = DB::connection('sqlsrv')->select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Documentos_Expediente'
    ORDER BY ORDINAL_POSITION
");

foreach ($columns as $col) {
    echo sprintf(
        "%-25s | %-20s | Nullable: %s\n", 
        $col->COLUMN_NAME, 
        $col->DATA_TYPE, 
        $col->IS_NULLABLE
    );
}

echo "\n\nPrimera fila de datos:\n";
$row = DB::table('Documentos_Expediente')->limit(1)->get();
echo json_encode($row, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
