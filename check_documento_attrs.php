<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Models\Documento;

echo "\n==============================================\n";
echo "  VERIFICACIÓN DE DOCUMENTOS\n";
echo "==============================================\n\n";

$docs = Documento::limit(3)->get();

if ($docs->count() > 0) {
    foreach ($docs as $doc) {
        echo "Documento:\n";
        echo "  id_documento: " . ($doc->id_documento ?? 'NULL') . "\n";
        echo "  fk_folio: " . ($doc->fk_folio ?? 'NULL') . "\n";
        echo "  ruta_archivo: " . ($doc->ruta_archivo ?? 'NULL') . "\n";
        echo "  admin_status: " . ($doc->admin_status ?? 'NULL') . "\n";
        echo "\n";
    }
} else {
    echo "✗ No hay documentos\n";
}

echo "==============================================\n\n";
