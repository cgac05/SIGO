<?php
// Temporary file to inspect database schema
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;

$columns = Schema::getColumns('Documentos_Expediente');

echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║         ESTRUCTURA ACTUAL - TABLA: DOCUMENTOS_EXPEDIENTE              ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

echo "CAMPO                            | TIPO            | Nullable | Default\n";
echo "─────────────────────────────────────────────────────────────────────────────\n";

$newFieldsToAdd = [
    'admin_status',
    'admin_observations',
    'verification_token',
    'id_admin',
    'fecha_verificacion'
];

foreach ($columns as $col) {
    $fieldName = $col['name'];
    $isDuplicate = in_array($fieldName, $newFieldsToAdd) ? " ⚠️ DUPLICATE" : "";
    
    $type = $col['type'] ?? 'unknown';
    $nullable = $col['nullable'] ? 'YES' : 'NO';
    $default = $col['default'] ?? '—';
    
    echo str_pad($fieldName, 32) . " | " . str_pad($type, 15) . " | " . str_pad($nullable, 8) . " | " . $default . $isDuplicate . "\n";
}

echo "\n╔════════════════════════════════════════════════════════════════════════╗\n";
echo "║  CAMPOS QUE SE AGREGARAN EN LA MIGRACION                              ║\n";
echo "╚════════════════════════════════════════════════════════════════════════╝\n\n";

foreach ($newFieldsToAdd as $field) {
    $exists = false;
    foreach ($columns as $col) {
        if ($col['name'] === $field) {
            $exists = true;
            break;
        }
    }
    echo ($exists ? "✗" : "✓") . " $field" . ($exists ? " (YA EXISTE)" : "") . "\n";
}

echo "\nTotal de campos actuales: " . count($columns) . "\n";
echo "Campos a agregar: " . count($newFieldsToAdd) . "\n";
?>
