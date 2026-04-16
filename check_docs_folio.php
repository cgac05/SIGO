<?php

// Usar tinker para consultar documentos
$docs = \App\Models\Documento::where('fk_folio', 1014)->get(['id_doc', 'ruta_archivo', 'origen_archivo', 'google_file_id']);

echo "=== Documentos de folio 1014 ===\n";
foreach ($docs as $d) {
    $origen = $d->origen_archivo ?? 'NULL';
    $ruta = $d->ruta_archivo ?? '---';
    $gdrive = $d->google_file_id ?? 'NULL';
    echo "ID: {$d->id_doc} | Ruta: {$ruta} | Origen: {$origen} | GDrive: {$gdrive}\n";
}
