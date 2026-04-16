<?php
// Buscar el documento con ese hash específico
require 'bootstrap/app.php';
$app = require_once('bootstrap/app.php');
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$docs = \App\Models\Documento::where('ruta_archivo', 'LIKE', '%1rKeeN6Iw3j%')->get();

echo "=== Búsqueda de documento con ese hash ===\n";
foreach ($docs as $doc) {
    echo "ID: {$doc->id_doc}\n";
    echo "  Folio: {$doc->fk_folio}\n";
    echo "  Ruta en BD: {$doc->ruta_archivo}\n";
    echo "  Origen: " . ($doc->origen_archivo ?? 'NULL') . "\n";
    echo "  Google File ID: " . ($doc->google_file_id ?? 'NULL') . "\n";
    echo "  Google File Name: " . ($doc->google_file_name ?? 'NULL') . "\n";
    echo "\n";
}

if ($docs->isEmpty()) {
    echo "No se encontró documento con ese hash\n";
}
