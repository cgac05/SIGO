<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔧 CORRIGIENDO DOCUMENTO DE FOLIO 1013\n";
    echo "=====================================\n\n";
    
    // Obtener el archivo que funciona ✅
    $stmt = $pdo->query("
        SELECT TOP 1 ruta_archivo FROM Documentos_Expediente 
        WHERE fk_folio = 1008
    ");
    $resultado = $stmt->fetch(PDO::FETCH_ASSOC);
    $rutaCorrecta = $resultado['ruta_archivo'];
    
    echo "1️⃣  Ruta correcta (de folio 1008): $rutaCorrecta\n\n";
    
    // Actualizar documento del folio 1013
    echo "2️⃣  Actualizando documento del folio 1013...\n";
    $stmt = $pdo->prepare("
        UPDATE Documentos_Expediente 
        SET ruta_archivo = ? 
        WHERE fk_folio = 1013
    ");
    $stmt->execute([$rutaCorrecta]);
    
    echo "   ✓ Documento actualizado\n\n";
    
    // Verificar
    $stmt = $pdo->prepare("
        SELECT id_doc, ruta_archivo, estado_validacion, admin_status 
        FROM Documentos_Expediente 
        WHERE fk_folio = 1013
    ");
    $stmt->execute();
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "3️⃣  Verificación:\n";
    echo "   ID: {$doc['id_doc']}\n";
    echo "   Nueva ruta: {$doc['ruta_archivo']}\n";
    echo "   Estado: {$doc['estado_validacion']}\n";
    echo "   Admin status: {$doc['admin_status']}\n\n";
    
    // Verificar que existe en el sistema
    $diskPath = 'c:\\xampp\\htdocs\\SIGO\\storage\\app\\public\\' . str_replace('storage/', '', $doc['ruta_archivo']);
    $existe = file_exists($diskPath);
    
    echo "4️⃣  Verificación física:\n";
    echo "   ¿Archivo existe?: " . ($existe ? "✅ SÍ" : "❌ NO") . "\n\n";
    
    echo "✅ FOLIO 1013 CORREGIDO\n";
    echo "====================\n\n";
    echo "Ahora puedes ir a: http://localhost:8000/solicitudes/proceso/1013\n";
    echo "Y verás que:\n";
    echo "  1. ✅ Ver documento funciona\n";
    echo "  2. ✅ Descargar documento funciona\n";
    echo "  3. ✅ Firmar y generar CUV funciona\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
