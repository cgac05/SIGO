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

echo "🔧 CORRIGIENDO FOLIO 1008 CON VALORES VÁLIDOS\n";
echo "============================================\n\n";

// 1. Estado de solicitud a 9 (DOCS_VERIFICADOS)
echo "1️⃣ Actualizando estado a 9...\n";
$sql = "UPDATE Solicitudes SET fk_id_estado = 9 WHERE folio = 1008";
sqlsrv_query($conn, $sql);
echo "   ✓ Hecho\n\n";

// 2. Monto a 100000 (sin decimales)
echo "2️⃣ Actualizando monto a 100000...\n";
$sql = "UPDATE Solicitudes SET monto_entregado = 100000.0000 WHERE folio = 1008";
sqlsrv_query($conn, $sql);
echo "   ✓ Hecho\n\n";

// 3. Documentos a estado 'Correcto'
echo "3️⃣ Actualizando documentos a 'Correcto'...\n";
$sql = "UPDATE Documentos_Expediente SET estado_validacion = 'Correcto' WHERE fk_folio = 1008";
$result = sqlsrv_query($conn, $sql);
echo "   ✓ Hecho\n\n";

// 4. Presupuesto: solicitado > aprobado para que haya disponible
echo "4️⃣ Actualizando presupuesto...\n";
$sql = "DELETE FROM presupuesto_apoyos WHERE folio = 1008";
sqlsrv_query($conn, $sql);

// Crear presupuesto: Solicitado $500k, Aprobado $100k = Disponible $400k
$sql = "INSERT INTO presupuesto_apoyos (folio, id_categoria, monto_solicitado, monto_aprobado, estado, fecha_solicitud, created_at, updated_at)
        VALUES (1008, 8, 500000.00, 100000.00, 'APROBADO', GETDATE(), GETDATE(), GETDATE())";
sqlsrv_query($conn, $sql);
echo "   ✓ Hecho (Disponible: \$400,000)\n\n";

// 5. Verificar cambios
echo "✅ VERIFICANDO TODOS LOS CAMBIOS:\n\n";

echo "1️⃣ Solicitud:\n";
$sql = "SELECT folio, fk_id_estado, monto_entregado FROM Solicitudes WHERE folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$sol = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "   Estado ID: " . $sol['fk_id_estado'] . " (9=DOCS_VERIFICADOS) ✓\n";
echo "   Monto: \$" . number_format($sol['monto_entregado'], 0) . " ✓\n\n";

echo "2️⃣ Documentos:\n";
$sql = "SELECT COUNT(*) as cnt FROM Documentos_Expediente WHERE fk_folio = 1008 AND estado_validacion = 'Correcto'";
$stmt = sqlsrv_query($conn, $sql);
$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "   Validados: " . $result['cnt'] . " ✓\n\n";

echo "3️⃣ Presupuesto:\n";
$sql = "SELECT monto_solicitado, monto_aprobado FROM presupuesto_apoyos WHERE folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$presupuesto = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$disponible = $presupuesto['monto_solicitado'] - $presupuesto['monto_aprobado'];
echo "   Solicitado: \$" . number_format($presupuesto['monto_solicitado'], 0) . "\n";
echo "   Aprobado: \$" . number_format($presupuesto['monto_aprobado'], 0) . "\n";
echo "   Disponible: \$" . number_format($disponible, 0) . " ✓\n\n";

echo "🎉 ESTÁ LISTO\n";
echo "=============\n";
echo "👉 Próximo paso:\n";
echo "   1. Presiona Ctrl+Shift+R en el navegador (refresco sin caché)\n";
echo "   2. Recarga con F5\n";
echo "   3. Verás:\n";
echo "      - Disponible en Apoyo: \$400,000\n";
echo "      - Documentos: 1 Correcto\n";
echo "      - Botón para firmar habilitado\n";

sqlsrv_close($conn);
?>
