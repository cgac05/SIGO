<?php
// Simple verification script
require 'vendor/autoload.php';

$app = require 'bootstrap/app.php';
$kernel = $app->make('Illuminate\Contracts\Console\Kernel');
$kernel->bootstrap();

use Illuminate\Support\Facades\Schema;

echo "\n";
echo "════════════════════════════════════════════════════════════════\n";
echo "  VERIFICACIÓN DE CAMPOS ADMINISTRATIVOS\n";
echo "════════════════════════════════════════════════════════════════\n\n";

$campos = [
    'admin_status',
    'admin_observations', 
    'verification_token',
    'id_admin',
    'fecha_verificacion'
];

$todosCorrectos = true;

foreach ($campos as $campo) {
    if (Schema::hasColumn('Documentos_Expediente', $campo)) {
        echo "✓ $campo - EXISTE\n";
    } else {
        echo "✗ $campo - NO EXISTE\n";
        $todosCorrectos = false;
    }
}

echo "\n";
if ($todosCorrectos) {
    echo "✓ TODOS LOS CAMPOS ESTÁN CORRECTAMENTE AGREGADOS\n";
    echo "✓ El sistema administrativo está LISTO para usar\n";
} else {
    echo "✗ FALTAN CAMPOS\n";
    echo "Debes ejecutar el siguiente SQL en SQL Server:\n\n";
    echo file_get_contents(__DIR__ . '/ADMIN_FIELDS_SQL.sql');
}
echo "\n════════════════════════════════════════════════════════════════\n\n";
