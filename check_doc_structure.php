<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ESTRUCTURA DE TABLA Documentos_Expediente ===\n";
$columns = DB::select("SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Documentos_Expediente' AND TABLE_SCHEMA = 'BD_SIGO'");
foreach($columns as $col) {
    echo "  - {$col->COLUMN_NAME} ({$col->DATA_TYPE})\n";
}

echo "\n=== DOCUMENTOS DE FOLIO 1008 ===\n";
$docs = DB::table('Documentos_Expediente')
    ->where('fk_folio', 1008)
    ->get();

if ($docs->count() === 0) {
    echo "❌ No hay documentos para folio 1008\n";
} else {
    foreach($docs as $i => $doc) {
        echo "\nDocumento #" . ($i+1) . ":\n";
        echo json_encode((array)$doc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        
        // Verificar si existe en la ruta
        if (isset($doc->ruta_archivo)) {
            $fullPath = public_path($doc->ruta_archivo);
            $exists = file_exists($fullPath);
            echo "  Ruta completa: {$fullPath}\n";
            echo "  ¿Existe?: " . ($exists ? "✓ SÍ" : "✗ NO") . "\n";
        }
    }
}
