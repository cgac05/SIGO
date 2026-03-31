<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Auth;
use App\Models\DirectivoCalendarioPermiso;

// Simulate logged-in user - check if any user is logged in
// We'll check all directivos with calendar permissions
$allPermisos = DirectivoCalendarioPermiso::all();
echo "=== All Calendar Permissions ===\n";
foreach($allPermisos as $p) {
    echo "Directivo: " . $p->fk_id_directivo . " | Email: " . $p->email_directivo . " | Activo: " . ($p->activo ? 'TRUE' : 'FALSE') . " | Activo (raw): " . $p->getAttributes()['activo'] . "\n";
}

// Check what the controller is actually passing to the view
echo "\n=== Simulating Controller Logic ===\n";
foreach($allPermisos as $permiso) {
    $directivo_id = $permiso->fk_id_directivo;
    $permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', $directivo_id)->first();
    
    echo "\nDirectivo ID: " . $directivo_id . "\n";
    if ($permiso) {
        echo "  - Permiso found: YES\n";
        echo "  - Permiso->activo: " . var_export($permiso->activo, true) . "\n";
        echo "  - \$permiso && \$permiso->activo: " . var_export(($permiso && $permiso->activo), true) . "\n";
        echo "  - Would show as: " . (($permiso && $permiso->activo) ? 'CONECTADO' : 'DESCONECTADO') . "\n";
    } else {
        echo "  - Permiso found: NO\n";
        echo "  - Would show as: DESCONECTADO\n";
    }
}
