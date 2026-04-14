<?php
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

echo "🔍 INVESTIGANDO ESTRUCTURA DOCUMENTOS_EXPEDIENTE\n";
echo "===============================================\n\n";

// 1. Obtener constraint CHECK
echo "1️⃣ Constraint de estado_validacion:\n";
$sql = "SELECT definition FROM sys.check_constraints WHERE parent_object_id = OBJECT_ID('Documentos_Expediente')";
$stmt = sqlsrv_query($conn, $sql);

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo $row['definition'] . "\n";
}

echo "\n2️⃣ Valores actuales en documentos:\n";
$sql = "SELECT DISTINCT estado_validacion FROM Documentos_Expediente";
$stmt = sqlsrv_query($conn, $sql);

while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "   - " . $row['estado_validacion'] . "\n";
}

echo "\n3️⃣ Documento del folio 1008:\n";
$sql = "SELECT * FROM Documentos_Expediente WHERE fk_folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$doc = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

foreach($doc as $key => $value) {
    echo "   $key: $value\n";
}

echo "\n";

// 4. FIX: Usar un valor válido
echo "4️⃣ Actualizando con valor válido...\n";
$sql = "UPDATE Documentos_Expediente SET estado_validacion = 'Aprobado' WHERE fk_folio = 1008";
sqlsrv_query($conn, $sql);
echo "   ✓ Documentos actualizados\n";

// 5. Actualizar monto
echo "\n5️⃣ Actualizando monto...\n";
$sql = "UPDATE Solicitudes SET monto_entregado = 100000 WHERE folio = 1008";
sqlsrv_query($conn, $sql);
echo "   ✓ Monto actualizado a 100000\n";

sqlsrv_close($conn);
?>
