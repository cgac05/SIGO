<?php
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if($conn === false) {
    die("❌ Conexión fallida\n");
}

echo "🔍 VERIFICANDO ESTADO DE BD PARA FOLIO 1008\n";
echo "==========================================\n\n";

// 1. Verificar estado de solicitud
echo "1️⃣ Estado de Solicitud:\n";
$sql = "SELECT folio, fk_id_estado, monto_entregado FROM Solicitudes WHERE folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$sol = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "   Folio: " . $sol['folio'] . "\n";
echo "   Estado ID: " . $sol['fk_id_estado'] . " (9=DOCS_VERIFICADOS)\n";
echo "   Monto: " . $sol['monto_entregado'] . "\n\n";

// 2. Verificar documentos
echo "2️⃣ Documentos:\n";
$sql = "SELECT COUNT(*) as cnt, COUNT(CASE WHEN estado_validacion = 'Validado' THEN 1 END) as validados 
        FROM Documentos_Expediente WHERE fk_folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$docs = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "   Totales: " . $docs['cnt'] . "\n";
echo "   Validados: " . $docs['validados'] . "\n\n";

// 3. Verificar presupuesto
echo "3️⃣ Presupuesto:\n";
$sql = "SELECT * FROM presupuesto_apoyos WHERE folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$presupuesto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if($presupuesto) {
    echo "   Monto Solicitado: " . $presupuesto['monto_solicitado'] . "\n";
    echo "   Monto Aprobado: " . $presupuesto['monto_aprobado'] . "\n";
    echo "   Estado: " . $presupuesto['estado'] . "\n";
} else {
    echo "   ❌ NO EXISTE PRESUPUESTO\n";
}

echo "\n";

// 4. CORREGIR TODO
echo "🔧 CORRIGIENDO TODOS LOS DATOS...\n\n";

// 4a. Cambiar estado a 9 (DOCS_VERIFICADOS)
echo "4️⃣a Actualizando estado...\n";
$sql = "UPDATE Solicitudes SET fk_id_estado = 9 WHERE folio = 1008";
$result = sqlsrv_query($conn, $sql);
if($result) echo "   ✓ Estado actualizado a 9\n";

// 4b. Actualizar monto si es necesario
echo "4️⃣b Verificando monto...\n";
$sql = "UPDATE Solicitudes SET monto_entregado = 100000 WHERE folio = 1008 AND monto_entregado < 100000";
$result = sqlsrv_query($conn, $sql);
echo "   ✓ Monto verificado\n";

// 4c. Actualizar documentos
echo "4️⃣c Actualizando documentos...\n";
$sql = "UPDATE Documentos_Expediente 
        SET estado_validacion = 'Validado', admin_status = 'APROBADO',
            fecha_verificacion = GETDATE(), revisado_por = 1, id_admin = 1
        WHERE fk_folio = 1008";
$result = sqlsrv_query($conn, $sql);
echo "   ✓ Documentos validados\n";

// 4d. RECREAR presupuesto
echo "4️⃣d Recreando presupuesto...\n";
$sql = "DELETE FROM presupuesto_apoyos WHERE folio = 1008";
sqlsrv_query($conn, $sql);

$sql = "INSERT INTO presupuesto_apoyos (folio, id_categoria, monto_solicitado, monto_aprobado, estado, fecha_solicitud, created_at, updated_at)
        VALUES (1008, 8, 500000, 200000, 'APROBADO', GETDATE(), GETDATE(), GETDATE())";
$result = sqlsrv_query($conn, $sql);
if($result) echo "   ✓ Presupuesto recreado\n";

echo "\n";

// 5. VERIFICAR CAMBIOS
echo "✅ VERIFICANDO CAMBIOS:\n";
echo "\n1️⃣ Solicitud 1008:\n";
$sql = "SELECT folio, fk_id_estado, monto_entregado FROM Solicitudes WHERE folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$sol = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "   Estado: " . $sol['fk_id_estado'] . "\n";
echo "   Monto: \$" . number_format($sol['monto_entregado'], 0) . "\n";

echo "\n2️⃣ Documentos:\n";
$sql = "SELECT COUNT(*) as cnt FROM Documentos_Expediente WHERE fk_folio = 1008 AND estado_validacion = 'Validado'";
$stmt = sqlsrv_query($conn, $sql);
$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "   Validados: " . $result['cnt'] . "\n";

echo "\n3️⃣ Presupuesto:\n";
$sql = "SELECT monto_solicitado, monto_aprobado FROM presupuesto_apoyos WHERE folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$presupuesto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if($presupuesto) {
    echo "   Disponible: \$" . number_format($presupuesto['monto_solicitado'] - $presupuesto['monto_aprobado'], 0) . "\n";
}

echo "\n💡 Ahora haz:\n";
echo "   1. Presiona Ctrl+Shift+R en el navegador (refresco forzado)\n";
echo "   2. Luego F5 para recargar la página\n";
echo "   3. Debería mostrar valores correctos\n";

sqlsrv_close($conn);
?>
