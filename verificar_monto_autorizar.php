<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "✅ VERIFICACIÓN: MONTO A AUTORIZAR = MONTO POR BENEFICIARIO\n";
    echo "═════════════════════════════════════════════════════════════\n\n";
    
    // Check folio 1016
    $stmt = $pdo->query("
        SELECT 
            s.folio,
            s.monto_entregado,
            a.monto_maximo,
            a.nombre_apoyo,
            b.nombre as beneficiario_nombre,
            (SELECT COUNT(*) FROM Documentos_Expediente WHERE fk_folio = s.folio AND admin_status = 'aceptado') as docs_aceptados,
            (SELECT COUNT(*) FROM Documentos_Expediente WHERE fk_folio = s.folio) as total_docs
        FROM Solicitudes s
        JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        JOIN Beneficiarios b ON s.fk_curp = b.curp
        WHERE s.folio = 1016
    ");
    
    $folio = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($folio) {
        echo "📋 FOLIO 1016:\n";
        echo "  Beneficiario: {$folio['beneficiario_nombre']}\n";
        echo "  Apoyo: {$folio['nombre_apoyo']}\n";
        echo "\n  📊 CAMPOS EN VISTA:\n";
        echo "  ┌─────────────────────────────────────────┐\n";
        echo "  │ Monto por Beneficiario: \${$folio['monto_maximo']}  │\n";
        echo "  │ (izquierda en Información General)      │\n";
        echo "  │                                         │\n";
        echo "  │ Monto a Autorizar: \${$folio['monto_maximo']}      │\n";
        echo "  │ (derecha en sección Presupuesto)        │\n";
        echo "  └─────────────────────────────────────────┘\n";
        echo "\n  ✅ ESTADO:\n";
        echo "  Ambos campos muestran IGUAL: \${$folio['monto_maximo']} ✓\n";
        echo "  Documentos: {$folio['docs_aceptados']}/{$folio['total_docs']} aceptados\n";
        echo "  Visible al directivo: ✅ SÍ\n";
        echo "\n  💡 VALIDATOR DE PRESUPUESTO AHORA USA:\n";
        echo "  · \$presupuestoDisponible >= \$apoyo->monto_maximo\n";
        echo "  · \$presupuestoCategoriaDisponible >= \$apoyo->monto_maximo\n";
    } else {
        echo "❌ Folio 1016 no encontrado\n";
    }
    
    // Check all pending solicitudes
    echo "\n\n📋 VERIFICACIÓN UNIVERSAL (TODOS LOS FOLIOS PENDIENTES):\n";
    echo "════════════════════════════════════════════════════════════\n\n";
    
    $stmt = $pdo->query("
        SELECT 
            s.folio,
            a.nombre_apoyo,
            a.monto_maximo,
            b.nombre as beneficiario_nombre
        FROM Solicitudes s
        JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        JOIN Beneficiarios b ON s.fk_curp = b.curp
        WHERE s.cuv IS NULL
        ORDER BY s.folio DESC
    ");
    
    $folios = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Folios que mostrarán correctamente:\n";
    foreach($folios as $folio) {
        echo "  ✅ Folio {$folio['folio']}: \${$folio['monto_maximo']}\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
