<?php
require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

echo "\n=== Debug Apoyos Loading ===\n\n";

// Test if route exists
if (Route::has('apoyos.index')) {
    echo "✓ Ruta apoyos.index existe\n";
} else {
    echo "✗ Ruta apoyos.index NO existe\n";
}

// Test database connection
try {
    $apoyos = \DB::table('Apoyos')->limit(1)->get();
    echo "✓ Se puede consultar Apoyos. Total: " . $apoyos->count() . "\n";
} catch (\Exception $e) {
    echo "✗ Error al consultar Apoyos: " . $e->getMessage() . "\n";
}

// Try to get the view
try {
    $view = view('apoyos.index', [
        'apoyos' => collect(),
        'tiposDocumentos' => collect(),
        'user' => null,
        'misSolicitudes' => collect(),
        'solicitudesRecientes' => collect()
    ]);
    echo "✓ Vista apoyos.index se puede renderizar\n";
} catch (\Exception $e) {
    echo "✗ Error al renderizar vista: " . $e->getMessage() . "\n";
    // Print full trace
    echo "\nStack trace:\n";
    echo $e->getTraceAsString() . "\n";
}
