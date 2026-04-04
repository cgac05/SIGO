<?php
// Script para verificar y crear columna peso_maximo_mb

$servername = "localhost";
$username = "SigoWebAppUser";
$password = "UsuarioSigo159";
$dbname = "BD_SIGO";
$connectionInfo = [
    "Database" => $dbname,
    "UID" => $username,
    "PWD" => $password,
];

try {
    $conn = sqlsrv_connect($servername, $connectionInfo);
    if (!$conn) throw new Exception("Connection failed");

    // Verificar estructura actual
    $sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'Cat_TiposDocumento'
            ORDER BY ORDINAL_POSITION";
    
    $result = sqlsrv_query($conn, $sql);
    if (!$result) throw new Exception("Query failed");

    echo "📋 Current columns in Cat_TiposDocumento:\n";
    echo "=============================================\n";
    
    $columnExists = false;
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        $nullable = $row['IS_NULLABLE'] === 'YES' ? 'NULL' : 'NOT NULL';
        echo "- {$row['COLUMN_NAME']} ({$row['DATA_TYPE']}) {$nullable}\n";
        
        if ($row['COLUMN_NAME'] === 'peso_maximo_mb') {
            $columnExists = true;
        }
    }
    
    if ($columnExists) {
        echo "\n✅ peso_maximo_mb column already exists!\n";
    } else {
        echo "\n❌ peso_maximo_mb column does NOT exist\n";
        echo "Creating column...\n";
        
        // Crear la columna
        $createSql = "ALTER TABLE Cat_TiposDocumento 
                      ADD peso_maximo_mb INT NULL DEFAULT 5";
        
        $createResult = sqlsrv_query($conn, $createSql);
        if ($createResult) {
            echo "✅ Column peso_maximo_mb created successfully!\n";
            echo "   - Type: INT\n";
            echo "   - Nullable: YES\n";
            echo "   - Default: 5 MB\n";
        } else {
            echo "❌ Error creating column: " . print_r(sqlsrv_errors(), true) . "\n";
        }
    }
    
    sqlsrv_close($conn);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
