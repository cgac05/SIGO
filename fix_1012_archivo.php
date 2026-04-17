<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔧 Corrigiendo archivo del folio 1012...\n\n";
    
    // Usar un archivo que SÍ existe
    $archivoExistente = 'storage/solicitudes/1rKeeN6Iw3jSO59grKpYY8h8vIiMZYKbiFOOn9hg.pdf';
    
    $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET ruta_archivo = ? WHERE fk_folio = 1012");
    $stmt->execute([$archivoExistente]);
    
    echo "✓ Archivo actualizado a: $archivoExistente\n";
    
    // Verificar
    $stmt = $pdo->query("SELECT ruta_archivo FROM Documentos_Expediente WHERE fk_folio = 1012");
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "✓ Verificación: " . $row['ruta_archivo'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
