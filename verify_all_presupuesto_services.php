<?php

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== VERIFICACIÓN FINAL: TODOS LOS SERVICIOS ===\n\n";

$files_to_check = [
    'app/Services/PresupuestaryControlService.php',
    'app/Services/GestionInventarioService.php',
    'app/Services/PresupuestoService.php',
];

$bad_columns = ['id_apoyo', 'costo_estimado', 'fecha_reserva', 'observaciones'];

foreach ($files_to_check as $file) {
    echo "📄 {$file}\n";
    $content = file_get_contents(__DIR__ . '/' . $file);
    
    $found_issues = [];
    foreach ($bad_columns as $col) {
        // Buscar patterns como 'id_apoyo' => o "id_apoyo" =>
        if (preg_match("/'$col'\\s*=>|\"$col\"\\s*=>/", $content)) {
            $found_issues[] = "'$col' (INSERT detected)";
        }
    }
    
    if (empty($found_issues)) {
        echo "  ✅ Sin columnas antiguas detectadas\n\n";
    } else {
        echo "  ❌ PROBLEMAS ENCONTRADOS:\n";
        foreach ($found_issues as $issue) {
            echo "    - {$issue}\n";
        }
        echo "\n";
    }
}

echo "✅ VERIFICACIÓN COMPLETADA\n";
