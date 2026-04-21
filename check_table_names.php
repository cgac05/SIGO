<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Obtener todas las tablas
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' ORDER BY TABLE_NAME");

echo "Tablas disponibles:\n";
foreach ($tables as $table) {
    if (strpos(strtolower($table->TABLE_NAME), 'clave') !== false || 
        strpos(strtolower($table->TABLE_NAME), 'auditoria') !== false) {
        echo "✓ " . $table->TABLE_NAME . "\n";
    }
}
?>
