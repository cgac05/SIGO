<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

// Contar directivos activos
$directivos = \App\Models\DirectivosCalendarioPermisos::where('activo', 1)->get();

echo "Directivos activos con permisos de calendario: " . count($directivos) . "\n";
foreach ($directivos as $dir) {
    echo "  - ID: {$dir->id_permiso}, Directivo: {$dir->fk_id_directivo}, Email: {$dir->email_directivo}\n";
}
?>
