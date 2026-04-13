<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Check existing users to see valid tipo_usuario values
$users = DB::select("SELECT DISTINCT tipo_usuario FROM Usuarios ORDER BY tipo_usuario");

echo "Valores válidos de tipo_usuario existentes:\n";
foreach ($users as $user) {
    echo "  - " . $user->tipo_usuario . "\n";
}

// Also get constraint info
$constraints = DB::select("
    SELECT CONSTRAINT_DEFINITION 
    FROM INFORMATION_SCHEMA.CHECK_CONSTRAINTS 
    WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'tipo_usuario'
");

if ($constraints) {
    echo "\nConstraint definition:\n";
    foreach ($constraints as $c) {
        echo "  " . $c->CONSTRAINT_DEFINITION . "\n";
    }
}
?>
