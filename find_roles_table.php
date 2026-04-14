<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TODAS LAS TABLAS EN BD_SIGO ===\n";
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'BD_SIGO' ORDER BY TABLE_NAME");

$filtered = [];
foreach ($tables as $table) {
    $name = $table->TABLE_NAME;
    // Buscar tablas relacionadas a usuarios, roles, personal
    if (stripos($name, 'usuario') !== false || 
        stripos($name, 'rol') !== false || 
        stripos($name, 'personal') !== false ||
        stripos($name, 'directivo') !== false ||
        stripos($name, 'cat_') === 0) {
        $filtered[] = $name;
    }
}

if (count($filtered) > 0) {
    foreach ($filtered as $name) {
        echo "  {$name}\n";
    }
}

// Buscar columnascols para la tabla usuarios
echo "\n=== ESTRUCTURA: usuarios ===\n";
$cols = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'usuarios' ORDER BY ORDINAL_POSITION");
foreach ($cols as $col) {
    echo "  {$col->COLUMN_NAME} ({$col->DATA_TYPE})\n";
}

// Ver el modelo User en Laravel
echo "\n=== MODELO USER EN LARAVEL ===\n";
echo "Buscando archivo app/Models/User.php...\n";
if (file_exists('app/Models/User.php')) {
    echo "✓ Existe\n";
}
