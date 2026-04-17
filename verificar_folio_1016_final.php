<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "✅ VERIFICACIÓN FINAL DE FOLIO 1016\n";
    echo "═════════════════════════════════════\n\n";
    
    // Get solicitud with apoyo details
    $stmt = $pdo->query("
        SELECT 
            s.folio,
            s.fk_id_apoyo,
            s.monto_entregado,
            s.presupuesto_confirmado,
            a.nombre_apoyo,
            a.monto_maximo
        FROM Solicitudes s
        JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        WHERE s.folio = 1016
    ");
    
    $sol = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($sol) {
        echo "📋 SOLICITUD 1016:\n";
        echo "  Apoyo: {$sol['nombre_apoyo']}\n";
        echo "  Monto Máximo (MONTO POR BENEFICIARIO): \${$sol['monto_maximo']}\n";
        echo "  Monto Entregado: \${$sol['monto_entregado']}\n";
        echo "  Presupuesto Confirmado: " . ($sol['presupuesto_confirmado'] ? '✅ SÍ' : '❌ NO') . "\n";
        
        // Check documents
        $stmt = $pdo->query("
            SELECT admin_status, COUNT(*) as cantidad
            FROM Documentos_Expediente
            WHERE fk_folio = 1016
            GROUP BY admin_status
        ");
        
        $docs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo "\n📄 DOCUMENTOS:\n";
        foreach($docs as $doc) {
            echo "  {$doc['admin_status']}: {$doc['cantidad']}\n";
        }
        
        // Check if directivo can see it
        $stmt = $pdo->query("
            SELECT COUNT(*) as expedientes_sin_aceptar
            FROM Documentos_Expediente
            WHERE fk_folio = 1016
            AND admin_status != 'aceptado'
            AND admin_status IS NOT NULL
        ");
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $puede_ver = $result['expedientes_sin_aceptar'] == 0;
        
        echo "\n🔍 VISIBILIDAD PARA DIRECTIVO:\n";
        echo "  ¿Todos los documentos aceptados?: " . ($puede_ver ? "✅ SÍ" : "❌ NO") . "\n";
        echo "  La solicitud " . ($puede_ver ? "✅ ES VISIBLE" : "❌ NO ES VISIBLE") . "\n";
        
    } else {
        echo "❌ Folio 1016 no encontrado\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
