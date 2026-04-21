<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Alterando tabla claves_seguimiento_privadas ===\n\n";

try {
    // Primero, dropear la foreign key constraint
    DB::statement("
        ALTER TABLE dbo.claves_seguimiento_privadas
        DROP CONSTRAINT claves_seguimiento_privadas_beneficiario_id_foreign
    ");
    echo "✓ FK constraint removida\n";
    
    // Ahora hacer la columna nullable
    DB::statement("
        ALTER TABLE dbo.claves_seguimiento_privadas
        ALTER COLUMN beneficiario_id INT NULL
    ");
    echo "✓ Columna beneficiario_id ahora es NULL\n";
    
    // Recrear la FK constraint con ON DELETE SET NULL
    DB::statement("
        ALTER TABLE dbo.claves_seguimiento_privadas
        ADD CONSTRAINT claves_seguimiento_privadas_beneficiario_id_foreign
        FOREIGN KEY (beneficiario_id)
        REFERENCES dbo.Usuarios(id_usuario)
        ON DELETE SET NULL
    ");
    echo "✓ FK constraint recreada con ON DELETE SET NULL\n\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
