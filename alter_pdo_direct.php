<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Intentando ALTER via PDO directo ===\n\n";

try {
    $pdo = DB::connection()->getPdo();
    
    // Paso 1: Drop FK
    $sql1 = "ALTER TABLE dbo.claves_seguimiento_privadas DROP CONSTRAINT claves_seguimiento_privadas_beneficiario_id_foreign";
    $pdo->exec($sql1);
    echo "✓ FK constraint removida\n";
    
    // Paso 2: Make nullable
    $sql2 = "ALTER TABLE dbo.claves_seguimiento_privadas ALTER COLUMN beneficiario_id INT NULL";
    $pdo->exec($sql2);
    echo "✓ Columna beneficiario_id ahora es NULL\n";
    
    // Paso 3: Recreate FK with SET NULL
    $sql3 = "ALTER TABLE dbo.claves_seguimiento_privadas ADD CONSTRAINT claves_seguimiento_privadas_beneficiario_id_foreign FOREIGN KEY (beneficiario_id) REFERENCES dbo.Usuarios(id_usuario) ON DELETE SET NULL";
    $pdo->exec($sql3);
    echo "✓ FK constraint recreada con ON DELETE SET NULL\n\n";
    
    echo "✅ ALTER TABLE exitoso\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
}
?>
