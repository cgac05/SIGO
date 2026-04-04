<?php
// Quick check script para verificar si peso_maximo_mb existe

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
    if (!$conn) throw new Exception("Connection failed: " . print_r(sqlsrv_errors(), true));

    // Verificar columnas actuales
    $sql = "SELECT COLUMN_NAME 
            FROM INFORMATION_SCHEMA.COLUMNS 
            WHERE TABLE_NAME = 'Cat_TiposDocumento' 
            AND COLUMN_NAME = 'peso_maximo_mb'";
    
    $result = sqlsrv_query($conn, $sql);
    if (!$result) throw new Exception("Query failed: " . print_r(sqlsrv_errors(), true));

    $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);
    
    if ($row) {
        echo "✅ peso_maximo_mb column already exists\n";
    } else {
        echo "❌ peso_maximo_mb column does NOT exist\n";
        echo "Creating column...\n";
        
        // Crear la columna
        $createSql = "ALTER TABLE Cat_TiposDocumento 
                      ADD peso_maximo_mb INT NULL DEFAULT 5";
        
        $createResult = sqlsrv_query($conn, $createSql);
        if ($createResult) {
            echo "✅ Column created successfully\n";
        } else {
            echo "❌ Error creating column: " . print_r(sqlsrv_errors(), true) . "\n";
        }
    }
    
    sqlsrv_close($conn);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
