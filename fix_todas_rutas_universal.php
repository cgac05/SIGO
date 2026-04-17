<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔧 CORRECCIÓN UNIVERSAL DE RUTAS DE DOCUMENTOS\n";
    echo "==============================================\n\n";
    
    // 1. Contar documentos problemáticos
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM Documentos_Expediente WHERE ruta_archivo LIKE 'storage/%'");
    $problematic = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "📊 Documentos con rutas incorrectas (storage/): $problematic\n\n";
    
    // 2. Corregir todas las rutas que empiezan con "storage/"
    $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET ruta_archivo = REPLACE(ruta_archivo, 'storage/', '') WHERE ruta_archivo LIKE 'storage/%'");
    $stmt->execute();
    echo "✓ Rutas corregidas\n\n";
    
    // 3. Corregir estado de validación: cambiar "Pendiente" a "Correcto"
    $stmt = $pdo->query("SELECT COUNT(*) as cnt FROM Documentos_Expediente WHERE estado_validacion = 'Pendiente'");
    $pendientes = $stmt->fetch(PDO::FETCH_ASSOC)['cnt'];
    echo "📊 Documentos en estado 'Pendiente': $pendientes\n";
    
    if($pendientes > 0) {
        $stmt = $pdo->prepare("UPDATE Documentos_Expediente SET estado_validacion = 'Correcto' WHERE estado_validacion = 'Pendiente' AND admin_status = 'aceptado'");
        $stmt->execute();
        echo "✓ Estados actualizados a 'Correcto'\n\n";
    }
    
    // 4. Ver estadísticas finales
    $stmt = $pdo->query("SELECT COUNT(*) as total, 
                                SUM(CASE WHEN estado_validacion = 'Correcto' THEN 1 ELSE 0 END) as correctos,
                                SUM(CASE WHEN estado_validacion = 'Pendiente' THEN 1 ELSE 0 END) as pendientes
                        FROM Documentos_Expediente");
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "📈 ESTADÍSTICAS FINALES:\n";
    echo "   Total documentos: " . $stats['total'] . "\n";
    echo "   Correctos: " . $stats['correctos'] . "\n";
    echo "   Pendientes: " . $stats['pendientes'] . "\n";
    
    echo "\n✅ CORRECCIÓN COMPLETADA - Todos los documentos ahora usarán rutas universales\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
