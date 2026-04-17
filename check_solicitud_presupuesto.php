<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 BÚSQUEDA DE TABLA presupuesto_apoyos\n";
    echo "═════════════════════════════════════════\n\n";
    
    // Find if table exists
    $stmt = $pdo->query("
        SELECT TABLE_NAME
        FROM INFORMATION_SCHEMA.TABLES
        WHERE TABLE_NAME LIKE '%presupuesto%'
    ");
    
    $tables = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($tables)) {
        echo "❌ No se encontró tabla con 'presupuesto'\n\n";
    } else {
        foreach($tables as $table) {
            echo "✅ Encontrada: {$table['TABLE_NAME']}\n";
        }
    }
    
    echo "\n📋 BÚSQUEDA EN SOLICITUDES - RELACIONAR CON APOYOS\n";
    echo "═════════════════════════════════════════════════\n\n";
    
    // Check Solicitudes structure
    $stmt = $pdo->query("
        SELECT COLUMN_NAME, DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
        FROM INFORMATION_SCHEMA.COLUMNS
        WHERE TABLE_NAME = 'Solicitudes'
        ORDER BY ORDINAL_POSITION
    ");
    
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "Columnas en Solicitudes:\n";
    foreach($columns as $col) {
        echo "  • {$col['COLUMN_NAME']} ({$col['DATA_TYPE']})\n";
    }
    
    echo "\n📊 VERIFICAR SOLICITUD 1016\n";
    echo "═════════════════════════════\n\n";
    
    $stmt = $pdo->query("
        SELECT TOP 1
            folio,
            monto_entregado,
            fk_id_apoyo,
            cuv,
            presupuesto_confirmado,
            presupuesto_solicitado,
            presupuesto_aprobado
        FROM Solicitudes
        WHERE folio = 1016
    ");
    
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($sol) {
        echo "Folio 1016:\n";
        foreach($sol as $key => $val) {
            echo "  $key: $val\n";
        }
        
        // Get apoyo details
        echo "\n📦 APOYO ASOCIADO:\n";
        $stmt = $pdo->query("
            SELECT id_apoyo, nombre_apoyo, monto_maximo, cupo_limite
            FROM Apoyos
            WHERE id_apoyo = {$sol['fk_id_apoyo']}
        ");
        $apoyo = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "  Nombre: {$apoyo['nombre_apoyo']}\n";
        echo "  Monto máximo: \${$apoyo['monto_maximo']}\n";
        echo "  Cupo límite: {$apoyo['cupo_limite']}\n";
    } else {
        echo "No se encontró folio 1016\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
