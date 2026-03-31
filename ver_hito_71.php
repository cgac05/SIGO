<?php
// Verificar que el hito se creó correctamente

$conn = sqlsrv_connect('localhost', [
    'Database' => 'BD_SIGO',
    'UID' => 'sa',
    'PWD' => '1234'
]);

$query = "SELECT TOP 1 * FROM hitos_apoyo WHERE id_hito = 71 ORDER BY id_hito DESC";
$result = sqlsrv_query($conn, $query);

echo "=== DATOS DEL HITO 71 ===\n\n";
if ($result && $row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)) {
    foreach ($row as $key => $value) {
        $display = $value;
        if ($value instanceof DateTime) {
            $display = $value->format('Y-m-d H:i:s');
        }
        echo "$key: $display\n";
    }
} else {
    echo "No se encontró el hito\n";
}

sqlsrv_close($conn);
?>
