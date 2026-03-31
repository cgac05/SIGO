<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Get all directivos with their calendar permissions - without join first
$permisos = DB::select('
    SELECT 
        id_permiso, 
        fk_id_directivo, 
        email_directivo, 
        activo,
        created_at,
        updated_at
    FROM directivos_calendario_permisos
    ORDER BY id_permiso DESC
');

echo "=== All Calendar Permissions in BD ===\n";
if (count($permisos) == 0) {
    echo "No records found\n";
} else {
    foreach($permisos as $p) {
        echo sprintf(
            "ID: %d | Directivo: %d | Email: %s | Activo: %d | Updated: %s\n", 
            $p->id_permiso,
            $p->fk_id_directivo,
            $p->email_directivo,
            $p->activo,
            $p->updated_at ? substr($p->updated_at, 0, 19) : 'NULL'
        );
    }
}

// Check Usuarios table structure
echo "\n=== Usuarios Table Columns ===\n";
$columns = DB::select('SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = "Usuarios" ORDER BY ORDINAL_POSITION');
foreach($columns as $col) {
    echo "- " . $col->COLUMN_NAME . "\n";
}
