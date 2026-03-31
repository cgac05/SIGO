<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Tablas en BD_SIGO:\n";
echo "═════════════════════════════════════\n";

$result = DB::select("
    SELECT TABLE_NAME 
    FROM INFORMATION_SCHEMA.TABLES 
    WHERE TABLE_SCHEMA = 'dbo'
    ORDER BY TABLE_NAME
");

foreach ($result as $row) {
    echo $row->TABLE_NAME . "\n";
}

// Verificar si las tablas necesarias existen
echo "\n\nTablas necesarias:\n";
echo "═════════════════════════════════════\n";

$requiredTables = [
    'Documentos_Expediente',
    'Apoyos',
    'Cat_EstadosSolicitud',
    'Solicitudes',
    'Usuarios'
];

$existingTables = array_column($result, 'TABLE_NAME');

foreach ($requiredTables as $table) {
    $exists = in_array($table, $existingTables) ? '✓' : '✗';
    echo "$exists $table\n";
}
