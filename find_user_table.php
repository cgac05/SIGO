<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ENCONTRANDO TABLA DE USUARIOS ===\n";
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'BD_SIGO' ORDER BY TABLE_NAME");

echo "Tablas disponibles (buscando usuarios):\n";
foreach ($tables as $table) {
    $name = $table->TABLE_NAME;
    if (stripos($name, 'user') !== false || 
        stripos($name, 'personal') !== false || 
        stripos($name, 'admin') !== false ||
        stripos($name, 'directivo') !== false) {
        echo "  ✓ {$name}\n";
    }
}

echo "\n=== BUSCAR USUARIO DIRECTIVO ===\n";

// Intentar con diferentes nombres de tabla
$tableNames = ['Users', 'users', 'personal_directivos', 'usuarios', 'Usuarios', 'Personal_Directivos'];
foreach ($tableNames as $table) {
    try {
        $user = DB::table($table)
            ->where('email', 'directivo@test.com')
            ->first();
        
        if ($user) {
            echo "✓ Encontrado en tabla: {$table}\n";
            echo json_encode((array)$user, JSON_PRETTY_PRINT) . "\n";
            break;
        }
    } catch (\Exception $e) {
        // Ignorar
    }
}
