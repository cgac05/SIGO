<?php
$pdo = new PDO('sqlsrv:Server=localhost;Database=BD_SIGO', 'SigoWebAppUser', 'UsuarioSigo159');

echo "=== Documentos_Expediente Columns ===\n";
$query = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Documentos_Expediente' ORDER BY ORDINAL_POSITION";
$result = $pdo->query($query);
while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
    $len = $row['CHARACTER_MAXIMUM_LENGTH'] ? ' (' . $row['CHARACTER_MAXIMUM_LENGTH'] . ')' : '';
    echo $row['COLUMN_NAME'] . ' => ' . $row['DATA_TYPE'] . $len . "\n";
}

echo "\n=== cadena_digital_documentos Columns ===\n";
$query2 = "SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'cadena_digital_documentos' ORDER BY ORDINAL_POSITION";
$result2 = $pdo->query($query2);
while ($row = $result2->fetch(PDO::FETCH_ASSOC)) {
    $len = $row['CHARACTER_MAXIMUM_LENGTH'] ? ' (' . $row['CHARACTER_MAXIMUM_LENGTH'] . ')' : '';
    echo $row['COLUMN_NAME'] . ' => ' . $row['DATA_TYPE'] . $len . "\n";
}
?>
