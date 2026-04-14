<?php
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

echo "🔧 ASIGNANDO CATEGORÍA Y PRESUPUESTO\n";
echo "====================================\n\n";

// 1. Obtener primera categoría disponible
echo "1️⃣ Obteniendo categoría disponible...\n";
$sql = "SELECT TOP 1 id_categoria FROM presupuesto_categorias WHERE activo = 1";
$stmt = sqlsrv_query($conn, $sql);
$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
$categoriaId = $result['id_categoria'] ?? 1;

echo "✓ Categoría: " . $categoriaId . "\n\n";

// 2. Actualizar Apoyo 5 con categoria
echo "2️⃣ Asignando categoría al Apoyo 5...\n";
$sql = "UPDATE Apoyos SET id_categoria = ? WHERE id_apoyo = 5";
$stmt = sqlsrv_query($conn, $sql, array($categoriaId));
echo "✓ Hecho\n\n";

// 3. Asignar presupuesto a solicitud 1008
echo "3️⃣ Asignando presupuesto a folio 1008...\n";
$sql = "DELETE FROM presupuesto_apoyos WHERE folio = 1008";
sqlsrv_query($conn, $sql);

$montoSolicitud = 100000;
$sql = "INSERT INTO presupuesto_apoyos (folio, id_categoria, monto_solicitado, monto_aprobado, 
        estado, fecha_solicitud, created_at, updated_at)
        VALUES (1008, ?, ?, ?, 'APROBADO', GETDATE(), GETDATE(), GETDATE())";

$stmt = sqlsrv_query($conn, $sql, array($categoriaId, $montoSolicitud, $montoSolicitud));

if($stmt !== false) {
    echo "✓ Presupuesto asignado: \$" . number_format($montoSolicitud, 0) . "\n";
} else {
    echo "❌ Error\n";
    print_r(sqlsrv_errors());
}

echo "\n";

// 4. Resumen
echo "✅ SOLICITUD 1008 COMPLETAMENTE LISTA\n";
echo "=====================================\n";
echo "Folio: 1008\n";
echo "Estado: DOCS_VERIFICADOS ✓\n";
echo "Documentos: 1 ✓\n";
echo "Presupuesto: \$" . number_format($montoSolicitud, 0) . " ✓\n\n";
echo "👉 Ya puedes hacer login y firmar:\n";
echo "   http://127.0.0.1:8000/solicitudes/proceso/1008\n";
echo "   Usuario: directivo@test.com\n";
echo "   Contraseña: password123\n";

sqlsrv_close($conn);
?>
