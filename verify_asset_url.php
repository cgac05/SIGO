<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN DE URL GENERADA ===\n";
$doc = DB::table('Documentos_Expediente')
    ->where('fk_folio', 1008)
    ->first();

if ($doc) {
    echo "Ruta en BD: {$doc->ruta_archivo}\n";
    
    // Simuler asset()
    $assetUrl = '/storage/solicitudes/1rKeeN6Iw3jSO59grKpYY8h8vIiMZYKbiFOOn9hg.pdf';
    echo "URL generada por asset(): {$assetUrl}\n";
    
    // Verificar que el symlink exista
    $symlinkPath = public_path('storage');
    echo "\n¿Existe symlink en public/storage? " . (is_link($symlinkPath) ? "✓ SÍ (symlink)" : (is_dir($symlinkPath) ? "✓ SÍ (carpeta)" : "✗ NO")) . "\n";
    
    if (is_link($symlinkPath)) {
        $target = readlink($symlinkPath);
        echo "Apunta a: {$target}\n";
    }
    
    // Verificar archivo accesible
    $publicFile = public_path('storage/solicitudes/1rKeeN6Iw3jSO59grKpYY8h8vIiMZYKbiFOOn9hg.pdf');
    echo "¿Archivo accesible en public/{$assetUrl}? " . (file_exists($publicFile) ? "✓ SÍ" : "✗ NO") . "\n";
}
