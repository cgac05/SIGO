<?php
$app = require_once __DIR__.'/bootstrap/app.php';
\Illuminate\Foundation\Application::setInstance($app);
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n============================================\n";
echo "  DATABASE SCHEMA - DOCUMENTOS_EXPEDIENTE\n";
echo "============================================\n\n";

// Get all columns
$columns = DB::connection('sqlsrv')->select("
    SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMNPROPERTY(OBJECT_ID('Documentos_Expediente'), COLUMN_NAME, 'IsIdentity') as IsIdentity
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Documentos_Expediente'
    ORDER BY ORDINAL_POSITION
");

echo "COLUMN NAME                    | DATA TYPE            | Nullable | PK?\n";
echo str_repeat("-", 80) . "\n";

foreach ($columns as $col) {
    $isPK = $col->IsIdentity ? "YES*" : "No";
    echo sprintf(
        "%-30s | %-20s | %-8s | %s\n", 
        $col->COLUMN_NAME, 
        $col->DATA_TYPE, 
        $col->IS_NULLABLE,
        $isPK
    );
}

echo "\n* = Primary Key (IDENTITY)\n";
echo "\nFirst row data:\n";
echo str_repeat("=", 80) . "\n";

$row = DB::table('Documentos_Expediente')->limit(1)->get();
if ($row->count() > 0) {
    echo json_encode($row[0], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
} else {
    echo "No hay datos en la tabla.";
}
