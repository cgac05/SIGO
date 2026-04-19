<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEST: Búsqueda AJAX por NOMBRE ===\n\n";

// Simular búsqueda por "Christian"
$query = 'christian';

$beneficiarios = DB::table('Beneficiarios')
    ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
    ->where(function ($q) use ($query) {
        $q->where('Beneficiarios.curp', 'LIKE', "%$query%")
          ->orWhere('Beneficiarios.nombre', 'LIKE', "%$query%")
          ->orWhere('Usuarios.email', 'LIKE', "%$query%");
    })
    ->select(
        'Beneficiarios.curp',
        'Beneficiarios.nombre',
        'Beneficiarios.apellido_paterno',
        'Beneficiarios.apellido_materno',
        'Usuarios.id_usuario as fk_id_usuario',
        'Usuarios.email'
    )
    ->limit(10)
    ->get();

echo "Búsqueda por: '" . $query . "'\n";
echo "Resultados encontrados: " . $beneficiarios->count() . "\n\n";

foreach ($beneficiarios as $b) {
    echo "✓ " . $b->nombre . " " . $b->apellido_paterno . "\n";
    echo "  CURP: " . ($b->curp ? $b->curp : 'NULL ⚠️') . "\n";
    echo "  Usuario ID: " . $b->fk_id_usuario . "\n";
    echo "  Email: " . $b->email . "\n\n";
}
?>
