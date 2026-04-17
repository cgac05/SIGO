<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 Diagnóstico de rutas:\n\n";
    
    // Obtener ruta del folio 1012
    $stmt = $pdo->query("SELECT id_doc, ruta_archivo FROM Documentos_Expediente WHERE fk_folio = 1012");
    if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "BD Ruta actual: " . $row['ruta_archivo'] . "\n";
        
        // Verificar en storage
        $pathInStorage = $row['ruta_archivo'];
        
        // Si tiene "storage/" al inicio, quitarlo para Storage::disk
        $cleanPath = str_replace('storage/', '', $pathInStorage);
        
        echo "Path limpio: " . $cleanPath . "\n";
        echo "Path en storage: storage/app/public/" . $cleanPath . "\n";
        
        // Verificar si existe
        $dir = 'C:\\xampp\\htdocs\\SIGO\\storage\\app\\public\\' . $cleanPath;
        echo "Ruta física: " . $dir . "\n";
        
        if(file_exists($dir)) {
            echo "✓ Archivo EXISTE en storage\n";
        } else {
            echo "❌ Archivo NO EXISTE\n";
            echo "\n📁 Archivos en storage:\n";
            $files = glob('C:\\xampp\\htdocs\\SIGO\\storage\\app\\public\\solicitudes\\*.pdf');
            foreach($files as $file) {
                echo "  - " . basename($file) . "\n";
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
