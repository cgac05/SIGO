<?php
// Conectar directamente a SQL Server sin Laravel
$server = "localhost";
$database = "BD_SIGO";
$uid = "SigoWebAppUser";
$pwd = "UsuarioSigo159";

try {
    // PDO connection string
    $conn = new PDO("sqlsrv:server=$server;database=$database", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✅ Connected to SQL Server\n\n";
    
    // First, let's disable constraints
    echo "Disabling constraints...\n";
    $conn->exec("EXEC sp_MSForEachTable 'ALTER TABLE ? NOCHECK CONSTRAINT ALL'");
    
    // Now alter the column
    echo "Altering column fk_id_usuario to nullable...\n";
    $conn->exec("ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL");
    
    // Re-enable constraints
    echo "Re-enabling constraints...\n";
    $conn->exec("EXEC sp_MSForEachTable 'ALTER TABLE ? CHECK CONSTRAINT ALL'");
    
    echo "\n✅ SUCCESS! fk_id_usuario is now nullable\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "ALTERNATE SOLUTION:\n";
    echo "Ejecuta manualmente en SQL Server Management Studio:\n\n";
    echo "-- Disable constraints\n";
    echo "EXEC sp_MSForEachTable 'ALTER TABLE ? NOCHECK CONSTRAINT ALL'\n\n";
    echo "-- Alter the column\n";
    echo "ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL\n\n";
    echo "-- Re-enable constraints\n";
    echo "EXEC sp_MSForEachTable 'ALTER TABLE ? CHECK CONSTRAINT ALL'\n";
}
?>
