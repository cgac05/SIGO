<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

// Get all directivos with their calendar permissions
$permisos = DB::select('
    SELECT 
        dcp.id_permiso, 
        dcp.fk_id_directivo, 
        dcp.email_directivo, 
        dcp.activo,
        dcp.created_at,
        dcp.updated_at,
        u.nombre_usuario,
        u.correo_electronico
    FROM directivos_calendario_permisos dcp
    LEFT JOIN Usuarios u ON dcp.fk_id_directivo = u.id_usuario
    ORDER BY dcp.id_permiso DESC
');

echo "=== All Calendar Permissions in BD ===\n";
if (count($permisos) == 0) {
    echo "No records found\n";
} else {
    foreach($permisos as $p) {
        echo sprintf(
            "ID: %d | Directivo: %d | Name: %s | Email: %s | Activo: %d | Updated: %s\n", 
            $p->id_permiso,
            $p->fk_id_directivo,
            $p->nombre_usuario ?? 'NULL',
            $p->email_directivo,
            $p->activo,
            $p->updated_at
        );
    }
}
