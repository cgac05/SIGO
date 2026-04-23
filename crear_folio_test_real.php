<?php
/**
 * Script: Crear folio de prueba con apoyo REAL
 */
require 'bootstrap/app.php';
$app = require_once 'bootstrap/app.php';

use App\Models\Solicitud;
use App\Models\Apoyo;
use App\Models\ClaveSegumientoPrivada;

echo "═══════════════════════════════════════════════════════\n";
echo "CREAR FOLIO DE PRUEBA CON APOYO REAL\n";
echo "═══════════════════════════════════════════════════════\n\n";

try {
    // 1. Obtener apoyos disponibles
    echo "✓ Buscando apoyos disponibles...\n";
    $apoyos = Apoyo::take(5)->get();
    
    if ($apoyos->count() == 0) {
        echo "✗ NO hay apoyos en la BD\n";
        exit;
    }
    
    echo "   Apoyos encontrados:\n";
    foreach ($apoyos as $a) {
        echo "   - ID: {$a->id_apoyo} | {$a->nombre_apoyo}\n";
    }
    
    // 2. Usar el primer apoyo
    $apoyo = $apoyos->first();
    echo "\n✓ Usando apoyo: {$apoyo->nombre_apoyo} (ID: {$apoyo->id_apoyo})\n";
    
    // 3. Crear solicitud con folio 1035
    echo "\n✓ Creando solicitud...\n";
    
    // Primero, borrar si existe
    Solicitud::where('folio', 1035)->delete();
    ClaveSegumientoPrivada::where('folio', 1035)->delete();
    
    $solicitud = Solicitud::create([
        'folio' => 1035,
        'fk_curp' => 'TEST1234567890TEST',
        'fk_id_apoyo' => $apoyo->id_apoyo,  // ✓ Apoyo real
        'beneficiario_id' => null,  // Sin usuario registrado
        'origen_solicitud' => 'admin_caso_a',
        'estado_solicitud' => 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN',
    ]);
    
    echo "   ✓ Solicitud creada: folio={$solicitud->folio}\n";
    
    // 4. Crear clave privada
    echo "\n✓ Creando clave de acceso...\n";
    $clave = ClaveSegumientoPrivada::create([
        'folio' => 1035,
        'clave_alfanumerica' => 'TEST-TEST-TEST-TEST',
        'beneficiario_id' => null,
        'activa' => true,
        'bloqueada' => false,
    ]);
    
    echo "   ✓ Clave creada: {$clave->clave_alfanumerica}\n";
    
    // 5. Verificar que se puede consultar
    echo "\n✓ Verificando datos...\n";
    $verificar = Solicitud::where('folio', 1035)->first();
    $apoyo_verificar = $verificar?->apoyo;
    
    if ($apoyo_verificar) {
        echo "   ✓ Apoyo se carga correctamente: {$apoyo_verificar->nombre_apoyo}\n";
    } else {
        echo "   ✗ Apoyo NO se carga\n";
    }
    
    // 6. URLs de prueba
    echo "\n" . str_repeat("═", 55) . "\n";
    echo "URLS PARA TESTING:\n";
    echo str_repeat("═", 55) . "\n";
    echo "Resumen Admin:\n";
    echo "  http://localhost:8000/admin/caso-a/resumen/1035\n\n";
    echo "QR Directo (copiar URL):\n";
    echo "  http://localhost:8000/consulta-privada/acceso-qr?folio=1035&clave=TEST-TEST-TEST-TEST\n\n";
    echo "Consulta Privada:\n";
    echo "  http://localhost:8000/consulta-privada/resumen\n";
    echo str_repeat("═", 55) . "\n";

} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
