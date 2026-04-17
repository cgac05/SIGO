<?php
try {
    $pdo = new PDO('sqlsrv:Server=JDEV\PARTIDA;Database=BD_SIGO', '', '');
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Documentos_Expediente' ORDER BY ORDINAL_POSITION");
    
    echo "Columnas de Documentos_Expediente:\n";
    echo "==================================\n";
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['COLUMN_NAME'] . "\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
