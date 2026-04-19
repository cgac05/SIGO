<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== BÚSQUEDA: Beneficiarios SIN CURP o con CURP NULL ===\n\n";

$beneficiariosSinCurp = DB::table('Beneficiarios')
    ->where(function($q) {
        $q->whereNull('curp')
          ->orWhere('curp', '');
    })
    ->leftJoin('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
    ->select(
        'Beneficiarios.curp',
        'Beneficiarios.nombre',
        'Beneficiarios.apellido_paterno',
        'Beneficiarios.apellido_materno',
        'Usuarios.id_usuario',
        'Usuarios.email'
    )
    ->get();

if (count($beneficiariosSinCurp) > 0) {
    echo "❌ Se encontraron " . count($beneficiariosSinCurp) . " beneficiario(s) SIN CURP:\n\n";
    foreach ($beneficiariosSinCurp as $b) {
        echo "  - Usuario ID: " . $b->id_usuario . "\n";
        echo "    Email: " . $b->email . "\n";
        echo "    Nombre: " . $b->nombre . " " . $b->apellido_paterno . "\n";
        echo "    CURP: " . ($b->curp ? "'" . $b->curp . "'" : "NULL") . "\n\n";
    }
} else {
    echo "✅ Todos los beneficiarios tienen CURP\n";
}

echo "\n=== BÚSQUEDA: Usuarios que son Beneficiarios pero NO tienen registro en Beneficiarios ===\n\n";

$usuariosSinBeneficiario = DB::table('Usuarios')
    ->where('tipo_usuario', 'Beneficiario')
    ->leftJoin('Beneficiarios', 'Usuarios.id_usuario', '=', 'Beneficiarios.fk_id_usuario')
    ->whereNull('Beneficiarios.curp')
    ->select(
        'Usuarios.id_usuario',
        'Usuarios.email',
        'Beneficiarios.curp'
    )
    ->get();

if (count($usuariosSinBeneficiario) > 0) {
    echo "❌ Se encontraron " . count($usuariosSinBeneficiario) . " usuario(s) de tipo Beneficiario SIN registro en Beneficiarios:\n\n";
    foreach ($usuariosSinBeneficiario as $u) {
        echo "  - Usuario ID: " . $u->id_usuario . "\n";
        echo "    Email: " . $u->email . "\n\n";
    }
} else {
    echo "✅ Todos los usuarios Beneficiarios tienen registro en Beneficiarios\n";
}
?>
