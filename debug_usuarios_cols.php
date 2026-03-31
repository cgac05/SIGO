<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all Usuarios - first check what columns exist
$columnsRef = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios'");
echo "=== Table Usuarios Columns ===\n";
foreach($columnsRef as $c) {
    echo "- " . $c->COLUMN_NAME . "\n";
}

// Get all Usuarios with their IDs and emails
echo "\n=== All Usuarios in BD ===\n";
$usuarios = DB::select('SELECT TOP 30 id_usuario, correo_electronico FROM Usuarios ORDER BY id_usuario'); 

foreach($usuarios as $u) {
    // Check if has calendar permission
    $perm = DB::selectOne(
        'SELECT id_permiso, activo FROM directivos_calendario_permisos WHERE fk_id_directivo = ?', 
        [$u->id_usuario]
    );
    
    echo "ID: " . $u->id_usuario . " | Email: " . $u->correo_electronico . " | Calendar Perm: " . ($perm ? 'YES' : 'NO');
    if ($perm) {
        echo " | Activo: " . ($perm->activo ? 'TRUE' : 'FALSE');
    }
    echo "\n";
}
