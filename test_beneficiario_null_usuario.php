<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Test: Insert Beneficiario with NULL fk_id_usuario ===\n\n";

try {
    // CURP válido: exactamente 18 caracteres
    $curp_test = 'ZZZX' . str_pad(rand(100000, 999999), 6, '0', STR_PAD_LEFT) . 'HNTVMH00';
    
    $inserted = DB::table('Beneficiarios')->insert([
        'curp' => $curp_test,
        'fk_id_usuario' => null,  // Test NULL
        'nombre' => 'Test Beneficiario',
        'apellido_paterno' => 'Apellido',
        'apellido_materno' => 'Materno',
        'fecha_nacimiento' => now()->subYears(30),
    ]);
    
    if ($inserted) {
        echo "✅ SUCCESS: Can insert with fk_id_usuario = NULL\n";
        
        // Verify
        $row = DB::table('Beneficiarios')->where('curp', $curp_test)->first();
        echo "  fk_id_usuario value: " . ($row->fk_id_usuario ?? 'NULL') . "\n";
        echo "  CURP: {$row->curp}\n";
        echo "  Nombre: {$row->nombre}\n";
    }
} catch (\Exception $e) {
    echo "❌ FAILED: " . $e->getMessage() . "\n";
}
?>
