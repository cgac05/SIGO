<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICANDO SOLICITUD 1007 ===\n\n";

$solicitud = DB::table('Solicitudes')
    ->where('folio', 1007)
    ->select('folio', 'presupuesto_confirmado', 'cuv', 'monto_entregado', 'fk_id_estado', 'fecha_confirmacion_presupuesto')
    ->first();

if (!$solicitud) {
    echo "❌ Solicitud 1007 no encontrada\n";
    exit(1);
}

echo "Folio: {$solicitud->folio}\n";
echo "presupuesto_confirmado: " . ($solicitud->presupuesto_confirmado ? 'TRUE/1' : 'FALSE/0') . "\n";
echo "cuv: {$solicitud->cuv}\n";
echo "monto_entregado: {$solicitud->monto_entregado}\n";
echo "fk_id_estado: {$solicitud->fk_id_estado}\n";
echo "fecha_confirmacion_presupuesto: {$solicitud->fecha_confirmacion_presupuesto}\n";

echo "\n=== VERIFICANDO DOCUMENTO 6 ===\n\n";

$doc = DB::table('Documentos_Expediente')
    ->where('id_doc', 6)
    ->select('id_doc', 'fk_folio', 'estado_validacion', 'revisado_por', 'fecha_revision')
    ->first();

if (!$doc) {
    echo "❌ Documento 6 no encontrado\n";
} else {
    echo "ID Doc: {$doc->id_doc}\n";
    echo "Folio: {$doc->fk_folio}\n";
    echo "Estado: {$doc->estado_validacion}\n";
    echo "Revisado por: {$doc->revisado_por}\n";
    echo "Fecha Revisión: {$doc->fecha_revision}\n";
}

echo "\n=== VERIFICANDO HITO ACTUAL ===\n\n";

$apoyo = DB::table('Solicitudes')
    ->where('folio', 1007)
    ->select('fk_id_apoyo')
    ->first();

if ($apoyo) {
    $hitos = DB::table('Hitos_Apoyo')
        ->where('fk_id_apoyo', $apoyo->fk_id_apoyo)
        ->where('activo', 1)
        ->select('clave_hito', 'nombre_hito', 'fecha_inicio', 'fecha_fin', 'orden_hito')
        ->orderBy('orden_hito')
        ->get();

    echo "Hitos activos para apoyo {$apoyo->fk_id_apoyo}:\n";
    foreach ($hitos as $hito) {
        echo "  - {$hito->clave_hito} ({$hito->nombre_hito}) - Orden: {$hito->orden_hito}\n";
    }
}
