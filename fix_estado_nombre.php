<?php
$serverName = "JDEV\\PARTIDA";
$connectionInfo = array(
    "Database" => "BD_SIGO",
    "UID" => "sa",
    "PWD" => "LocalSummer470"
);

$conn = sqlsrv_connect($serverName, $connectionInfo);

echo "🔍 VERIFICANDO NOMBRES DE ESTADOS\n";
echo "================================\n\n";

// Obtener todos los estados
$sql = "SELECT id_estado, nombre_estado FROM Cat_EstadosSolicitud ORDER BY id_estado";
$stmt = sqlsrv_query($conn, $sql);

echo "Estados disponibles:\n";
while($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "ID: " . $row['id_estado'] . " | Nombre: '" . $row['nombre_estado'] . "'\n";
}

echo "\n";

// Obtener estado actual del folio 1008
echo "Estado actual de folio 1008:\n";
$sql = "SELECT s.fk_id_estado, cs.nombre_estado 
        FROM Solicitudes s
        LEFT JOIN Cat_EstadosSolicitud cs ON s.fk_id_estado = cs.id_estado
        WHERE s.folio = 1008";
$stmt = sqlsrv_query($conn, $sql);
$result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

echo "ID: " . $result['fk_id_estado'] . "\n";
echo "Nombre: '" . ($result['nombre_estado'] ?? 'NULL') . "'\n\n";

// Ver qué string espera la vista
echo "⚠️ La vista espera:  'DOCUMENTOS_VERIFICADOS'\n";
echo "✓ Folio 1008 tiene: '" . ($result['nombre_estado'] ?? 'NADA') . "'\n\n";

if($result['nombre_estado'] !== 'DOCUMENTOS_VERIFICADOS') {
    echo "🔧 ACTUALIZANDO ESTADO AL NOMBRE CORRECTO...\n\n";
    
    // Buscar ID del estado DOCUMENTOS_VERIFICADOS
    $sql = "SELECT id_estado FROM Cat_EstadosSolicitud WHERE nombre_estado = 'DOCUMENTOS_VERIFICADOS'";
    $stmt = sqlsrv_query($conn, $sql);
    $estadoRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    if($estadoRow) {
        echo "Estados encontrado con ID: " . $estadoRow['id_estado'] . "\n";
        
        $sql = "UPDATE Solicitudes SET fk_id_estado = ? WHERE folio = 1008";
        sqlsrv_query($conn, $sql, array($estadoRow['id_estado']));
        
        echo "✓ Folio 1008 actualizado a estado ID: " . $estadoRow['id_estado'] . "\n";
    } else {
        echo "❌ Estado 'DOCUMENTOS_VERIFICADOS' no existe en Cat_EstadosSolicitud\n";
        echo "\n💡 Opción: Crear el estado...\n";
        
        $sql = "INSERT INTO Cat_EstadosSolicitud (nombre_estado) VALUES ('DOCUMENTOS_VERIFICADOS')";
        if(sqlsrv_query($conn, $sql)) {
            echo "✓ Estado creado\n";
            
            // Obtener el ID que se acaba de crear
            $sql = "SELECT id_estado FROM Cat_EstadosSolicitud WHERE nombre_estado = 'DOCUMENTOS_VERIFICADOS'";
            $stmt = sqlsrv_query($conn, $sql);
            $estadoRow = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            
            $sql = "UPDATE Solicitudes SET fk_id_estado = ? WHERE folio = 1008";
            sqlsrv_query($conn, $sql, array($estadoRow['id_estado']));
            
            echo "✓ Folio 1008 actualizado\n";
        }
    }
}

echo "\n✅ LISTO\n";
echo "   Recarga la página para ver los cambios\n";

sqlsrv_close($conn);
?>
