<?php
require __DIR__ . '/bootstrap/app.php';

$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

\Log::info("=== VERIFICACIÓN DE DOCUMENTOS FOLIO 1013 ===");

$docs = \App\Models\Documento::where('fk_folio', 1013)->orderBy('id_doc', 'desc')->get();

echo "=== DOCUMENTOS DEL FOLIO 1013 ===\n";
echo str_repeat("=", 120) . "\n\n";

foreach ($docs as $doc) {
    echo "📄 Documento ID: {$doc->id_doc}\n";
    echo "   Folio: {$doc->fk_folio}\n";
    echo "   Ruta en BD: {$doc->ruta_archivo}\n";
    echo "   Origen: " . ($doc->origen_archivo ?? 'NULL') . "\n";
    echo "   Google ID: " . ($doc->google_file_id ?? 'NULL') . "\n";
    
    // Verificar si el archivo existe
    $posibles = [
        'storage_path(app/public/' . $doc->ruta_archivo . ')' => storage_path('app/public/' . $doc->ruta_archivo),
        'public_path(storage/' . $doc->ruta_archivo . ')' => public_path('storage/' . $doc->ruta_archivo),
    ];
    
    echo "   Verificaciones:\n";
    foreach ($posibles as $label => $path) {
        $existe = file_exists($path) ? '✓ EXISTE' : '✗ NO';
        echo "     $existe - $label\n";
    }
    
    // Usar Storage facade
    $existsStorage = \Illuminate\Support\Facades\Storage::disk('public')->exists($doc->ruta_archivo);
    echo "     " . ($existsStorage ? '✓' : '✗') . " Storage::disk('public')->exists()\n";
    
    if (strpos($doc->ruta_archivo, 'google_drive') !== false || $doc->google_file_id) {
        echo "   🔗 Documento de Google Drive\n";
    }
    
    echo "\n";
}

if ($docs->isEmpty()) {
    echo "❌ No hay documentos para folio 1013\n";
}

echo str_repeat("=", 120) . "\n";
