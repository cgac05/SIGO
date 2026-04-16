<?php
try {
    $pdo = new PDO('sqlsrv:Server=sigo.database.windows.net;Database=SIGO_BD', 'chguavilaca', '3nu7zjMx4Vuj');
    
    echo "📋 CLONANDO FOLIO 1007 A NUEVO FOLIO DE PRUEBA\n";
    echo "==============================================\n\n";
    
    // 1. Obtener datos del folio 1007
    echo "1️⃣  Leyendo datos del folio 1007...\n";
    $stmt = $pdo->query("SELECT * FROM Solicitudes WHERE folio = 1007");
    $folio1007 = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$folio1007) {
        echo "❌ Folio 1007 no existe\n";
        exit;
    }
    
    // 2. Obtener siguiente folio disponible
    $stmt = $pdo->query("SELECT MAX(CAST(folio as INT)) as max_folio FROM Solicitudes");
    $maxFolio = $stmt->fetch(PDO::FETCH_ASSOC)['max_folio'];
    $nuevoFolio = $maxFolio + 1;
    echo "   Nuevo folio será: $nuevoFolio\n\n";
    
    // 3. Crear nueva solicitud copiando folio 1007
    echo "2️⃣  Creando nueva solicitud...\n";
    $pdo->exec("SET IDENTITY_INSERT Solicitudes ON");
    $stmt = $pdo->prepare("
        INSERT INTO Solicitudes (folio, fk_curp, fk_id_apoyo, fk_id_estado, fk_id_prioridad, fecha_creacion, fecha_actualizacion, permite_correcciones, presupuesto_confirmado, monto_entregado)
        VALUES (?, ?, ?, 10, ?, GETDATE(), GETDATE(), 1, 0, ?)
    ");
    $stmt->execute([
        $nuevoFolio,
        $folio1007['fk_curp'],
        $folio1007['fk_id_apoyo'],
        $folio1007['fk_id_prioridad'],
        $folio1007['monto_entregado'] ?? 0
    ]);
    $pdo->exec("SET IDENTITY_INSERT Solicitudes OFF");
    echo "   ✓ Solicitud creada: Folio $nuevoFolio\n\n";
    
    // 4. Copiar documentos del folio 1007
    echo "3️⃣  Copiando documentos...\n";
    $stmt = $pdo->query("SELECT * FROM Documentos_Expediente WHERE fk_folio = 1007");
    $docsCount = 0;
    
    while($doc = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO Documentos_Expediente (fk_folio, fk_id_tipo_doc, ruta_archivo, estado_validacion, version, fecha_carga, origen_archivo, admin_status)
            VALUES (?, ?, ?, 'Correcto', ?, GETDATE(), ?, 'aceptado')
        ");
        $stmtInsert->execute([
            $nuevoFolio,
            $doc['fk_id_tipo_doc'],
            $doc['ruta_archivo'],
            $doc['version'],
            $doc['origen_archivo']
        ]);
        $docsCount++;
    }
    echo "   ✓ $docsCount documento(s) copiado(s)\n\n";
    
    // 5. Copiar presupuesto si existe
    echo "4️⃣  Copiando presupuesto...\n";
    $stmt = $pdo->query("SELECT * FROM presupuesto_apoyos WHERE folio = 1007");
    $presup = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if($presup) {
        $stmtInsert = $pdo->prepare("
            INSERT INTO presupuesto_apoyos (folio, id_categoria, monto_solicitado, monto_aprobado, estado, fecha_solicitud, created_at, updated_at)
            VALUES (?, ?, ?, ?, 'APROBADO', GETDATE(), GETDATE(), GETDATE())
        ");
        $stmtInsert->execute([
            $nuevoFolio,
            $presup['id_categoria'],
            $presup['monto_solicitado'],
            $presup['monto_aprobado']
        ]);
        echo "   ✓ Presupuesto copiado\n\n";
    }
    
    // 6. Resumen
    echo "✅ FOLIO $nuevoFolio CREADO EXITOSAMENTE\n";
    echo "========================================\n\n";
    echo "Información:\n";
    echo "   Folio: $nuevoFolio\n";
    echo "   CURP: {$folio1007['fk_curp']}\n";
    echo "   Apoyo ID: {$folio1007['fk_id_apoyo']}\n";
    echo "   Estado: 10 (DOCUMENTOS_VERIFICADOS) ✓\n";
    echo "   Documentos: $docsCount ✓\n";
    echo "   Presupuesto: Copiado ✓\n\n";
    echo "🔗 URL para acceder:\n";
    echo "   http://localhost:8000/solicitudes/proceso/$nuevoFolio\n\n";
    echo "📋 Pruebas:\n";
    echo "   1. Haz clic en 'Ver' documento → debe abrir sin errores\n";
    echo "   2. Haz clic en 'Descargar' → debe descargar PDF\n";
    echo "   3. Haz clic en 'Firmar y Generar CUV' → debe generar CUV\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage();
}
?>
