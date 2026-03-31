<?php
$conn = sqlsrv_connect('localhost', [
    'Database' => 'BD_SIGO',
    'UID' => 'sa',
    'PWD' => '1234'
]);

$sql = "SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'hitos_apoyo' AND COLUMN_NAME = 'ultima_sincronizacion'";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['COLUMN_NAME'] . ": " . $row['DATA_TYPE'] . "\n";
}

sqlsrv_close($conn);
?>
