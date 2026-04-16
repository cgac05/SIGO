<?php
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

$query = "SELECT id_doc, fk_folio, ruta_archivo, origen_archivo, google_file_id FROM Documentos_Expediente WHERE fk_folio = 1013 ORDER BY id_doc DESC";
$stmt = sqlsrv_query($con, $query);

if ($stmt === false) { 
    die('Query failed: ' . print_r(sqlsrv_errors(), true)); 
}

echo "=== DOCUMENTOS DEL FOLIO 1013 ===\n";
echo str_repeat("=", 100) . "\n\n";

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $id = $row['id_doc'];
    $ruta = $row['ruta_archivo'];
    $origen = $row['origen_archivo'] ?? 'NULL';
    $gdrive = $row['google_file_id'] ?? 'NULL';
    
    echo "ID: $id\n";
    echo "Folio: {$row['fk_folio']}\n";
    echo "Ruta en BD: $ruta\n";
    echo "Origen: $origen\n";
    echo "Google ID: $gdrive\n";
    
    // Verificar si el archivo existe
    $posibles = [
        storage_path('app/public/' . $ruta),
        public_path('storage/' . $ruta),
    ];
    
    echo "Verificaciones:\n";
    foreach ($posibles as $path) {
        $existe = file_exists($path) ? '✓ EXISTE' : '✗ NO EXISTE';
        echo "  $existe: $path\n";
    }
    
    if (strpos($ruta, 'google_drive') !== false || $gdrive !== 'NULL') {
        echo "  🔗 Es documento de Google Drive\n";
    }
    
    echo "\n";
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($con);
