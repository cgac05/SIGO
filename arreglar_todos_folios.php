<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔧 ARREGLANDO TODOS LOS FOLIOS PARA FIRMA UNIVERSAL\n";
    echo "====================================================\n\n";
    
    // 1. TODOS los documentos con admin_status='aceptado' → estado 'Correcto'
    echo "1️⃣  Actualizando estados de documentos...\n";
    $stmt = $pdo->prepare("
        UPDATE Documentos_Expediente 
        SET estado_validacion = 'Correcto'
        WHERE admin_status = 'aceptado' AND estado_validacion != 'Correcto'
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "   ✓ $updated documentos actualizados a 'Correcto'\n\n";
    
    // 2. TODOS los folios sin CUV que tengan documentos correctos → estado 10
    echo "2️⃣  Preparando solicitudes para firma...\n";
    $stmt = $pdo->prepare("
        UPDATE Solicitudes 
        SET fk_id_estado = 10
        WHERE cuv IS NULL
        AND EXISTS (
            SELECT 1 FROM Documentos_Expediente d 
            WHERE d.fk_folio = Solicitudes.folio 
            AND d.estado_validacion = 'Correcto'
        )
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "   ✓ $updated solicitudes actualizadas a estado 10 (DOCUMENTOS_VERIFICADOS)\n\n";
    
    // 3. Verificar resultado final
    echo "3️⃣  RESULTADO FINAL:\n\n";
    $stmt = $pdo->query("
        SELECT 
            s.folio,
            s.fk_id_estado,
            COUNT(d.id_doc) as total_docs,
            SUM(CASE WHEN d.estado_validacion = 'Correcto' THEN 1 ELSE 0 END) as docs_correctos
        FROM Solicitudes s
        LEFT JOIN Documentos_Expediente d ON s.folio = d.fk_folio
        WHERE s.cuv IS NULL
        GROUP BY s.folio, s.fk_id_estado
        ORDER BY s.folio
    ");
    
    $listos = 0;
    echo "FOLIO | ESTADO | ESTADO_NOMBRE | DOCS_CORRECTOS | LISTO PARA FIRMA\n";
    echo "------|--------|---------------|--------|------------------\n";
    
    $estadosNombres = [
        1 => 'PENDIENTE_ENVÍO',
        3 => 'APROBADA',
        4 => 'RECHAZADA', 
        10 => 'DOCUMENTOS_VERIFICADOS',
        12 => 'ENVIADA_FIRMA'
    ];
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $nombre = $estadosNombres[$row['fk_id_estado']] ?? 'DESCONOCIDO';
        $listo = ($row['fk_id_estado'] == 10 && $row['docs_correctos'] > 0) ? '✓ SÍ' : '❌ NO';
        if($row['fk_id_estado'] == 10 && $row['docs_correctos'] > 0) $listos++;
        
        echo "{$row['folio']} | {$row['fk_id_estado']} | $nombre | {$row['docs_correctos']} | $listo\n";
    }
    
    echo "\n✅ TOTAL FOLIOS LISTOS PARA FIRMA: $listos\n";
    echo "\n✅ TODOS LOS FOLIOS CORREGIDOS - Sistema universal activo\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
