<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PREPARAR FOLIO 1008 PARA DEMOSTRACIÓN ===\n\n";

// 1. Limpiar CUV (volver a NULL para que se pueda firmar de nuevo)
DB::table('Solicitudes')
    ->where('folio', 1008)
    ->update([
        'cuv' => null,
        'fk_id_estado' => 10, // DOCUMENTOS_VERIFICADOS
        'presupuesto_confirmado' => 0,
    ]);

echo "✓ Folio 1008 reseteado\n\n";

// 2. Verificar estado actual
$solicitud = DB::table('Solicitudes')
    ->where('folio', 1008)
    ->first([
        'folio',
        'fk_id_estado',
        'cuv',
        'presupuesto_confirmado',
        'monto_entregado'
    ]);

echo "=== ESTADO ACTUAL ===\n";
echo "Folio: {$solicitud->folio}\n";
echo "Estado ID: {$solicitud->fk_id_estado}\n";

$estado = DB::table('Cat_EstadosSolicitud')
    ->where('id_estado', $solicitud->fk_id_estado)
    ->first();

echo "Nombre Estado: {$estado->nombre_estado}\n";
echo "CUV: " . ($solicitud->cuv ? $solicitud->cuv : "NULL ✓") . "\n";
echo "Presupuesto Confirmado: {$solicitud->presupuesto_confirmado}\n";
echo "Monto Entregado: \${$solicitud->monto_entregado}\n\n";

// 3. Verificar presupuesto disponible
$presupuesto = DB::table('presupuesto_apoyos')
    ->where('folio', 1008)
    ->first();

if ($presupuesto) {
    $disponible = ($presupuesto->monto_solicitado ?? 0) - ($presupuesto->monto_aprobado ?? 0);
    echo "=== PRESUPUESTO ===\n";
    echo "Solicitado: \${$presupuesto->monto_solicitado}\n";
    echo "Aprobado: \${$presupuesto->monto_aprobado}\n";
    echo "Disponible: \${$disponible}\n\n";
}

// 4. Verificar documentos
$docs = DB::table('Documentos_Expediente')
    ->where('fk_folio', 1008)
    ->get(['id_doc', 'estado_validacion', 'ruta_archivo']);

echo "=== DOCUMENTOS ===\n";
echo "Total: {$docs->count()}\n";
foreach ($docs as $doc) {
    echo "  - ID {$doc->id_doc}: estado={$doc->estado_validacion}, archivo={$doc->ruta_archivo}\n";
}

echo "\n" . str_repeat("═", 60) . "\n";
echo "✅ FOLIO 1008 LISTO PARA DEMOSTRACIÓN\n";
echo str_repeat("═", 60) . "\n";
echo "\n📝 Credenciales de prueba:\n";
echo "   Email: directivo@test.com\n";
echo "   Password: password123\n";
echo "\n🔗 URL para demostración:\n";
echo "   http://localhost:8000/solicitudes/proceso/1008\n";
echo "\n⏳ Estado: DOCUMENTOS_VERIFICADOS (listo para firmar)\n";
