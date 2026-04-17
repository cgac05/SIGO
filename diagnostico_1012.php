<?php
try {
    // Conectar a Azure
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "✓ Conectado a Azure\n\n";
    echo "==== DIAGNÓSTICO FOLIO 1012 ====\n\n";
    
    // 1. Solicitud
    echo "1. SOLICITUD:\n";
    $stmt = $pdo->query("SELECT folio, fk_id_estado, cuv, presupuesto_confirmado FROM Solicitudes WHERE folio = 1012");
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Folio: " . $row['folio'] . "\n";
        echo "   Estado: " . $row['fk_id_estado'] . "\n";
        echo "   CUV: " . ($row['cuv'] ?: "NULL") . "\n";
        echo "   Presupuesto confirmado: " . $row['presupuesto_confirmado'] . "\n";
    } else {
        echo "   ❌ NO EXISTE\n";
    }
    
    // 2. Documentos
    echo "\n2. DOCUMENTOS:\n";
    $stmt = $pdo->query("SELECT id_doc, ruta_archivo, estado_validacion, admin_status FROM Documentos_Expediente WHERE fk_folio = 1012");
    $docCount = 0;
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $docCount++;
        echo "   Doc $docCount:\n";
        echo "     - Ruta: " . $row['ruta_archivo'] . "\n";
        echo "     - Estado validación: " . $row['estado_validacion'] . "\n";
        echo "     - Admin status: " . $row['admin_status'] . "\n";
    }
    
    if($docCount === 0) {
        echo "   ❌ SIN DOCUMENTOS\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
