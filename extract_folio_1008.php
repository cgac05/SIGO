<?php
try {
    $pdo = new PDO('sqlsrv:Server=JDEV\PARTIDA;Database=BD_SIGO', '', '');
    
    // 1. Solicitud con filtro simple
    echo "==== SOLICITUD FOLIO 1008 ====\n";
    $stmt = $pdo->query("SELECT TOP 1 * FROM Solicitudes WHERE folio = 1008");
    $solicitud = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($solicitud) {
        foreach($solicitud as $col => $val) {
            echo "$col: $val\n";
        }
    } else {
        echo "❌ No existe folio 1008\n";
    }
    
    echo "\n==== DOCUMENTOS FOLIO 1008 ====\n";
    $stmt = $pdo->query("SELECT TOP 10 * FROM Documentos_Expediente WHERE fk_folio = 1008");
    $count = 0;
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "Documento $count:\n";
        foreach($row as $col => $val) {
            echo "  $col: $val\n";
        }
        echo "\n";
    }
    
    echo "\n==== PRESUPUESTO FOLIO 1008 ====\n";
    $stmt = $pdo->query("SELECT TOP 10 * FROM presupuesto_apoyos WHERE fk_folio = 1008");
    $count = 0;
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "Presupuesto $count:\n";
        foreach($row as $col => $val) {
            echo "  $col: $val\n";
        }
        echo "\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
