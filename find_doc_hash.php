<?php
// Verificar qué ruta tiene el documento en la BD
$con = sqlsrv_connect('db.injuve.gob.mx', array(
    'UID' => 'SIGO_USR', 
    'PWD' => 'B0lsa de@poyos_2025', 
    'Database' => 'SIGO_BD', 
    'Encrypt' => false, 
    'TrustServerCertificate' => true
));

if ($con === false) { 
    die('Connection failed: ' . print_r(sqlsrv_errors(), true)); 
}

$query = "SELECT id_doc, ruta_archivo FROM Documentos_Expediente WHERE ruta_archivo LIKE '%1rKeeN6Iw3j%'";
$stmt = sqlsrv_query($con, $query);

if ($stmt === false) { 
    die('Query failed: ' . print_r(sqlsrv_errors(), true)); 
}

echo "Documento con ese hash:\n";
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "ID: {$row['id_doc']} | Ruta en BD: " . ($row['ruta_archivo'] ?? 'NULL') . "\n";
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($con);
