<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Ver todas las tablas
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'BD_SIGO' ORDER BY TABLE_NAME");

echo "Total de tablas: " . count($tables) . "\n\n";

// Mostrar solo los nombres
foreach ($tables as $table) {
    $name = $table->TABLE_NAME;
    
    // Filtrar las que importan
    if (stripos($name, 'personal') !== false || 
        stripos($name, 'rol') !== false ||
        stripos($name, 'usuario') !== false) {
        echo "✓ {$name}\n";
    }
}
