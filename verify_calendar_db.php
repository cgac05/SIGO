<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$permisos = DB::select('SELECT TOP 10 id_permiso, fk_id_directivo, email_directivo, activo, created_at FROM directivos_calendario_permisos ORDER BY id_permiso DESC');
echo "=== Current Calendar Permissions in BD ===\n";
if (count($permisos) == 0) {
    echo "No records found in directivos_calendario_permisos table\n";
} else {
    foreach($permisos as $p) {
        echo sprintf("ID: %d, Directivo: %d, Email: %s, Activo: %s, Created: %s\n", 
            $p->id_permiso, 
            $p->fk_id_directivo, 
            $p->email_directivo, 
            $p->activo ? 'true' : 'false',
            $p->created_at
        );
    }
}
