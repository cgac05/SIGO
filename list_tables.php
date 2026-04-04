<?php
// Script para listar todas las tablas de la BD

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

    // Listar todas las tablas
    $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME";
    $result = sqlsrv_query($conn, $sql);
    
    if (!$result) throw new Exception("Query failed");

    echo "📊 Tables in BD_SIGO:\n";
    echo "=====================\n";
    $count = 0;
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo ($count + 1) . ". " . $row['TABLE_NAME'] . "\n";
        $count++;
    }
    echo "\nTotal: $count tables\n";
    
    // Buscar tabla de tipos de documento
    echo "\n🔍 Searching for document-related tables...\n";
    $sql2 = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE='BASE TABLE' AND TABLE_NAME LIKE '%tipo%' OR TABLE_NAME LIKE '%documento%' OR TABLE_NAME LIKE '%Cat%'";
    $result2 = sqlsrv_query($conn, $sql2);
    
    while ($row = sqlsrv_fetch_array($result2, SQLSRV_FETCH_ASSOC)) {
        echo "   Found: " . $row['TABLE_NAME'] . "\n";
    }
    
    sqlsrv_close($conn);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
