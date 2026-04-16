<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "🔧 Corrigiendo folio 1012...\n\n";
    
    // 1. Corregir ruta del documento (agregar storage/)
    echo "1️⃣  Corrigiendo ruta del documento...\n";
    $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET ruta_archivo = CONCAT('storage/', ruta_archivo) WHERE fk_folio = 1012 AND ruta_archivo NOT LIKE 'storage/%'");
    $stmt->execute();
    echo "   ✓ Ruta actualizada\n";
    
    // 2. Cambiar estado validación a 'Correcto'
    echo "\n2️⃣  Marcando documento como CORRECTO...\n";
    $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET estado_validacion = 'Correcto' WHERE fk_folio = 1012");
    $stmt->execute();
    echo "   ✓ Documento validado\n";
    
    // 3. Cambiar estado de solicitud a 10 (DOCUMENTOS_VERIFICADOS)
    echo "\n3️⃣  Cambiando estado de solicitud a DOCUMENTOS_VERIFICADOS...\n";
    $stmt = $pdo->prepare("UPDATE Solicitudes SET fk_id_estado = 10 WHERE folio = 1012");
    $stmt->execute();
    echo "   ✓ Estado actualizado\n";
    
    // 4. Verificar
    echo "\n✅ VERIFICACIÓN FINAL:\n";
    $stmt = $pdo->query("SELECT folio, fk_id_estado FROM Solicitudes WHERE folio = 1012");
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Estado solicitud: " . $row['fk_id_estado'] . " (10 = DOCUMENTOS_VERIFICADOS)\n";
    }
    
    $stmt = $pdo->query("SELECT ruta_archivo, estado_validacion FROM Documentos_Expediente WHERE fk_folio = 1012");
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "   Ruta: " . $row['ruta_archivo'] . "\n";
        echo "   Estado: " . $row['estado_validacion'] . "\n";
    }
    
    echo "\n✅ Folio 1012 listo para firmar!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
