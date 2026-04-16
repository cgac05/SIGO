<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 VERIFICACIÓN DE CAMBIOS APLICADOS\n";
    echo "====================================\n\n";
    
    // 1. Verificar rutas de documentos
    echo "1️⃣  RUTAS DE DOCUMENTOS:\n";
    $stmt = $pdo->query("SELECT TOP 5 id_doc, fk_folio, ruta_archivo FROM Documentos_Expediente ORDER BY id_doc DESC");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Doc {$row['id_doc']} (Folio {$row['fk_folio']}): {$row['ruta_archivo']}\n";
    }
    
    // 2. Verificar si hay rutas con "storage/"
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM Documentos_Expediente WHERE ruta_archivo LIKE 'storage/%'");
    $conStorage = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "\n   Rutas con 'storage/': $conStorage (" . ($conStorage === 0 ? "✓ OK" : "❌ PROBLEMA") . ")\n\n";
    
    // 3. Verificar estados
    echo "2️⃣  ESTADOS DE DOCUMENTOS:\n";
    $stmt = $pdo->query("SELECT estado_validacion, COUNT(*) as cnt FROM Documentos_Expediente GROUP BY estado_validacion");
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   {$row['estado_validacion']}: {$row['cnt']}\n";
    }
    echo "\n";
    
    // 4. Verificar folios listos para firma
    echo "3️⃣  SOLICITUDES LISTAS PARA FIRMA (Estado 10 + Sin CUV):\n";
    $stmt = $pdo->query("SELECT folio, fk_id_estado, cuv FROM Solicitudes WHERE cuv IS NULL AND fk_id_estado = 10 LIMIT 5");
    $count = 0;
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "   Folio {$row['folio']} - Estado: {$row['fk_id_estado']} - CUV: " . ($row['cuv'] ?: "NULL") . "\n";
    }
    echo "   Total: $count\n\n";
    
    // 5. Folios que NO cumplen condiciones
    echo "4️⃣  SOLICITUDES QUE AÚN NO CUMPLEN REQUISITOS:\n";
    $stmt = $pdo->query("SELECT folio, fk_id_estado, cuv, (SELECT COUNT(*) FROM Documentos_Expediente WHERE fk_folio = Solicitudes.folio AND estado_validacion = 'Correcto') as docs_correctos FROM Solicitudes WHERE (cuv IS NULL AND fk_id_estado != 10) OR (fk_id_estado IS NULL)");
    $count = 0;
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $count++;
        echo "   Folio {$row['folio']} - Estado: {$row['fk_id_estado']} - Docs OK: {$row['docs_correctos']}\n";
    }
    if($count === 0) echo "   ✓ Todas las solicitudes están correctas\n";
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
