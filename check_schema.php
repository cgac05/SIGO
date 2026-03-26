<?php
// Conexión directa a SQL Server sin Laravel
$host = 'localhost';
$database = 'BD_SIGO';
$user = 'SigoWebAppUser';
$password = 'UsuarioSigo159';

$connectionOptions = array(
    "UID" => $user,
    "PWD" => $password,
    "Database" => $database,
    "Encrypt" => "no",
    "TrustServerCertificate" => "Yes"
);

try {
    $conn = sqlsrv_connect($host, $connectionOptions);
    if ($conn === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    echo "\n=== SCHEMA DE DOCUMENTOS_EXPEDIENTE ===\n\n";
    
    // Get all columns
    $query = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Documentos_Expediente' ORDER BY ORDINAL_POSITION";
    $result = sqlsrv_query($conn, $query);
    
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    
    echo "COLUMN NAME                    | DATA TYPE            | Nullable\n";
    echo str_repeat("-", 80) . "\n";
    
    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
        echo sprintf(
            "%-30s | %-20s | %s\n",
            $row['COLUMN_NAME'],
            $row['DATA_TYPE'],
            $row['IS_NULLABLE']
        );
    }
    
    echo "\n\nFirst 3 rows of data:\n";
    echo str_repeat("=", 80) . "\n";
    
    $query = "SELECT TOP 3 * FROM Documentos_Expediente";
    $result = sqlsrv_query($conn, $query);
    
    if ($result !== false) {
        $rowNum = 1;
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
            echo "Row $rowNum:\n";
            foreach ($row as $key => $value) {
                echo "  $key: ";
                if (is_object($value) && get_class($value) === 'DateTime') {
                    echo $value->format('Y-m-d H:i:s');
                } else {
                    echo json_encode($value);
                }
                echo "\n";
            }
            echo "\n";
            $rowNum++;
        }
    } else {
        echo "Error: " . print_r(sqlsrv_errors(), true);
    }
    
    sqlsrv_close($conn);
} catch (Exception $e) {
    echo "Exception: " . $e->getMessage();
}
