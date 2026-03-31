<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\DirectivoCalendarioPermiso;

// Find the record
$permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', 17)->first();

if ($permiso) {
    echo "=== DirectivoCalendarioPermiso Record (ID 17) ===\n";
    echo "ID: " . $permiso->id_permiso . "\n";
    echo "Email: " . $permiso->email_directivo . "\n";
    echo "Activo (raw): " . var_export($permiso->getAttributes()['activo'] ?? 'NOT_FOUND', true) . "\n";
    echo "Activo (casted): " . var_export($permiso->activo, true) . "\n";
    echo "Activo type: " . gettype($permiso->activo) . "\n";
    echo "Activo bool check: " . ($permiso->activo ? 'TRUE' : 'FALSE') . "\n";
    echo "\nAll attributes:\n";
    print_r($permiso->getAttributes());
} else {
    echo "No record found for directivo_id 17\n";
}
