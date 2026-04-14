<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver todas las tablas
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'BD_SIGO' ORDER BY TABLE_NAME");

echo "=== TODAS LAS TABLAS ===\n";
foreach ($tables as $table) {
    echo "{$table->TABLE_NAME}\n";
}

echo "\n=== TABLA Personal (relacionada a usuarios) ===\n";
if (count($tables) > 0) {
    $cols = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Personal' ORDER BY ORDINAL_POSITION");
    
    if (count($cols) === 0) {
        echo "Tabla Personal no existe o está vacía\n";
        
        // Buscar variantes
        $variants = ['personal', 'PERSONAL', 'Personals', 'personals'];
        foreach ($variants as $variant) {
            $cols2 = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '{$variant}' ORDER BY ORDINAL_POSITION");
            if (count($cols2) > 0) {
                echo "✓ Encontrada como: {$variant}\n";
                break;
            }
        }
    } else {
        echo "Columnas en Personal:\n";
        foreach ($cols as $col) {
            echo "  {$col->COLUMN_NAME} ({$col->DATA_TYPE})\n";
        }
        
        // Ver usuario 21 en Personal
        echo "\nDatos de personal para usuario 21:\n";
        $personal = DB::table('Personal')->where('fk_id_usuario', 21)->first();
        if ($personal) {
            echo json_encode((array)$personal, JSON_PRETTY_PRINT) . "\n";
        } else {
            echo "No encontrado\n";
        }
    }
}
