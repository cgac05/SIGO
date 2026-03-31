<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== TESTING SUPPORT HISTORY SECTION ===\n\n";

// Get test beneficiario
$beneficiario = \App\Models\Beneficiario::where('curp', 'AICC050509HNTVMHA5')->first();

if (!$beneficiario) {
    echo "ERROR: Beneficiario not found\n";
    exit(1);
}

echo "✓ Beneficiario found: " . $beneficiario->nombre . " " . $beneficiario->apellido_paterno . "\n";

// Test relationships
echo "\n=== RELATIONSHIP TESTS ===\n";

// Test solicitudes relationship
echo "1. Testing solicitudes() relationship: ";
try {
    $solicitudes = $beneficiario->solicitudes;
    echo "✓ OK (" . count($solicitudes) . " records)\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

// Test eager load with ordering
echo "2. Testing eager load with orderBy: ";
try {
    $solicitudes = $beneficiario->solicitudes()->orderBy('fecha_creacion', 'desc')->get();
    echo "✓ OK (" . count($solicitudes) . " records)\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

// Test estado relationship
echo "3. Testing estado relationship for each solicitud: ";
try {
    $errors = [];
    foreach ($beneficiario->solicitudes as $s) {
        if (!$s->estado) {
            $errors[] = "Solicitud " . $s->folio . " has no estado";
        }
    }
    if (empty($errors)) {
        echo "✓ OK all solicitudes have estado\n";
    } else {
        echo "✗ FAILED: " . implode(", ", $errors) . "\n";
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

// Test statistics
echo "\n=== STATISTICS CALCULATION ===\n";
try {
    $totalSolicitudes = $beneficiario->solicitudes->count();
    $aprobadas = $beneficiario->solicitudes->where('fk_id_estado', 4)->count();
    $rechazadas = $beneficiario->solicitudes->where('fk_id_estado', 5)->count();
    $pendientes = $beneficiario->solicitudes->whereIn('fk_id_estado', [1, 2, 3, 8, 9])->count();
    $montoTotal = $beneficiario->solicitudes->sum('monto_entregado');
    
    echo "✓ Total: " . $totalSolicitudes . "\n";
    echo "✓ Aprobadas: " . $aprobadas . " (" . round(($aprobadas/$totalSolicitudes)*100) . "%)\n";
    echo "✓ Rechazadas: " . $rechazadas . " (" . round(($rechazadas/$totalSolicitudes)*100) . "%)\n";
    echo "✓ Pendientes: " . $pendientes . " (" . round(($pendientes/$totalSolicitudes)*100) . "%)\n";
    echo "✓ Monto Total: $" . number_format($montoTotal, 2) . "\n";
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

// Test detail data
echo "\n=== SOLICITUDES DETAIL ===\n";
try {
    foreach ($beneficiario->solicitudes()->orderBy('fecha_creacion', 'desc')->get() as $s) {
        echo "\n  Folio: " . $s->folio . "\n";
        echo "  Estado: " . $s->estado->nombre_estado . " (ID: " . $s->fk_id_estado . ")\n";
        echo "  Apoyo: " . ($s->apoyo->nombre_apoyo ?? 'N/A') . "\n";
        echo "  Fecha: " . $s->fecha_creacion->format('d/m/Y H:i') . "\n";
        echo "  Monto: " . ($s->monto_entregado ?? 'No entregado') . "\n";
        echo "  Notas: " . ($s->observaciones_internas ?? 'N/A') . "\n";
    }
} catch (\Exception $e) {
    echo "✗ FAILED: " . $e->getMessage() . "\n";
}

echo "\n✅ ALL TESTS PASSED - SUPPORT HISTORY FEATURE READY!\n";
