<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔧 NORMALIZANDO ESTADOS DE DOCUMENTOS\n";
    echo "=====================================\n\n";
    
    // Cambiar 'APROBADO' a 'aceptado'
    $stmt = $pdo->prepare("
        UPDATE Documentos_Expediente
        SET admin_status = 'aceptado'
        WHERE admin_status = 'APROBADO'
    ");
    $stmt->execute();
    $count1 = $stmt->rowCount();
    echo "✓ Cambios: estado 'APROBADO' → 'aceptado': $count1\n";
    
    // Cambiar 'pendiente' a 'Correcto' si estado_validacion = 'Correcto'
    $stmt = $pdo->prepare("
        UPDATE Documentos_Expediente
        SET admin_status = 'aceptado'
        WHERE estado_validacion = 'Correcto' 
        AND (admin_status IS NULL OR admin_status = 'pendiente')
    ");
    $stmt->execute();
    $count2 = $stmt->rowCount();
    echo "✓ Cambios: documentos con estado_validacion='Correcto' → admin_status='aceptado': $count2\n";
    
    echo "\n✅ NORMALIZACIÓN COMPLETADA\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
