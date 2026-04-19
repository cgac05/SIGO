<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

$beneficiarios = DB::select("
    SELECT TOP 5 u.id_usuario, u.email, b.curp, b.nombre
    FROM Usuarios u
    LEFT JOIN Beneficiarios b ON u.id_usuario = b.fk_id_usuario
    WHERE b.curp IS NOT NULL
    ORDER BY u.id_usuario
");

if (empty($beneficiarios)) {
    echo "No hay beneficiarios registrados con CURP\n";
} else {
    echo "Beneficiarios registrados con CURP:\n";
    foreach ($beneficiarios as $ben) {
        echo "  - ID: " . $ben->id_usuario . " | Email: " . $ben->email . " | CURP: " . $ben->curp . "\n";
    }
}
?>
