<?php
try {
    $pdo = new PDO('sqlsrv:Server=JDEV\PARTIDA;Database=BD_SIGO', '', '');
    
    // Columns de presupuesto_apoyos
    echo "==== COLUMNAS presupuesto_apoyos ====\n";
    $stmt = $pdo->query("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='presupuesto_apoyos' ORDER BY ORDINAL_POSITION");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "- " . $row['COLUMN_NAME'] . "\n";
    }
    
    echo "\n==== PRESUPUESTO FOLIO 1008 ====\n";
    $stmt = $pdo->query("SELECT TOP 10 * FROM presupuesto_apoyos WHERE folio = '1008'");
    if($stmt->rowCount() === 0) {
        echo "❌ No hay presupuesto para folio 1008\n";
    }
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        foreach($row as $col => $val) {
            echo "  $col: $val\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
