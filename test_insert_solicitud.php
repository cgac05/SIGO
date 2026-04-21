<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

echo "=== TEST WORKFLOW COMPLETO MOMENTO 1 ===\n\n";

// Simular datos de entrada
$apoyo_id = 3;
$admin_id = 6;
$beneficiario_id = 1;
$curp = 'AICC050509HNTVMHA5';
$observaciones = "Notas de prueba | Nombre: Christian | Email: test@mail.com | Tel: 3111111111";
$documentos = ['CEDULA', 'COMPROBANTE_DOMICILIO'];

echo "Simulando inserción de Solicitud + ClaveSegumientoPrivada + Auditoría\n";
echo "Apoyo ID: $apoyo_id | Admin ID: $admin_id | Beneficiario ID: $beneficiario_id\n\n";

DB::beginTransaction();

try {
    // 1. Crear Solicitud
    $folio = DB::table('Solicitudes')->insertGetId([
        'fk_id_apoyo' => $apoyo_id,
        'fk_id_estado' => 1,
        'estado_solicitud' => 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN',
        'origen_solicitud' => 'admin_caso_a',
        'creada_por_admin' => 1,
        'admin_creador' => $admin_id,
        'beneficiario_id' => $beneficiario_id,
        'apoyo_id' => $apoyo_id,
        'observaciones_internas' => $observaciones,
        'fk_curp' => $curp,
        'fecha_creacion' => \DB::raw('GETDATE()'),
        'fecha_cambio_estado' => \DB::raw('GETDATE()'),
    ], 'folio');
    
    echo "✓ Solicitud creada: Folio $folio\n";
    
    // 2. Crear ClaveSegumientoPrivada
    $clave = 'TESTKEY' . random_int(10000, 99999);
    $hash_clave = hash('sha256', $folio . $clave . 'test_key');
    
    DB::table('claves_seguimiento_privadas')->insert([
        'folio' => $folio,
        'clave_alfanumerica' => $clave,
        'hash_clave' => $hash_clave,
        'beneficiario_id' => $beneficiario_id,
        'fecha_creacion' => \DB::raw('GETDATE()'),
        'intentos_fallidos' => 0,
        'bloqueada' => 0,
    ]);
    
    echo "✓ ClaveSegumientoPrivada creada: $clave\n";
    
    // 3. Crear AuditoriaCargaMaterial
    DB::table('auditorias_carga_material')->insert([
        'folio' => $folio,
        'evento' => 'caso_a_momento_1_presencial',
        'admin_id' => $admin_id,
        'cantidad_docs' => count($documentos),
        'fecha_evento' => \DB::raw('GETDATE()'),
        'ip_admin' => '127.0.0.1',
        'navegador_agente' => 'Mozilla/5.0 Test',
        'detalles_evento' => json_encode(['documentos' => $documentos]),
    ]);
    
    echo "✓ AuditoriaCargaMaterial registrada\n\n";
    
    DB::commit();
    
    // Verificar inserciones
    echo "=== VERIFICACIÓN DE DATOS ===\n\n";
    
    $solicitud = DB::table('Solicitudes')->where('folio', $folio)->first();
    echo "Solicitud:\n";
    echo "  Folio: " . $solicitud->folio . "\n";
    echo "  Estado: " . $solicitud->estado_solicitud . "\n";
    echo "  Origen: " . $solicitud->origen_solicitud . "\n";
    echo "  Admin: " . $solicitud->admin_creador . "\n\n";
    
    $clave_rec = DB::table('claves_seguimiento_privadas')->where('folio', $folio)->first();
    echo "Clave:\n";
    echo "  Folio: " . $clave_rec->folio . "\n";
    echo "  Clave: " . $clave_rec->clave_alfanumerica . "\n";
    echo "  Fecha: " . $clave_rec->fecha_creacion . "\n\n";
    
    $auditoria = DB::table('auditorias_carga_material')
        ->where('folio', $folio)
        ->first();
    echo "Auditoría:\n";
    echo "  Folio: " . $auditoria->folio . "\n";
    echo "  Evento: " . $auditoria->evento . "\n";
    echo "  Docs: " . $auditoria->cantidad_docs . "\n";
    echo "  Fecha: " . $auditoria->fecha_evento . "\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "✗ Error: " . $e->getMessage() . "\n\n";
    echo $e->getTraceAsString();
}
?>
