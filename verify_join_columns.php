<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

echo "=== Movimientos Presupuestarios Columns ===\n";
$cols = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'movimientos_presupuestarios' ORDER BY ORDINAL_POSITION");
foreach ($cols as $col) {
    echo "- " . $col->COLUMN_NAME . "\n";
}

echo "\n=== Presupuesto Apoyos Columns ===\n";
$cols = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'presupuesto_apoyos' ORDER BY ORDINAL_POSITION");
foreach ($cols as $col) {
    echo "- " . $col->COLUMN_NAME . "\n";
}

echo "\n=== Testing Join (PresupuestoController.php line 231) ===\n";
try {
    $result = DB::table('movimientos_presupuestarios as mov')
        ->join('presupuesto_apoyos as pa', 'mov.id_apoyo_presupuesto', '=', 'pa.id_apoyo_presupuesto')
        ->where('pa.id_categoria', 1)
        ->select([
            'mov.id_movimiento',
            'mov.tipo_movimiento',
            'mov.monto',
            'mov.created_at',
        ])
        ->limit(1)
        ->toSql();
    echo "✓ Join SQL (corrected): $result\n";
} catch (\Exception $e) {
    echo "✗ Join Error: " . $e->getMessage() . "\n";
}

echo "\n=== Checking OLD column references (should NOT exist) ===\n";
$oldCols = ['id_presupuesto_apoyo', 'fecha_movimiento', 'notas'];
foreach ($oldCols as $col) {
    $exists = DB::select("SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'movimientos_presupuestarios' AND COLUMN_NAME = '$col'");
    echo ($exists[0]->cnt > 0 ? "✗ EXISTS" : "✓ Missing") . ": $col\n";
}
