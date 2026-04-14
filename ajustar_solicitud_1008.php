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

echo "🔄 AJUSTANDO SOLICITUD 1008\n";
echo "==========================\n\n";

// 1. Obtener un folio con documentos verificados
echo "1️⃣ Obteniendo apoyo con categoría...\n";
$sql = "SELECT TOP 1 id_apoyo, id_categoria, nombre_apoyo FROM Apoyos WHERE id_categoria IS NOT NULL ORDER BY id_apoyo";
$stmt = sqlsrv_query($conn, $sql);
$apoyo = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "✓ Apoyo: " . $apoyo['nombre_apoyo'] . " (ID: " . $apoyo['id_apoyo'] . ", Cat: " . $apoyo['id_categoria'] . ")\n\n";

// 2. Actualizar apoyo en solicitud 1008
echo "2️⃣ Actualizando apoyo en solicitud 1008...\n";
$sql = "UPDATE Solicitudes SET fk_id_apoyo = ? WHERE folio = 1008";
sqlsrv_query($conn, $sql, array($apoyo['id_apoyo']));
echo "✓ Actualizado\n\n";

// 3. Copiar documento de folio 1000
echo "3️⃣ Obteniendo documento de folio 1000...\n";
$sql = "SELECT TOP 1 * FROM Documentos_Expediente WHERE fk_folio = 1000";
$stmt = sqlsrv_query($conn, $sql);
$doc = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if($doc) {
    echo "✓ Documento encontrado: " . basename($doc['ruta_archivo']) . "\n";
    
    // Verificar si ya existe
    $sql = "SELECT COUNT(*) as cnt FROM Documentos_Expediente WHERE fk_folio = 1008";
    $stmt = sqlsrv_query($conn, $sql);
    $count = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if($count['cnt'] == 0) {
        // Copiar documento con valores válidos
        $sql = "INSERT INTO Documentos_Expediente (fk_folio, fk_id_tipo_doc, ruta_archivo, 
                estado_validacion, version, fecha_carga, origen_archivo, admin_status, fecha_verificacion, revisado_por)
                VALUES (1008, ?, ?, 'Validado', 1, GETDATE(), 'COPIA_PRUEBA', 'APROBADO', GETDATE(), 1)";
        
        $stmt = sqlsrv_query($conn, $sql, array($doc['fk_id_tipo_doc'], $doc['ruta_archivo']));
        
        if($stmt === false) {
            echo "⚠️  Error al copiar documento\n";
            print_r(sqlsrv_errors());
        } else {
            echo "✓ Documento copiado\n";
        }
    } else {
        echo "✓ Documento ya existe\n";
    }
}

echo "\n";

// 4. Asignar presupuesto con categoría válida
echo "4️⃣ Asignando presupuesto...\n";
$montoSolicitud = 100000;

// Verificar si ya existe
$sql = "SELECT COUNT(*) as cnt FROM presupuesto_apoyos WHERE folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$count = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

if($count['cnt'] == 0) {
    $sql = "INSERT INTO presupuesto_apoyos (folio, id_categoria, monto_solicitado, monto_aprobado, 
            estado, fecha_solicitud, created_at, updated_at)
            VALUES (1008, ?, ?, ?, 'APROBADO', GETDATE(), GETDATE(), GETDATE())";
    
    $stmt = sqlsrv_query($conn, $sql, array($apoyo['id_categoria'], $montoSolicitud, $montoSolicitud));
    
    if($stmt === false) {
        echo "⚠️ Error al asignar presupuesto\n";
        print_r(sqlsrv_errors());
    } else {
        echo "✓ Presupuesto asignado: \$" . number_format($montoSolicitud, 0) . "\n";
    }
} else {
    echo "✓ Presupuesto ya existe\n";
}

echo "\n";

// 5. Resumen final
echo "✅ SOLICITUD LISTA PARA PRUEBAS\n";
echo "================================\n";
echo "Folio: 1008\n";
echo "Apoyo: " . $apoyo['nombre_apoyo'] . "\n";
echo "Categoría: " . $apoyo['id_categoria'] . "\n";
echo "Monto: \$" . number_format($montoSolicitud, 0) . "\n";
echo "Documentos: Verificados ✓\n";
echo "Estado: DOCS_VERIFICADOS (Listo para firmar)\n\n";
echo "👉 Accede como directivo@test.com\n";
echo "🔗 URL: http://localhost/SIGO/solicitudes/proceso/1008\n";

sqlsrv_close($conn);
?>
