<?php
// Direct PDO connection to SQL Server
$dsn = 'sqlsrv:Server=localhost,1433;Database=BD_SIGO';
$username = 'SigoWebAppUser';
$password = 'UsuarioSigo159';

try {
    $pdo = new PDO($dsn, $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // First check what tables exist
    $tables = $pdo->query("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tablas disponibles: " . implode(', ', $tables) . "\n";
    
    // Try with schema prefix
    $sql = "ALTER TABLE dbo.Documentos_Expediente ALTER COLUMN origen_archivo VARCHAR(100)";
    $pdo->exec($sql);
    
    echo "✓ Columna origen_archivo ampliada a VARCHAR(100)\n";
    exit(0);
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>
