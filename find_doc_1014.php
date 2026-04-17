<?php
$con = sqlsrv_connect('db.injuve.gob.mx', array(
    'UID' => 'SIGO_USR', 
    'PWD' => 'B0lsa de@poyos_2025', 
    'Database' => 'SIGO_BD', 
    'Encrypt' => false, 
    'TrustServerCertificate' => true
));

if ($con === false) { die('Connection failed'); }

// Buscar documento de la solicitud 1014
$query = "SELECT TOP 20 id_doc, fk_folio, ruta_archivo, origen_archivo, google_file_id, google_file_name FROM Documentos_Expediente WHERE fk_folio = 1014 ORDER BY id_doc DESC";
$stmt = sqlsrv_query($con, $query);

echo "=== Documentos de folio 1014 ===\n";
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    echo "ID: {$row['id_doc']} | Ruta: {$row['ruta_archivo']} | Origen: " . ($row['origen_archivo'] ?? 'NULL') . " | GDrive ID: " . ($row['google_file_id'] ?? 'NULL') . "\n";
    if (strpos($row['ruta_archivo'], '1rKeeN6Iw3j') !== false) {
        echo "  ✓ ENCONTRADO - Este es el que buscas!\n";
    }
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($con);
