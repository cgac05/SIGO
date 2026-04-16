<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 ESTADO COMPLETO DE TODOS LOS FOLIOS\n";
    echo "=====================================\n\n";
    
    // Obtener TODOS los folios con sus documentos
    $stmt = $pdo->query("
        SELECT 
            s.folio,
            s.fk_id_estado,
            s.cuv,
            COUNT(d.id_doc) as total_docs,
            SUM(CASE WHEN d.estado_validacion = 'Correcto' THEN 1 ELSE 0 END) as docs_correctos,
            SUM(CASE WHEN d.admin_status = 'aceptado' THEN 1 ELSE 0 END) as docs_aprobados
        FROM Solicitudes s
        LEFT JOIN Documentos_Expediente d ON s.folio = d.fk_folio
        GROUP BY s.folio, s.fk_id_estado, s.cuv
        ORDER BY s.folio DESC
    ");
    
    echo "FOLIO | ESTADO | CUV | DOCS | CORRECTOS | APROBADOS | ESTADO ESPERADO\n";
    echo "----------|--------|---------|------|----------|-----------|------------------\n";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $folio = $row['folio'];
        $estado = $row['fk_id_estado'] ?: 'NULL';
        $cuv = $row['cuv'] ?: 'NULL';
        $docs = $row['total_docs'] ?: 0;
        $correctos = $row['docs_correctos'] ?: 0;
        $aprobados = $row['docs_aprobados'] ?: 0;
        
        // Determinar si está listo para firma
        $listaParaFirma = ($correctos > 0 && $cuv == 'NULL' && $estado == '10') ? '✓ LISTO' : '❌ PENDIENTE';
        if($cuv != 'NULL') $listaParaFirma = '✓ FIRMADO';
        
        echo "$folio | $estado | $cuv | $docs | $correctos | $aprobados | $listaParaFirma\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
