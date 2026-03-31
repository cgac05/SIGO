<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== All Usuarios in BD with Calendar Permissions ===\n";
$usuarios = DB::select('SELECT TOP 50 id_usuario, email FROM Usuarios ORDER BY id_usuario'); 

foreach($usuarios as $u) {
    // Check if has calendar permission
    $perm = DB::selectOne(
        'SELECT id_permiso, activo FROM directivos_calendario_permisos WHERE fk_id_directivo = ?', 
        [$u->id_usuario]
    );
    
    echo "ID: " . str_pad($u->id_usuario, 3) . " | Email: " . str_pad($u->email, 30) . " | Perm: " . ($perm ? 'YES' : 'NO');
    if ($perm) {
        echo " | Activo: " . ($perm->activo ? 'TRUE' : 'FALSE');
    }
    echo "\n";
}
