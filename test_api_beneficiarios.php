<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEST API BENEFICIARIOS ===\n\n";

$curp = 'AICC050509HNTVMHA5';
echo "Buscando CURP: $curp\n\n";

$result = DB::table('Beneficiarios')
    ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
    ->where('Beneficiarios.curp', 'LIKE', '%' . $curp . '%')
    ->select(
        'Beneficiarios.curp',
        'Beneficiarios.nombre',
        'Beneficiarios.apellido_paterno',
        'Beneficiarios.apellido_materno',
        'Beneficiarios.telefono',
        'Beneficiarios.fecha_nacimiento',
        'Beneficiarios.genero',
        'Usuarios.id_usuario as fk_id_usuario',
        'Usuarios.email'
    )
    ->get();

if ($result->count() > 0) {
    echo "✓ Encontrados " . $result->count() . " resultado(s)\n\n";
    foreach ($result as $b) {
        echo "CURP: " . $b->curp . "\n";
        echo "Nombre: " . $b->nombre . " " . $b->apellido_paterno . " " . $b->apellido_materno . "\n";
        echo "Email: " . $b->email . "\n";
        echo "Teléfono: " . ($b->telefono ?? 'N/A') . "\n";
        echo "ID Usuario: " . $b->fk_id_usuario . "\n";
        echo "\n";
    }
} else {
    echo "✗ No encontrado\n";
}

echo "\n=== TEST RESPUESTA JSON ===\n\n";

$beneficiarios = $result->map(function ($b) {
    return (object)[
        'curp' => $b->curp,
        'nombre_completo' => trim($b->nombre . ' ' . ($b->apellido_paterno ?? '') . ' ' . ($b->apellido_materno ?? '')),
        'nombre' => $b->nombre,
        'email' => $b->email,
        'telefono' => $b->telefono,
        'fk_id_usuario' => $b->fk_id_usuario,
    ];
});

echo json_encode($beneficiarios, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
?>
