<?php

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== BÚSQUEDA: MovimientoPresupuestario::create() con columnas antiguas ===\n\n";

$files_to_check = [
    'app/Services/PresupuestaryControlService.php',
    'app/Services/GestionInventarioService.php',
    'app/Services/PresupuestoService.php',
    'app/Services/FirmaElectronicaService.php',
];

$bad_columns = ['id_presupuesto_apoyo', 'folio_solicitud', "'tipo'", 'id_usuario', 'fecha_cambio', 'estado'];

foreach ($files_to_check as $file) {
    echo "📄 {$file}\n";
    $content = file_get_contents(__DIR__ . '/' . $file);
    
    // Buscar MovimientoPresupuestario::create(
    if (preg_match('/MovimientoPresupuestario::create\(\s*\[([^]]*)\]/s', $content, $matches)) {
        $create_array = $matches[1];
        echo "  🔴 ENCONTRADO MovimientoPresupuestario::create():\n";
        
        // Mostrar primeras líneas del array
        $lines = array_slice(explode("\n", substr($create_array, 0, 200)), 0, 5);
        foreach ($lines as $line) {
            if (!empty(trim($line))) {
                echo "    " . trim($line) . "\n";
            }
        }
    } else {
        echo "  ✅ No found\n";
    }
    echo "\n";
}

echo "✅ BÚSQUEDA COMPLETADA\n";
