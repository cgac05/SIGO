<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$beneficiario = \App\Models\Beneficiario::where('curp', 'AICC050509HNTVMHA5')->first();
if ($beneficiario && $beneficiario->user) {
    echo 'Usuario ID: ' . $beneficiario->user->id_usuario . "\n";
    echo 'Email: ' . $beneficiario->user->email . "\n";
    echo 'Profile Link: http://localhost:8000/admin/padron/' . $beneficiario->user->id_usuario . "\n";
} else {
    echo 'Beneficiario no encontrado';
}
