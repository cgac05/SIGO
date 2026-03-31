<?php
// Ver qué valores válidos tiene tipo_apoyo

$conn = sqlsrv_connect('localhost', [
    'Database' => 'BD_SIGO',
    'UID' => 'sa',
    'PWD' => '1234'
]);

// Ver los tipos de apoyo utilizados actualmente
$query = "SELECT DISTINCT tipo_apoyo FROM Apoyos WHERE tipo_apoyo IS NOT NULL";
$result = sqlsrv_query($conn, $query);

echo "=== TIPOS DE APOYO EN BD ===\n\n";
while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    echo "- " . ($row['tipo_apoyo'] ?? 'NULL') . "\n";
}

sqlsrv_close($conn);
?>
