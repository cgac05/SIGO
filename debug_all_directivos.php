<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all Usuarios with role 3 (directivos)
$directivos = DB::select('SELECT TOP 20 id_usuario, correo_electronico, rol FROM Usuarios WHERE rol = 3 ORDER BY id_usuario');

echo "=== All Directivos (Role 3) in BD ===\n";
foreach($directivos as $d) {
    // Check if has calendar permission
    $perm = DB::selectOne(
        'SELECT id_permiso, activo FROM directivos_calendario_permisos WHERE fk_id_directivo = ?', 
        [$d->id_usuario]
    );
    
    echo "ID: " . $d->id_usuario . " | Email: " . $d->correo_electronico . " | Has Permission: " . ($perm ? 'YES' : 'NO');
    if ($perm) {
        echo " | Activo: " . ($perm->activo ? 'TRUE' : 'FALSE');
    }
    echo "\n";
}
