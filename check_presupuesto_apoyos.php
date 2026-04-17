<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 ESTRUCTURA DE presupuesto_apoyos\n";
    echo "═════════════════════════════════════\n\n";
    
    $stmt = $pdo->query("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'presupuesto_apoyos'
        ORDER BY ORDINAL_POSITION
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo "• {$col['COLUMN_NAME']}\n";
        echo "  Tipo: {$col['DATA_TYPE']}\n";
        if($col['CHARACTER_MAXIMUM_LENGTH'] && $col['CHARACTER_MAXIMUM_LENGTH'] > 0) {
            echo "  Max: {$col['CHARACTER_MAXIMUM_LENGTH']}\n";
        }
        echo "  Nullable: {$col['IS_NULLABLE']}\n\n";
    }
    
    echo "\n📊 PRESUPUESTO PARA FOLIO 1016\n";
    echo "═════════════════════════════════\n\n";
    
    $stmt = $pdo->query("
        SELECT 
            pa.*,
            a.nombre_apoyo,
            a.monto_maximo
        FROM presupuesto_apoyos pa
        JOIN Apoyos a ON pa.fk_id_apoyo = a.id_apoyo
        WHERE pa.fk_folio = 1016
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($rows)) {
        echo "❌ No hay presupuesto registrado para folio 1016\n";
    } else {
        foreach($rows as $row) {
            echo "\n▶ ID: {$row['id_presupuesto_apoyo']}\n";
            echo "  Apoyo: {$row['nombre_apoyo']}\n";
            echo "  Monto máximo: \${$row['monto_maximo']}\n";
            foreach($row as $key => $val) {
                if(stripos($key, 'monto') !== false || stripos($key, 'cantidad') !== false || stripos($key, 'presupuesto') !== false) {
                    echo "  $key: $val\n";
                }
            }
        }
    }
    
    echo "\n\n📋 COMPARAR CON FOLIO 1015 (QUE FUNCIONA)\n";
    echo "═════════════════════════════════════════\n\n";
    
    $stmt = $pdo->query("
        SELECT TOP 1
            s.folio,
            s.monto_entregado,
            a.nombre_apoyo,
            a.monto_maximo
        FROM Solicitudes s
        JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        WHERE s.folio = 1015
    ");
    
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);
    if($sol) {
        echo "Folio 1015:\n";
        echo "  Apoyo: {$sol['nombre_apoyo']}\n";
        echo "  Monto máximo: \${$sol['monto_maximo']}\n";
        echo "  Monto entregado: \${$sol['monto_entregado']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
