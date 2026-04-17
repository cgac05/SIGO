<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "🔍 DIAGNÓSTICO DE DOCUMENTOS\n";
    echo "============================\n\n";
    
    // 1. Revisar rutas almacenadas en BD para folios con documentos funcionando
    echo "📋 FOLIOS CON DOCUMENTOS QUE FUNCIONAN (1008, 1012)\n";
    echo "---------------------------------------------------\n";
    
    foreach([1008, 1012] as $folio) {
        echo "\n▶ Folio $folio:\n";
        $stmt = $pdo->prepare("
            SELECT id_doc, fk_id_tipo_doc, ruta_archivo, estado_validacion, admin_status 
            FROM Documentos_Expediente 
            WHERE fk_folio = ?
        ");
        $stmt->execute([$folio]);
        
        while($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "  ID: {$doc['id_doc']}\n";
            echo "  Ruta en BD: {$doc['ruta_archivo']}\n";
            
            $diskPath = 'storage/app/public/' . str_replace('storage/', '', $doc['ruta_archivo']);
            $realPath = str_replace('storage/', '', $doc['ruta_archivo']);
            
            $exists1 = file_exists('c:\\xampp\\htdocs\\SIGO\\' . $diskPath);
            $exists2 = file_exists('c:\\xampp\\htdocs\\SIGO\\storage\\app\\public\\' . $realPath);
            
            echo "  Ruta física esperada: storage/app/public/$realPath\n";
            echo "  ¿Existe en disk?: " . ($exists2 ? "✅ SÍ" : "❌ NO") . "\n";
        }
    }
    
    // 2. Revisar folio 1013 (el nuevo)
    echo "\n\n📋 FOLIO 1013 (NUEVO - CLONADO)\n";
    echo "-------------------------------\n";
    
    $stmt = $pdo->prepare("
        SELECT id_doc, fk_id_tipo_doc, ruta_archivo, estado_validacion, admin_status 
        FROM Documentos_Expediente 
        WHERE fk_folio = 1013
    ");
    $stmt->execute();
    
    while($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "\n▶ ID: {$doc['id_doc']}\n";
        echo "  Ruta en BD: {$doc['ruta_archivo']}\n";
        
        $realPath = str_replace('storage/', '', $doc['ruta_archivo']);
        $fullPath = 'c:\\xampp\\htdocs\\SIGO\\storage\\app\\public\\' . $realPath;
        $exists = file_exists($fullPath);
        
        echo "  Ruta física esperada: storage/app/public/$realPath\n";
        echo "  ¿Existe?: " . ($exists ? "✅ SÍ" : "❌ NO") . "\n";
        
        if(!$exists) {
            echo "  ⚠️  Archivo NO ENCONTRADO en sistema de archivos\n";
            
            // Buscar si existe en otra ruta
            echo "  🔎 Buscando archivo en otras rutas...\n";
            $fileName = basename($realPath);
            
            $searchDirs = [
                'storage/app/public/solicitudes/',
                'public/solicitudes/',
                'storage/app/',
                'public/'
            ];
            
            foreach($searchDirs as $dir) {
                $searchPath = 'c:\\xampp\\htdocs\\SIGO\\' . $dir . $fileName;
                if(file_exists($searchPath)) {
                    echo "     ✅ ENCONTRADO en: $dir$fileName\n";
                }
            }
        }
    }
    
    // 3. Listar archivos que existen en storage/app/public/solicitudes/
    echo "\n\n📁 ARCHIVOS EN storage/app/public/solicitudes/\n";
    echo "--------------------------------------------\n";
    
    $storageDir = 'c:\\xampp\\htdocs\\SIGO\\storage\\app\\public\\solicitudes\\';
    if(is_dir($storageDir)) {
        $files = scandir($storageDir);
        foreach($files as $file) {
            if($file != '.' && $file != '..') {
                echo "  - $file\n";
            }
        }
    } else {
        echo "  ❌ Directorio no existe\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
