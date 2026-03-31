<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Búsqueda de admin@injuve.gob.mx ===\n";

// Find the user
$user = DB::selectOne('SELECT id_usuario, email FROM Usuarios WHERE email = ?', ['admin@injuve.gob.mx']);

if ($user) {
    echo "✅ Usuario encontrado:\n";
    echo "   ID: " . $user->id_usuario . "\n";
    echo "   Email: " . $user->email . "\n";
    
    // Check if has calendar permission
    $perm = DB::selectOne(
        'SELECT id_permiso, activo FROM directivos_calendario_permisos WHERE fk_id_directivo = ?',
        [$user->id_usuario]
    );
    
    echo "\n   Calendar Permission: " . ($perm ? 'YES' : 'NO') . "\n";
    if ($perm) {
        echo "   - Activo: " . ($perm->activo ? 'TRUE' : 'FALSE') . "\n";
    }
} else {
    echo "❌ Usuario no encontrado\n";
}

echo "\n=== Todos los Directivos (usuarios con permisos de calendario) ===\n";
$allUsers = DB::select('SELECT id_usuario, email FROM Usuarios ORDER BY id_usuario');
foreach ($allUsers as $u) {
    $perm = DB::selectOne(
        'SELECT id_permiso, activo FROM directivos_calendario_permisos WHERE fk_id_directivo = ?',
        [$u->id_usuario]
    );
    
    echo "ID: " . str_pad($u->id_usuario, 3) . " | " . str_pad($u->email, 30) . " | Perm: " . ($perm ? 'YES' : 'NO');
    if ($perm) {
        echo " | Activo: " . ($perm->activo ? 'TRUE' : 'FALSE');
    }
    echo "\n";
}
