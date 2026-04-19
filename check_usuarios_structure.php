<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 ESTRUCTURA DE TABLA USUARIOS\n";
    echo "===============================\n\n";
    
    $stmt = $pdo->query("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH, IS_NULLABLE
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'Usuarios'
        ORDER BY ORDINAL_POSITION
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach($columns as $col) {
        echo "• {$col['COLUMN_NAME']}\n";
        echo "  Tipo: {$col['DATA_TYPE']}\n";
        echo "  Max Length: " . ($col['CHARACTER_MAXIMUM_LENGTH'] ?? 'N/A') . "\n";
        echo "  Nullable: " . ($col['IS_NULLABLE'] == 'YES' ? 'Sí' : 'No') . "\n\n";
    }
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
