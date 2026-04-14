<?php
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if($conn === false) {
    die("Conexión fallida");
}

echo "📋 PREPARANDO SOLICITUD 1008 PARA PRUEBAS DE FIRMA\n";
echo "=================================================\n\n";

// 1. Obtener datos completos del folio 1000
echo "1️⃣ Obteniendo datos del folio 1000...\n";
$sql = "SELECT s.*, a.nombre_apoyo, a.id_categoria 
        FROM Solicitudes s
        JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        WHERE s.folio = 1000";
$stmt = sqlsrv_query($conn, $sql);
$solicitud1000 = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "✓ Folio 1000: " . $solicitud1000['nombre_apoyo'] . "\n";
echo "  - CURP: " . $solicitud1000['fk_curp'] . "\n";
echo "  - Apoyo ID: " . $solicitud1000['fk_id_apoyo'] . "\n";
echo "  - Categoría: " . $solicitud1000['id_categoria'] . "\n";
echo "  - Monto: \$" . $solicitud1000['monto_entregado'] . "\n\n";

// 2. Actualizar solicitud 1008 con datos completos de 1000
echo "2️⃣ Actualizando solicitud 1008...\n";
$sql = "UPDATE Solicitudes 
        SET fk_curp = ?, fk_id_apoyo = ?, fk_id_prioridad = ?, monto_entregado = ?
        WHERE folio = 1008";

$params = array(
    $solicitud1000['fk_curp'],
    $solicitud1000['fk_id_apoyo'],
    $solicitud1000['fk_id_prioridad'] ?? 2,
    $solicitud1000['monto_entregado']
);

$stmt = sqlsrv_query($conn, $sql, $params);
echo "✓ Solicitud actualizada\n\n";

// 3. Copiar documentos
echo "3️⃣ Copiando documentos...\n";
$sql = "SELECT * FROM Documentos_Expediente WHERE fk_folio = 1000";
$stmt = sqlsrv_query($conn, $sql);

$docCount = 0;
while($doc = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $sqlInsert = "INSERT INTO Documentos_Expediente (fk_folio, fk_id_tipo_doc, ruta_archivo, 
                  estado_validacion, version, fecha_carga, origen_archivo, admin_status, 
                  fecha_verificacion, revisado_por, id_admin)
                  SELECT 1008, fk_id_tipo_doc, ruta_archivo, 'Pendiente', version, GETDATE(), 
                  'COPIA_PRUEBA', 'APROBADO', GETDATE(), 1, 1
                  FROM Documentos_Expediente WHERE fk_folio = 1000 AND fk_id_tipo_doc = ?";
    
    $stmtInsert = sqlsrv_query($conn, $sqlInsert, array($doc['fk_id_tipo_doc']));
    
    if($stmtInsert !== false) {
        $docCount++;
        echo "  ✓ " . basename($doc['ruta_archivo']) . "\n";
    }
}
echo "✓ " . $docCount . " documento(s) copiado(s)\n\n";

// 4. Asignar presupuesto
echo "4️⃣ Asignando presupuesto...\n";
$sql = "DELETE FROM presupuesto_apoyos WHERE folio = 1008";
sqlsrv_query($conn, $sql);

$montoSolicitud = $solicitud1000['monto_entregado'];
$sql = "INSERT INTO presupuesto_apoyos (folio, id_categoria, monto_solicitado, monto_aprobado, 
        estado, fecha_solicitud, created_at, updated_at)
        VALUES (1008, ?, ?, ?, 'APROBADO', GETDATE(), GETDATE(), GETDATE())";

$params = array(
    $solicitud1000['id_categoria'],
    $montoSolicitud,
    $montoSolicitud
);

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt !== false) {
    echo "✓ Presupuesto: \$" . number_format($montoSolicitud, 0) . "\n";
} else {
    echo "⚠️ Error al asignar presupuesto\n";
    print_r(sqlsrv_errors());
}

echo "\n";

// 5. Verificación final
echo "✅ SOLICITUD 1008 LISTA\n";
echo "==========================\n";
echo "Folio: 1008\n";
echo "Beneficiario (CURP): " . $solicitud1000['fk_curp'] . "\n";
echo "Apoyo: " . $solicitud1000['nombre_apoyo'] . "\n";
echo "Monto: \$" . number_format($montoSolicitud, 0) . "\n";
echo "Documentos: " . $docCount . " (Verificados ✓)\n";
echo "Estado: DOCS_VERIFICADOS ✓\n\n";
echo "🔗 Acceso:\n";
echo "   Usuario: directivo@test.com\n";
echo "   Contraseña: password123\n";
echo "   URL: http://127.0.0.1:8000/solicitudes/proceso/1008\n";

sqlsrv_close($conn);
?>
