<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Ejecutando ALTER TABLE directo ===\n\n";

try {
    // Conectar directamente con PDO
    $pdo = DB::connection()->getPdo();
    
    // Disable CONSTRAINTS temporalmente
    $pdo->exec("ALTER TABLE dbo.Solicitudes NOCHECK CONSTRAINT ALL");
    
    // Hacer el ALTER
    $pdo->exec("ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL");
    
    // Re-enable CONSTRAINTS
    $pdo->exec("ALTER TABLE dbo.Solicitudes CHECK CONSTRAINT ALL");
    
    echo "✅ ALTER TABLE completed successfully!\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "\nAlternativa: Ejecuta esto manualmente en SQL Server Management Studio:\n\n";
    echo "ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL;\n";
}
?>
