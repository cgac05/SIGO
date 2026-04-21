<?php
// Conectar como SA con Autenticación de Windows (Local)
$server = "localhost";
$database = "BD_SIGO";

try {
    // Usar autenticación integrada de Windows
    // Para PDO + sqlsrv: usar DSN sin especificar Integrated (usa autenticación del sistema)
    $conn = new PDO(
        "sqlsrv:server=$server;database=$database;TrustServerCertificate=yes",
        null, 
        null,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    
    echo "✅ Conectado a SQL Server con autenticación de Windows\n\n";
    
    // 1. Alter Beneficiarios
    echo "1. Ejecutando: ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL\n";
    $conn->exec("ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL");
    echo "   ✅ SUCCESS\n\n";
    
    // 2. Alter claves_seguimiento_privadas
    echo "2. Ejecutando: ALTER TABLE dbo.[claves_seguimiento_privadas] ALTER COLUMN [beneficiario_id] INT NULL\n";
    $conn->exec("ALTER TABLE dbo.[claves_seguimiento_privadas] ALTER COLUMN [beneficiario_id] INT NULL");
    echo "   ✅ SUCCESS\n\n";
    
    // Verificar cambios
    echo "3. Verificando cambios en BD...\n";
    $result = $conn->query("
        SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE (
            (TABLE_NAME = 'Beneficiarios' AND COLUMN_NAME = 'fk_id_usuario')
            OR 
            (TABLE_NAME = 'claves_seguimiento_privadas' AND COLUMN_NAME = 'beneficiario_id')
        )
        ORDER BY TABLE_NAME
    ");
    
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $nullable = $row['IS_NULLABLE'] === 'YES' ? '✅ YES' : '❌ NO';
        echo "   {$row['TABLE_NAME']}.{$row['COLUMN_NAME']}: $nullable\n";
    }
    
    echo "\n✅✅✅ TODOS LOS CAMBIOS COMPLETADOS CON ÉXITO ✅✅✅\n\n";
    echo "PRÓXIMOS PASOS:\n";
    echo "1. Ejecuta: php artisan migrate\n";
    echo "2. Ejecuta: php test_beneficiario_parcial.php\n";
    echo "3. Prueba en navegador: http://localhost:8000/admin/caso-a/momento-uno\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "ALTERNATIVA: Ejecuta manualmente en SQL Server Management Studio:\n\n";
    echo "-- Conecta como SA con autenticación de Windows\n";
    echo "ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL;\n";
    echo "ALTER TABLE dbo.[claves_seguimiento_privadas] ALTER COLUMN [beneficiario_id] INT NULL;\n";
}
?>
