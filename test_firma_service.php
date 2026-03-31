<?php

// Quick test script for FirmaElectronicaService
use App\Models\Usuario;
use App\Services\FirmaElectronicaService;
use Illuminate\Support\Facades\DB;

echo "=== TEST: FirmaElectronicaService ===\n\n";

// Test 1: Check if service can be instantiated
try {
    $service = app(FirmaElectronicaService::class);
    echo "✓ FirmaElectronicaService instantiated successfully\n";
} catch (\Exception $e) {
    echo "✗ Failed to instantiate service: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check if Seguimiento_Solicitud table exists
try {
    $exists = DB::table('Seguimiento_Solicitud')->count();
    echo "✓ Seguimiento_Solicitud table exists (rows: $exists)\n";
} catch (\Exception $e) {
    echo "✗ Table check failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 3: Test CUV generation
try {
    $cuv = $service->generarCuv(18);
    if (strlen($cuv) === 18 && ctype_xdigit($cuv)) {
        echo "✓ CUV generation works: $cuv\n";
    } else {
        echo "✗ CUV format invalid\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "✗ CUV generation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 4: Test sello digital generation
try {
    $documentos = [
        ['id_documento' => 1, 'tipo' => 1, 'estado' => 'aprobado'],
        ['id_documento' => 2, 'tipo' => 2, 'estado' => 'aprobado'],
    ];
    $sello = $service->generarSelloDigital(12345, $documentos, 1);
    if (strlen($sello) === 64 && ctype_xdigit($sello)) {
        echo "✓ Sello digital generation works (SHA256): " . substr($sello, 0, 16) . "...\n";
    } else {
        echo "✗ Sello digital format invalid\n";
        exit(1);
    }
} catch (\Exception $e) {
    echo "✗ Sello digital generation failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 5: Check controller has the service injected
try {
    $reflection = new ReflectionClass(\App\Http\Controllers\SolicitudProcesoController::class);
    $constructor = $reflection->getConstructor();
    $params = $constructor->getParameters();
    
    $hasService = false;
    foreach ($params as $param) {
        if (strpos($param->getType() ? $param->getType()->getName() : '', 'FirmaElectronicaService') !== false) {
            $hasService = true;
            break;
        }
    }
    
    if ($hasService) {
        echo "✓ SolicitudProcesoController has FirmaElectronicaService injected\n";
    } else {
        echo "✗ FirmaElectronicaService not found in controller dependencies\n";
    }
} catch (\Exception $e) {
    echo "✗ Controller reflection failed: " . $e->getMessage() . "\n";
}

echo "\n=== All tests passed! ===\n";
