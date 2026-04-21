<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Crear beneficiario del sistema para casos no registrados ===\n\n";

try {
    // Verificar si ya existe
    $existing = DB::table('Usuarios')->where('email', 'sistema@sigo.admin')->first();
    
    if ($existing) {
        echo "✓ Beneficiario del sistema ya existe: ID " . $existing->id_usuario . "\n";
        echo "  Email: " . $existing->email . "\n";
    } else {
        // Crear beneficiario del sistema
        $id = DB::table('Usuarios')->insertGetId([
            'email' => 'sistema@sigo.admin',
            'password_hash' => bcrypt('SISTEMA-NO-LOGIN'),
            'tipo_usuario' => 'Beneficiario',
            'activo' => 1,
            'fecha_creacion' => \DB::raw('GETDATE()'),
        ], 'id_usuario');
        
        echo "✓ Beneficiario del sistema creado: ID " . $id . "\n";
    }
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
