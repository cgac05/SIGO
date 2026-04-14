<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

echo "=== DOCUMENTOS DE FOLIO 1008 ===\n";
$docs = DB::table('Documentos_Expediente')
    ->where('fk_folio', 1008)
    ->get();

foreach($docs as $i => $doc) {
    echo "\nDocumento #" . ($i+1) . ":\n";
    echo "  - ID: {$doc->id_documento}\n";
    echo "  - Folio: {$doc->fk_folio}\n";
    echo "  - Ruta guardada: {$doc->ruta_archivo}\n";
    echo "  - Estado validación: {$doc->estado_validacion}\n";
    
    // Verificar si existe el archivo
    $fullPath = public_path($doc->ruta_archivo);
    $exists = file_exists($fullPath);
    echo "  - ¿Existe en: {$fullPath}? " . ($exists ? "✓ SÍ" : "✗ NO") . "\n";
    
    // Ver qué hay en storage
    if (strpos($doc->ruta_archivo, 'storage') !== false) {
        $storagePath = str_replace('storage/', '', $doc->ruta_archivo);
        $storageExists = Storage::disk('public')->exists($storagePath);
        echo "  - ¿Existe en storage/{$storagePath}? " . ($storageExists ? "✓ SÍ" : "✗ NO") . "\n";
    }
}

echo "\n=== DIRECTORIOS DISPONIBLES ===\n";
echo "Storage public path: " . storage_path('app/public') . "\n";
echo "Public path: " . public_path() . "\n";

// Ver qué documentos hay en storage
echo "\nArchivos en storage/app/public/documentos:\n";
$publicDocsPath = storage_path('app/public/documentos');
if (is_dir($publicDocsPath)) {
    $files = scandir($publicDocsPath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - {$file}\n";
        }
    }
} else {
    echo "  ✗ Carpeta no existe\n";
}

echo "\nArchivos en public/storage/documentos:\n";
$publicStoragePath = public_path('storage/documentos');
if (is_dir($publicStoragePath)) {
    $files = scandir($publicStoragePath);
    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "  - {$file}\n";
        }
    }
} else {
    echo "  ✗ Carpeta no existe\n";
}
