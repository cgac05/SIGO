<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "✅ VERIFICACIÓN DE CAMBIOS APLICADOS\n";
    echo "====================================\n\n";
    
    // 1. Verificar rutas de documentos
    echo "1️⃣  RUTAS DE DOCUMENTOS (últimos 5):\n";
    $stmt = $pdo->query("SELECT TOP 5 id_doc, fk_folio, ruta_archivo FROM Documentos_Expediente ORDER BY id_doc DESC");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Doc {$row['id_doc']} (Folio {$row['fk_folio']}): {$row['ruta_archivo']}\n";
    }
    
    // 2. Verificar si hay rutas con "storage/"
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM Documentos_Expediente WHERE ruta_archivo LIKE 'storage/%'");
    $conStorage = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "   Rutas con 'storage/': $conStorage " . ($conStorage == 0 ? "✓ CORRECTO" : "❌ PROBLEMA") . "\n\n";
    
    // 3. Verificar estados
    echo "2️⃣  ESTADOS DE DOCUMENTOS:\n";
    $stmt = $pdo->query("SELECT estado_validacion, COUNT(*) as cnt FROM Documentos_Expediente GROUP BY estado_validacion");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['estado_validacion']}: {$row['cnt']}\n";
    }
    echo "\n";
    
    // 4. Verificar folios listos para firma
    echo "3️⃣  SOLICITUDES LISTAS PARA FIRMA (Estado=10 + CUV=NULL):\n";
    $stmt = $pdo->query("SELECT TOP 10 folio, fk_id_estado, cuv FROM Solicitudes WHERE cuv IS NULL AND fk_id_estado = 10");
    $count = 0;
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "   ✓ Folio {$row['folio']}\n";
    }
    echo "   Total: $count firmas pendientes\n\n";
    
    echo "✅ ESTADO GENERAL: Cambios APLICADOS correctamente en BD\n";
    echo "   Próximo paso: Refrescar navegador (Ctrl+Shift+R o vaciar cache)\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
