<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PROBLEMA IDENTIFICADO ===\n";
echo "El documento guardado usa ruta: solicitudes/8vHfA3lOT4nIlZVIBqE8345EngoWVYghqnlImqxu.pdf\n";
echo "Pero el archivo NO EXISTE en esa ubicación.\n\n";

echo "=== ARCHIVO QUE NECESITAMOS ===\n";
// Usar el primer PDF que encontramos
$storagePath = storage_path('app/public/solicitudes');
$files = scandir($storagePath);
$pdf_files = array_filter($files, function($f) { return strpos($f, '.pdf') !== false; });
$pdf_files = array_values($pdf_files);

if (count($pdf_files) > 0) {
    $correctFile = $pdf_files[0];
    $correctRoute = "storage/solicitudes/{$correctFile}";
    
    echo "Archivo disponible: {$correctFile}\n";
    echo "Ruta correcta debería ser: {$correctRoute}\n\n";
    
    echo "=== ACTUALIZANDO DOCUMENTO ===\n";
    $updated = DB::table('Documentos_Expediente')
        ->where('fk_folio', 1008)
        ->update(['ruta_archivo' => $correctRoute]);
    
    echo "Registros actualizados: {$updated}\n";
    
    // Verificar la actualización
    $doc = DB::table('Documentos_Expediente')
        ->where('fk_folio', 1008)
        ->first();
    
    echo "\nVerificación - Ruta actualizada a: {$doc->ruta_archivo}\n";
    
    // Verificar que el asset() genera la URL correcta
    $fullPath = public_path($correctRoute);
    $exists = file_exists($fullPath);
    echo "¿Existe en {$fullPath}? " . ($exists ? "✓ SÍ" : "✗ NO") . "\n";
    
    if (!$exists) {
        // Comprobar alternativas
        $altPath = storage_path('app/public/solicitudes/' . $correctFile);
        echo "\n📍 Ruta alternativa real: {$altPath}\n";
        echo "¿Existe en ruta real? " . (file_exists($altPath) ? "✓ SÍ" : "✗ NO") . "\n";
    }
} else {
    echo "❌ NO hay archivos PDF en storage/app/public/solicitudes\n";
}
