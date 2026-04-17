<?php
// Direct SQL Server verification
$servername = "sigo.database.windows.net";
$uid = "sigouser@sigo";
$pwd = "SigoCloud2024!";
$database = "SIGO_BD";

$conn = odbc_connect("Driver={ODBC Driver 17 for SQL Server};Server=$servername;Database=$database;Uid=$uid;Pwd=$pwd;", "", "");

if (!$conn) {
    die("Connection failed: " . odbc_error() . "\n");
}

echo "=== Movimientos Presupuestarios Columns ===\n";
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'movimientos_presupuestarios' ORDER BY ORDINAL_POSITION";
$result = odbc_exec($conn, $query);
if ($result) {
    while ($row = odbc_fetch_array($result)) {
        echo "- " . $row['COLUMN_NAME'] . "\n";
    }
}

echo "\n=== Presupuesto Apoyos Columns ===\n";
$query = "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'presupuesto_apoyos' ORDER BY ORDINAL_POSITION";
$result = odbc_exec($conn, $query);
if ($result) {
    while ($row = odbc_fetch_array($result)) {
        echo "- " . $row['COLUMN_NAME'] . "\n";
    }
}

echo "\n=== Checking for problematic columns ===\n";
$problematicCols = [
    'movimientos_presupuestarios' => ['id_presupuesto_apoyo', 'fecha_movimiento', 'notas'],
    'presupuesto_apoyos' => ['id_presupuesto_apoyo', 'costo_estimado', 'fecha_reserva']
];

foreach ($problematicCols as $table => $cols) {
    echo "\nIn table '$table':\n";
    foreach ($cols as $col) {
        $query = "SELECT COUNT(*) as cnt FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = '$table' AND COLUMN_NAME = '$col'";
        $result = odbc_exec($conn, $query);
        if ($result && $row = odbc_fetch_array($result)) {
            echo ($row['cnt'] > 0 ? "✗ FOUND" : "✓ NOT FOUND") . ": $col\n";
        }
    }
}

odbc_close($conn);
?>
