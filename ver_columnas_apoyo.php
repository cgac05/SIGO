<?php
// Script para ver columnas de la tabla Apoyos

$conn = sqlsrv_connect('localhost', [
    'Database' => 'BD_SIGO',
    'UID' => 'sa',
    'PWD' => '1234'
]);

if ($conn === false) {
    die("Conexión fallida: " . print_r(sqlsrv_errors(), true));
}

$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Apoyos' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);

echo "=== COLUMNAS DE TABLA APOYOS ===\n\n";
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")\n";
}

echo "\n";

$query = "SELECT * FROM Apoyos WHERE id_apoyo = 24";
$result = sqlsrv_query($conn, $query);
$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

echo "=== EJEMPLO: Apoyo ID 24 ===\n\n";
foreach ($row as $key => $value) {
    echo "$key: ";
    if ($value instanceof DateTime) {
        echo $value->format('Y-m-d H:i:s');
    } else {
        echo $value;
    }
    echo "\n";
}

sqlsrv_close($conn);
?>
