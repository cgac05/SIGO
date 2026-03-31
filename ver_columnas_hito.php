<?php
// Ver columnas de hitos_apoyo

$conn = sqlsrv_connect('localhost', [
    'Database' => 'BD_SIGO',
    'UID' => 'sa',
    'PWD' => '1234'
]);

$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hitos_apoyo' ORDER BY ORDINAL_POSITION";
$stmt = sqlsrv_query($conn, $sql);

echo "=== COLUMNAS DE TABLA hitos_apoyo ===\n\n";
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . " (" . $row['DATA_TYPE'] . ")\n";
}

echo "\n";

// Ver ejemplo
$query = "SELECT TOP 1 * FROM hitos_apoyo WHERE fk_id_apoyo = 24 ORDER BY id_hito";
$result = sqlsrv_query($conn, $query);
$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC);

echo "=== EJEMPLO: Hito del Apoyo 24 ===\n\n";
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
