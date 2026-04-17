<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔧 Corrigiendo rutas de documentos...\n\n";
    
    // Arreglar rutas que empiezan con "storage/"
    $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET ruta_archivo = REPLACE(ruta_archivo, 'storage/', '') WHERE ruta_archivo LIKE 'storage/%'");
    $stmt->execute();
    
    echo "✓ Rutas corregidas\n";
    
    // Verificar
    $stmt = $pdo->query("SELECT id_doc, ruta_archivo FROM Documentos_Expediente WHERE fk_folio = 1012");
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "\n✓ Nueva ruta: " . $row['ruta_archivo'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
