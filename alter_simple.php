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
    
    // Simply alter the column
    echo "Altering column fk_id_usuario to nullable...\n";
    $conn->exec("ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL");
    
    echo "✅ SUCCESS! fk_id_usuario is now nullable\n\n";
    
    // Verify
    echo "Verificando cambios...\n";
    $result = $conn->query("
        SELECT IS_NULLABLE 
        FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'Beneficiarios' 
        AND COLUMN_NAME = 'fk_id_usuario'
    ");
    $row = $result->fetch(PDO::FETCH_ASSOC);
    echo "fk_id_usuario IS_NULLABLE: " . $row['IS_NULLABLE'] . "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "ALTERNATE SOLUTION:\n";
    echo "Ejecuta manualmente en SQL Server Management Studio:\n\n";
    echo "ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL\n";
}
?>
