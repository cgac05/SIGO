<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "✅ VERIFICACIÓN DE MONTOS EN LISTADO DIRECTIVO\n";
    echo "═════════════════════════════════════════════════\n\n";
    
    // Simular la query que usa el controlador para el listado
    $stmt = $pdo->query("
        SELECT TOP 10
            s.folio,
            s.monto_entregado,
            a.nombre_apoyo,
            a.monto_maximo,
            b.nombre as beneficiario_nombre
        FROM Solicitudes s
        JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        JOIN Beneficiarios b ON s.fk_curp = b.curp
        WHERE s.cuv IS NULL
        ORDER BY s.folio DESC
    ");
    
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "📋 SOLICITUDES PENDIENTES DE FIRMA (Sin CUV):\n";
    echo "───────────────────────────────────────────────\n\n";
    
    if(empty($rows)) {
        echo "No hay solicitudes pendientes\n";
    } else {
        foreach($rows as $row) {
            echo "📌 FOLIO {$row['folio']}\n";
            echo "  Beneficiario: {$row['beneficiario_nombre']}\n";
            echo "  Apoyo: {$row['nombre_apoyo']}\n";
            echo "  ✅ MONTO QUE MOSTRARÁ: \${$row['monto_maximo']} (antes: \${$row['monto_entregado']})\n";
            echo "\n";
        }
    }
    
    // Verificar específicamente folio 1016
    echo "\n\n🔍 VERIFICACIÓN ESPECÍFICA - FOLIO 1016:\n";
    echo "════════════════════════════════════════\n\n";
    
    $stmt = $pdo->query("
        SELECT 
            s.folio,
            s.monto_entregado,
            a.nombre_apoyo,
            a.monto_maximo,
            b.nombre as beneficiario_nombre,
            (SELECT COUNT(*) FROM Documentos_Expediente WHERE fk_folio = s.folio) as total_docs,
            (SELECT COUNT(*) FROM Documentos_Expediente WHERE fk_folio = s.folio AND admin_status = 'aceptado') as docs_aceptados
        FROM Solicitudes s
        JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        JOIN Beneficiarios b ON s.fk_curp = b.curp
        WHERE s.folio = 1016
    ");
    
    $folio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($folio) {
        echo "✅ Folio existe\n";
        echo "  Beneficiario: {$folio['beneficiario_nombre']}\n";
        echo "  Apoyo: {$folio['nombre_apoyo']}\n";
        echo "\n  📊 MONTO POR BENEFICIARIO: \${$folio['monto_maximo']}\n";
        echo "  📄 Documentos: {$folio['docs_aceptados']}/{$folio['total_docs']} aceptados\n";
        echo "  Estado: " . ($folio['docs_aceptados'] == $folio['total_docs'] ? "✅ VISIBLE AL DIRECTIVO" : "❌ OCULTA AL DIRECTIVO") . "\n";
    } else {
        echo "❌ Folio 1016 no encontrado\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
