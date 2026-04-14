<?php
// Conexión a SQL Server
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if($conn === false) {
    echo "Conexión fallida.\n";
    die(print_r(sqlsrv_errors(), true));
}

// Consultar estructura de Documentos_Expediente
$sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Documentos_Expediente' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);

if($stmt === false) {
    echo "Consulta fallida.\n";
    die(print_r(sqlsrv_errors(), true));
}

echo "\nEstructura de tabla Documentos_Expediente:\n";
echo "==================================\n";
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $nullable = $row['IS_NULLABLE'] === 'NO' ? 'NOT NULL' : 'NULL';
    echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ") " . $nullable . "\n";
}

// Consultar estructura de presupuesto_apoyos
$sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'presupuesto_apoyos' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);

if($stmt === false) {
    echo "\nConsulta presupuesto_apoyos fallida.\n";
} else {
    echo "\nEstructura de tabla presupuesto_apoyos:\n";
    echo "==================================\n";
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $nullable = $row['IS_NULLABLE'] === 'NO' ? 'NOT NULL' : 'NULL';
        echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ") " . $nullable . "\n";
    }
    sqlsrv_free_stmt($stmt);
}

// Consultar estructura de Apoyos
$sql = "SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Apoyos' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);

if($stmt === false) {
    echo "\nConsulta Apoyos fallida.\n";
} else {
    echo "Estructura de tabla Apoyos:\n";
    echo "==================================\n";
    while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $nullable = $row['IS_NULLABLE'] === 'NO' ? 'NOT NULL' : 'NULL';
        echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ") " . $nullable . "\n";
    }
    sqlsrv_free_stmt($stmt);
}

sqlsrv_close($conn);
?>
