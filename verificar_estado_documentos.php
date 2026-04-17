<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "📋 ESTADO ACTUAL DE DOCUMENTOS POR SOLICITUD\n";
    echo "═════════════════════════════════════════════\n\n";
    
    // Obtener todas las solicitudes con sus documentos
    $stmt = $pdo->query("
        SELECT 
            s.folio,
            a.nombre_apoyo,
            s.fk_id_estado,
            s.cuv,
            COUNT(d.id_doc) as total_docs,
            SUM(CASE WHEN d.admin_status = 'aceptado' THEN 1 ELSE 0 END) as docs_aceptados,
            SUM(CASE WHEN d.admin_status = 'pendiente' THEN 1 ELSE 0 END) as docs_pendientes,
            SUM(CASE WHEN d.admin_status = 'rechazado' THEN 1 ELSE 0 END) as docs_rechazados
        FROM Solicitudes s
        LEFT JOIN Documentos_Expediente d ON s.folio = d.fk_folio
        LEFT JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
        GROUP BY s.folio, a.nombre_apoyo, s.fk_id_estado, s.cuv
        ORDER BY s.folio DESC
    ");
    
    $solicitudes = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if(empty($solicitudes)) {
        echo "  ❌ No hay solicitudes en el sistema\n";
        exit;
    }
    
    foreach($solicitudes as $sol) {
        $folio = $sol['folio'];
        $apoyo = $sol['nombre_apoyo'] ?? 'Sin apoyo';
        $total = $sol['total_docs'] ?? 0;
        $aceptados = $sol['docs_aceptados'] ?? 0;
        $pendientes = $sol['docs_pendientes'] ?? 0;
        $rechazados = $sol['docs_rechazados'] ?? 0;
        $cuv = $sol['cuv'] ?? 'No firmado';
        
        echo "▶ FOLIO: $folio\n";
        echo "  Apoyo: $apoyo\n";
        echo "  CUV: $cuv\n";
        echo "  Documentos:\n";
        echo "    • Total: $total\n";
        echo "    • Aceptados: $aceptados ✓\n";
        echo "    • Pendientes: $pendientes ⏳\n";
        echo "    • Rechazados: $rechazados ✗\n";
        
        // Determinar si directivo lo ve
        $puedeVerDirectivo = ($total > 0 && $aceptados > 0 && ($pendientes + $rechazados) == 0);
        
        if($puedeVerDirectivo) {
            echo "  🟢 DIRECTIVO VE: SÍ ✓\n";
        } else {
            $razon = [];
            if($total == 0) $razon[] = "sin documentos";
            if($aceptados == 0) $razon[] = "ninguno aceptado";
            if($pendientes > 0) $razon[] = "$pendientes pendiente(s)";
            if($rechazados > 0) $razon[] = "$rechazados rechazado(s)";
            echo "  🔴 DIRECTIVO VE: NO ✗\n";
            echo "    Razón: " . implode(", ", $razon) . "\n";
        }
        
        // Mostrar detalles de documentos
        $docStmt = $pdo->prepare("
            SELECT 
                id_doc,
                fk_id_tipo_doc,
                admin_status,
                estado_validacion,
                origen_archivo
            FROM Documentos_Expediente
            WHERE fk_folio = ?
            ORDER BY id_doc
        ");
        $docStmt->execute([$folio]);
        $docs = $docStmt->fetchAll(PDO::FETCH_ASSOC);
        
        if(!empty($docs)) {
            echo "  Detalle de documentos:\n";
            foreach($docs as $doc) {
                $status = $doc['admin_status'] ?? 'null';
                $icon = '';
                if($status == 'aceptado') $icon = '✓ ';
                elseif($status == 'pendiente') $icon = '⏳ ';
                elseif($status == 'rechazado') $icon = '✗ ';
                
                echo "    └─ Doc #{$doc['id_doc']}: admin_status=$status {$icon}\n";
            }
        }
        
        echo "\n";
    }
    
    echo "\n📊 RESUMEN\n";
    echo "──────────\n";
    $puedenVer = 0;
    $noPuedenVer = 0;
    
    foreach($solicitudes as $sol) {
        $total = $sol['total_docs'] ?? 0;
        $aceptados = $sol['docs_aceptados'] ?? 0;
        $pendientes = $sol['docs_pendientes'] ?? 0;
        $rechazados = $sol['docs_rechazados'] ?? 0;
        
        if($total > 0 && $aceptados > 0 && ($pendientes + $rechazados) == 0) {
            $puedenVer++;
        } else {
            $noPuedenVer++;
        }
    }
    
    echo "Solicitudes visibles para directivo: $puedenVer\n";
    echo "Solicitudes ocultas para directivo: $noPuedenVer\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
