<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== ACTUALIZANDO EMAIL DEL BENEFICIARIO ===\n\n";

// Actualizar el email del beneficiario Christian Guillermo
$updated = DB::table('Beneficiarios')
    ->where('curp', 'AICC050509HNTVMHA5')
    ->update(['email' => 'chguavilaca@ittepic.edu.mx']);

if ($updated) {
    echo "✓ Email actualizado correctamente\n\n";
    
    // Verificar la actualización
    $beneficiario = DB::table('Beneficiarios')
        ->where('curp', 'AICC050509HNTVMHA5')
        ->first();
    
    echo "Datos actualizados:\n";
    echo "  Nombre: " . $beneficiario->nombre . "\n";
    echo "  CURP: " . $beneficiario->curp . "\n";
    echo "  Email: " . $beneficiario->email . "\n";
} else {
    echo "✗ No se pudo actualizar el email\n";
}

echo "\n=== FIN ===\n";
?>
