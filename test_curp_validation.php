<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Services\CasoADocumentService;
use App\Models\Apoyo;

echo "=== TEST VALIDACIÓN CURP REQUERIDO ===\n\n";

// Test 1: Intento sin CURP (debe fallar)
echo "TEST 1: Sin CURP (debe fallar)\n";
echo "---\n";

try {
    $service = new CasoADocumentService();
    
    $beneficiario_sin_curp = (object)[
        'id_usuario' => null,
        'nombre_completo' => 'Test User',
        'curp' => null,  // ← NULL, debe fallar
        'email' => 'test@example.com',
        'telefono' => '5511111111'
    ];
    
    $resultado = $service->crearExpedientePresencial(
        null,  // sin beneficiario registrado
        $beneficiario_sin_curp,
        3,  // apoyo_id
        ['CEDULA'],  // documentos
        6,  // admin_id
        'Nota de prueba'
    );
    
    echo "✗ ERROR: Debería haber fallado pero pasó\n";
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'CURP') !== false) {
        echo "✓ Correctamente bloqueado: " . $e->getMessage() . "\n";
    } else {
        echo "✗ Error diferente: " . $e->getMessage() . "\n";
    }
}

echo "\n";

// Test 2: Con CURP válido (debe funcionar)
echo "TEST 2: Con CURP válido (debe funcionar)\n";
echo "---\n";

try {
    $service = new CasoADocumentService();
    
    $beneficiario_con_curp = (object)[
        'id_usuario' => null,
        'nombre_completo' => 'Beneficiario Test 123',
        'curp' => 'AICC050509HNTVMHA5',  // ← CURP válido
        'email' => 'ben@example.com',
        'telefono' => '3121234567'
    ];
    
    $resultado = $service->crearExpedientePresencial(
        null,
        $beneficiario_con_curp,
        3,
        ['CEDULA', 'COMPROBANTE_DOMICILIO'],
        6,
        'Registro de prueba'
    );
    
    echo "✓ Inserción exitosa\n";
    echo "  Folio: " . $resultado['folio'] . "\n";
    echo "  Clave: " . $resultado['clave_acceso'] . "\n";
    
    // Verificar que fk_curp no es NULL
    $solicitud = DB::table('Solicitudes')
        ->where('folio', $resultado['folio'])
        ->first();
    
    if ($solicitud->fk_curp) {
        echo "  ✓ CURP guardado: " . $solicitud->fk_curp . "\n";
    } else {
        echo "  ✗ ERROR: CURP es NULL en BD\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Error inesperado: " . $e->getMessage() . "\n";
}

echo "\n";
echo "=== TEST COMPLETADO ===\n";
?>
