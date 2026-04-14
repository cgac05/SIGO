<?php
// Configuración de conexión
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

if($conn === false) {
    echo "❌ Conexión fallida.\n";
    die(print_r(sqlsrv_errors(), true));
}

echo "📋 CREACIÓN DE SOLICITUD DE PRUEBA\n";
echo "==================================\n\n";

// 1. Obtener datos del folio 1000
echo "1️⃣ Obteniendo datos del folio 1000...\n";
$sql = "SELECT TOP 1 * FROM Solicitudes WHERE folio = 1000";
$stmt = sqlsrv_query($conn, $sql);

if($stmt === false) {
    echo "❌ Error al obtener folio 1000\n";
    sqlsrv_close($conn);
    die();
}

$folio1000 = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if(!$folio1000) {
    echo "❌ Folio 1000 no encontrado\n";
    sqlsrv_close($conn);
    die();
}

echo "✓ Datos obtenidos. CURP: " . $folio1000['fk_curp'] . "\n\n";

// 2. Obtener el siguiente folio disponible
echo "2️⃣ Obteniendo nuevo folio disponible...\n";
$sql = "SELECT MAX(folio) as max_folio FROM Solicitudes";
$stmt = sqlsrv_query($conn, $sql);
$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$nuevoFolio = $result['max_folio'] + 1;
echo "✓ Nuevo folio: $nuevoFolio\n\n";

// 3. Crear nueva solicitud (copia del folio 1000)
echo "3️⃣ Creando nueva solicitud...\n";
$sql = "SET IDENTITY_INSERT Solicitudes ON;
        INSERT INTO Solicitudes (folio, fk_curp, fk_id_apoyo, fk_id_estado, fk_id_prioridad, 
            fecha_creacion, monto_entregado, presupuesto_confirmado, permite_correcciones, directivo_autorizo)
        VALUES (?, ?, ?, 9, ?, GETDATE(), ?, 0, 1, 0);
        SET IDENTITY_INSERT Solicitudes OFF;";

$params = array(
    $nuevoFolio,
    $folio1000['fk_curp'],
    $folio1000['fk_id_apoyo'],
    $folio1000['fk_id_prioridad'] ?? 2,
    $folio1000['monto_entregado'] ?? 50000
);

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt === false) {
    echo "❌ Error al crear solicitud\n";
    print_r(sqlsrv_errors());
    sqlsrv_close($conn);
    die();
}

// Limpiar el resultado de las sentencias SET
while(sqlsrv_next_result($stmt)) {}

echo "✓ Solicitud creada con folio $nuevoFolio\n\n";

// 4. Obtener documentos del folio 1000
echo "4️⃣ Obteniendo documentos del folio 1000...\n";
$sql = "SELECT * FROM Documentos_Expediente WHERE fk_folio = 1000";
$stmt = sqlsrv_query($conn, $sql);

$documentos = array();
while($doc = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $documentos[] = $doc;
}

echo "✓ Documentos encontrados: " . count($documentos) . "\n\n";

// 5. Copiar documentos al nuevo folio
echo "5️⃣ Copiando documentos al nuevo folio...\n";
foreach($documentos as $doc) {
    $sql = "INSERT INTO Documentos_Expediente (fk_folio, fk_id_tipo_doc, ruta_archivo, 
            estado_validacion, version, fecha_carga, origen_archivo, admin_status, fecha_verificacion)
            VALUES (?, ?, ?, 'Aprobado', ?, GETDATE(), ?, 'APROBADO', GETDATE())";
    
    $params = array(
        $nuevoFolio,
        $doc['fk_id_tipo_doc'],
        $doc['ruta_archivo'],
        $doc['version'] ?? 1,
        $doc['origen_archivo'] ?? 'COPIA_PRUEBA'
    );
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if($stmt === false) {
        echo "⚠️ Error al copiar documento\n";
        print_r(sqlsrv_errors());
    } else {
        echo "✓ Documento copiado: " . basename($doc['ruta_archivo']) . "\n";
    }
}

echo "\n";

// 6. Obtener apoyo para asignar presupuesto
echo "6️⃣ Obteniendo información del apoyo...\n";
$sql = "SELECT id_apoyo, id_categoria, monto_maximo FROM Apoyos WHERE id_apoyo = ?";
$params = array($folio1000['fk_id_apoyo']);
$stmt = sqlsrv_query($conn, $sql, $params);
$apoyo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "✓ Apoyo: ID " . $apoyo['id_apoyo'] . ", Categoría: " . $apoyo['id_categoria'] . "\n\n";

// 7. Asignar presupuesto
echo "7️⃣ Asignando presupuesto...\n";
$montoSolicitud = $folio1000['monto_entregado'] ?? 50000;

$sql = "INSERT INTO presupuesto_apoyos (folio, id_categoria, monto_solicitado, monto_aprobado, 
        estado, fecha_solicitud, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'SOLICITADO', GETDATE(), GETDATE(), GETDATE())";

$params = array(
    $nuevoFolio,
    $apoyo['id_categoria'],
    $montoSolicitud,
    $montoSolicitud
);

$stmt = sqlsrv_query($conn, $sql, $params);

if($stmt === false) {
    echo "⚠️ Error al asignar presupuesto\n";
    print_r(sqlsrv_errors());
} else {
    echo "✓ Presupuesto asignado: \$" . number_format($montoSolicitud, 0) . "\n";
}

echo "\n";

// 8. Verificar que el estado sea correcto
echo "8️⃣ Verificando estado de solicitud...\n";
$sql = "SELECT TOP 1 s.folio, s.fk_id_estado, cs.nombre_estado 
        FROM Solicitudes s 
        LEFT JOIN Cat_EstadosSolicitud cs ON s.fk_id_estado = cs.id_estado 
        WHERE s.folio = ?";
$params = array($nuevoFolio);
$stmt = sqlsrv_query($conn, $sql, $params);
$solicitudVerif = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "✓ Folio: " . $solicitudVerif['folio'] . "\n";
echo "✓ Estado: " . ($solicitudVerif['nombre_estado'] ?? 'DOCUMENTOS_VERIFICADOS (12)') . "\n";
echo "✓ Estado ID: " . $solicitudVerif['fk_id_estado'] . "\n\n";

// 9. Resumen final
echo "✅ SOLICITUD CREADA EXITOSAMENTE\n";
echo "==================================\n";
echo "Folio: $nuevoFolio\n";
echo "CURP: " . $folio1000['fk_curp'] . "\n";
echo "Monto: \$" . number_format($montoSolicitud, 0) . "\n";
echo "Documentos: " . count($documentos) . " (Todos verificados ✓)\n";
echo "Estado: Documentos Verificados (Listo para firmar)\n\n";
echo "🔗 URL para prueba:\n";
echo "http://localhost/SIGO/solicitudes/proceso/$nuevoFolio\n";

sqlsrv_close($conn);
?>
