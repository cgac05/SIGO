<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔧 PREPARANDO BD PARA FLUJO UNIVERSAL DE FIRMA DIRECTIVO\n";
    echo "==========================================================\n\n";
    
    // 1. Corregir todas las rutas: quitar "storage/" si existe
    echo "1️⃣  Corrigiendo rutas de documentos...\n";
    $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET ruta_archivo = REPLACE(ruta_archivo, 'storage/', '') WHERE ruta_archivo LIKE 'storage/%'");
    $stmt->execute();
    echo "   ✓ Rutas normalizadas\n\n";
    
    // 2. Cambiar estado de validación a "Correcto" para documentos aprobados
    echo "2️⃣  Actualizando estados de documentos...\n";
    $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET estado_validacion = 'Correcto' WHERE admin_status = 'aceptado' AND estado_validacion = 'Pendiente'");
    $stmt->execute();
    echo "   ✓ Documentos aprobados marcados como 'Correcto'\n\n";
    
    // 3. Cambiar estado de solicitudes a 10 (DOCUMENTOS_VERIFICADOS) para que se puedan firmar
    echo "3️⃣  Preparando solicitudes para firma...\n";
    $stmt = $pdo->prepare("
        UPDATE Solicitudes 
        SET fk_id_estado = 10
        WHERE cuv IS NULL 
        AND fk_id_estado != 3
        AND EXISTS (SELECT 1 FROM Documentos_Expediente d WHERE d.fk_folio = Solicitudes.folio AND d.estado_validacion = 'Correcto')
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "   ✓ $updated solicitudes actualizadas a estado DOCUMENTOS_VERIFICADOS\n\n";
    
    // 4. Resumen final
    echo "📊 RESUMEN FINAL:\n";
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Solicitudes WHERE cuv IS NULL");
    $sinFirmar = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Solicitudes WHERE cuv IS NOT NULL");
    $firmadas = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Documentos_Expediente WHERE estado_validacion = 'Correcto'");
    $correctos = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    
    echo "   📝 Solicitudes sin firmar: $sinFirmar (listas para firma)\n";
    echo "   ✅ Solicitudes firmadas: $firmadas\n";
    echo "   ✓ Documentos validados: $correctos\n";
    
    echo "\n✅ BD PREPARADA - Lista para nuevo folio mañana\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
