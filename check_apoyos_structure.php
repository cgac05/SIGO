<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 ESTRUCTURA DE TABLA APOYOS\n";
    echo "═════════════════════════════\n\n";
    
    $stmt = $pdo->query("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'Apoyos'
        ORDER BY ORDINAL_POSITION
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo "• {$col['COLUMN_NAME']}\n";
        echo "  Tipo: {$col['DATA_TYPE']}\n";
        if($col['CHARACTER_MAXIMUM_LENGTH'] && $col['CHARACTER_MAXIMUM_LENGTH'] > 0) {
            echo "  Max: {$col['CHARACTER_MAXIMUM_LENGTH']}\n";
        }
        echo "\n";
    }
    
    echo "\n\n📋 VALORES EN TABLA APOYOS\n";
    echo "═════════════════════════════\n";
    
    $stmt = $pdo->query("
        SELECT TOP 5
            id_apoyo,
            nombre_apoyo,
            monto_maximo,
            cantidad_max_beneficiarios
        FROM Apoyos
    ");
    
    $apoyos = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($apoyos as $apoyo) {
        echo "\n▶ {$apoyo['nombre_apoyo']}\n";
        echo "  ID: {$apoyo['id_apoyo']}\n";
        echo "  Monto máximo: {$apoyo['monto_maximo']}\n";
        echo "  Cantidad max beneficiarios: {$apoyo['cantidad_max_beneficiarios']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
