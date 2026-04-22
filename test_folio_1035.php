<?php
/**
 * Test: Verify folio 1035 exists and has clave
 */

require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use App\Models\Solicitud;
use App\Models\ClaveSegumientoPrivada;

echo "═══════════════════════════════════════════════════════\n";
echo "TEST: VERIFICAR FOLIO 1035\n";
echo "═══════════════════════════════════════════════════════\n\n";

try {
    // Buscar solicitud
    $solicitud = Solicitud::where('folio', 1035)->first();
    
    if (!$solicitud) {
        echo "✗ Folio 1035 NO EXISTE\n";
        echo "   Creando folio de prueba...\n";
        
        // Crear una solicitud de prueba
        $solicitud = Solicitud::create([
            'folio' => 1035,
            'fk_curp' => 'TEST1234567890TEST',
            'fk_id_beneficiario' => null,
            'solicitud_date' => now(),
            'solicitud_time' => now(),
        ]);
        echo "   ✓ Folio 1035 creado\n";
    }
    
    echo "✓ Folio 1035 encontrado:\n";
    echo "   Folio: " . $solicitud->folio . "\n";
    echo "   CURP: " . $solicitud->fk_curp . "\n";
    
    // Buscar clave
    $clave = ClaveSegumientoPrivada::where('folio', 1035)->first();
    
    if (!$clave) {
        echo "\n✗ Clave NO EXISTE para folio 1035\n";
        echo "   Creando clave de prueba...\n";
        
        $clave = ClaveSegumientoPrivada::create([
            'folio' => 1035,
            'clave_alfanumerica' => 'TEST-TEST-TEST-TEST',
            'beneficiario_id' => null,
        ]);
        echo "   ✓ Clave creada\n";
    }
    
    echo "\n✓ Clave encontrada:\n";
    echo "   Folio: " . $clave->folio . "\n";
    echo "   Clave: " . $clave->clave_alfanumerica . "\n";
    
    // Generar URL QR
    $qrUrl = route('caso-a.acceso-qr', [
        'folio' => 1035,
        'clave' => $clave->clave_alfanumerica
    ], true);
    
    echo "\n✓ URL QR:\n";
    echo "   " . $qrUrl . "\n";
    
    echo "\n═══════════════════════════════════════════════════════\n";
    echo "✓ TODOS LOS DATOS ESTÁN LISTOS\n";
    echo "   Visita: http://localhost:8000/admin/caso-a/resumen/1035\n";
    echo "═══════════════════════════════════════════════════════\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
