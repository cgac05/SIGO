<?php
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use App\Services\AdministrativeVerificationService;

$service = app(AdministrativeVerificationService::class);

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  BÚSQUEDA DE SOLICITUDES PENDIENTES\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$solicitudes = $service->getSolicitudesPendientes(10);
$stats = $service->getVerificationStats();

echo "Estadísticas:\n";
echo "  Pendientes: " . $stats['pendientes'] . "\n";
echo "  Aceptados: " . $stats['aceptados'] . "\n";
echo "  Rechazados: " . $stats['rechazados'] . "\n\n";

if (count($solicitudes) > 0) {
    echo "✓ Solicitudes encontradas: " . count($solicitudes) . "\n\n";
    foreach ($solicitudes as $i => $item) {
        echo "  " . ($i + 1) . ". Folio: {$item['solicitud']->folio}\n";
        echo "     Beneficiario: {$item['solicitud']->beneficiario->nombre}\n";
        echo "     Apoyo: {$item['apoyo']->nombre_apoyo}\n";
        echo "     Documentos: " . count($item['documentos']) . "\n";
    }
} else {
    echo "✗ No hay solicitudes pendientes\n\n";
    echo "Verificando documentos en base:\n";
    
    $allDocs = \App\Models\Documento::limit(5)->get();
    echo "  Total documentos encontrados: " . count($allDocs) . "\n";
    
    foreach ($allDocs as $doc) {
        echo "\n  - Documento: {$doc->id_documento}\n";
        echo "    admin_status: " . ($doc->admin_status ?? 'NULL') . "\n";
        echo "    Folio: {$doc->fk_folio}\n";
    }
}

echo "\n════════════════════════════════════════════════════════════════\n\n";
