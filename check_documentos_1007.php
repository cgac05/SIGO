<?php
require 'vendor/autoload.php';
require 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;

// Buscar documentos para solicitud 1007
$documentos = DB::table('Documentos_Expediente')
    ->where('fk_folio', 1007)
    ->select('id_doc', 'fk_folio', 'tipo_documento', 'estado_validacion', 'fecha_carga')
    ->get();

echo "=== DOCUMENTOS PARA SOLICITUD 1007 ===\n\n";

if ($documentos->isEmpty()) {
    echo "❌ No hay documentos para la solicitud 1007\n";
} else {
    foreach ($documentos as $doc) {
        echo "ID Documento: {$doc->id_doc}\n";
        echo "  Folio: {$doc->fk_folio}\n";
        echo "  Tipo: {$doc->tipo_documento}\n";
        echo "  Estado: {$doc->estado_validacion}\n";
        echo "  Fecha Carga: {$doc->fecha_carga}\n";
        echo "\n";
    }
}

// Verificar hito actual
$hito = DB::table('Hitos_Apoyo')
    ->join('Solicitudes', 'Hitos_Apoyo.fk_id_apoyo', '=', 'Solicitudes.fk_id_apoyo')
    ->where('Solicitudes.folio', 1007)
    ->where('Hitos_Apoyo.activo', 1)
    ->select('Hitos_Apoyo.clave_hito', 'Hitos_Apoyo.nombre_hito', 'Hitos_Apoyo.fecha_inicio', 'Hitos_Apoyo.fecha_fin')
    ->first();

echo "\n=== HITO ACTUAL ===\n";
if ($hito) {
    echo "Clave: {$hito->clave_hito}\n";
    echo "Nombre: {$hito->nombre_hito}\n";
    echo "Inicio: {$hito->fecha_inicio}\n";
    echo "Fin: {$hito->fecha_fin}\n";
} else {
    echo "❌ No se encontró hito\n";
}
