<?php
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

$sql = "SELECT id_estado, nombre_estado FROM Cat_EstadosSolicitud ORDER BY id_estado";
$stmt = sqlsrv_query($conn, $sql);

echo "Estados disponibles:\n";
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "ID: " . $row['id_estado'] . " - " . $row['nombre_estado'] . "\n";
}

sqlsrv_close($conn);
?>
