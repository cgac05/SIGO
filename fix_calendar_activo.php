<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Update the existing record to activo = 1
DB::table('directivos_calendario_permisos')->where('id_permiso', 4)->update(['activo' => 1]);

// Verify the update
$permisos = DB::select('SELECT TOP 10 id_permiso, fk_id_directivo, email_directivo, activo, created_at FROM directivos_calendario_permisos ORDER BY id_permiso DESC');
echo "=== Updated Calendar Permissions ===\n";
foreach($permisos as $p) {
    echo sprintf("ID: %d, Directivo: %d, Email: %s, Activo: %s, Created: %s\n", 
        $p->id_permiso, 
        $p->fk_id_directivo, 
        $p->email_directivo, 
        $p->activo ? 'true' : 'false',
        $p->created_at
    );
}
