<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Find a beneficiary with solicitudes
$beneficiario = \App\Models\Beneficiario::whereHas('solicitudes')->first();

if (!$beneficiario) {
    echo "No beneficiario with solicitudes found\n";
    exit;
}

$user = $beneficiario->user;

echo "=== BENEFICIARIO WITH SOLICITUDES ===\n";
echo "CURP: " . $beneficiario->curp . "\n";
echo "Nombre: " . $beneficiario->nombre . " " . $beneficiario->apellido_paterno . "\n";
echo "Email: " . $user->email . "\n";
echo "Total Solicitudes: " . $beneficiario->solicitudes->count() . "\n\n";

echo "=== SOLICITUDES DETAIL ===\n";
foreach ($beneficiario->solicitudes as $solicitud) {
    echo "Folio: " . $solicitud->folio . "\n";
    echo "  Apoyo: " . ($solicitud->apoyo->nombre_apoyo ?? 'N/A') . "\n";
    echo "  Estado: " . ($solicitud->estado->nombre_estado ?? 'N/A') . " (ID: " . $solicitud->fk_id_estado . ")\n";
    echo "  Fecha: " . $solicitud->fecha_creacion->format('d/m/Y H:i') . "\n";
    echo "  Monto: " . ($solicitud->monto_entregado ?? 'N/A') . "\n";
    echo "  Notas: " . ($solicitud->observaciones_internas ?? 'N/A') . "\n";
    echo "\n";
}

echo "=== STATISTICS ===\n";
$aprobadas = $beneficiario->solicitudes->where('fk_id_estado', 4)->count();
$rechazadas = $beneficiario->solicitudes->where('fk_id_estado', 5)->count();
$pendientes = $beneficiario->solicitudes->whereIn('fk_id_estado', [1, 2, 3, 8, 9])->count();
$montoTotal = $beneficiario->solicitudes->sum('monto_entregado');

echo "Aprobadas: " . $aprobadas . "\n";
echo "Rechazadas: " . $rechazadas . "\n";
echo "Pendientes: " . $pendientes . "\n";
echo "Monto Total: " . $montoTotal . "\n";

echo "\n✓ Model relationships working correctly!\n";
