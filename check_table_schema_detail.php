<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$result = DB::select("
    SELECT TABLE_SCHEMA, TABLE_NAME
    FROM INFORMATION_SCHEMA.TABLES
    WHERE TABLE_NAME = 'claves_seguimiento_privadas'
");

if (!empty($result)) {
    echo "Tabla encontrada:\n";
    echo "  Schema: " . $result[0]->TABLE_SCHEMA . "\n";
    echo "  Nombre: " . $result[0]->TABLE_NAME . "\n";
} else {
    echo "Tabla no encontrada\n";
}
?>
