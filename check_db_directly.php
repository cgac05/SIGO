<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "\n==============================================\n";
echo "  CONSULTA SQL DIRECTA - DOCUMENTOS\n";
echo "==============================================\n\n";

$docs = DB::table('Documentos_Expediente')
    ->select('id_documento', 'fk_folio', 'ruta_archivo', 'admin_status')
    ->limit(3)
    ->get();

echo "Resultados:\n";
foreach ($docs as $doc) {
    echo "  id_documento: " . ($doc->id_documento ?? 'NULL') . "\n";
    echo "  fk_folio: " . ($doc->fk_folio ?? 'NULL') . "\n";
    echo "  ruta_archivo: " . ($doc->ruta_archivo ?? 'NULL') . "\n\n";
}

echo "==============================================\n\n";
