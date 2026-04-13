-- 🔄 RESETEAR FOLIO 1000 PARA PRUEBAS DE NUEVA INTERFAZ DE FIRMA
-- Objetivo: Volver a estado "listo para firmar" en Fase 2

-- 1️⃣ RESETEAR SOLICITUD
UPDATE Solicitudes 
SET 
    cuv = NULL,
    fk_id_hito_actual = (SELECT TOP 1 id_hito FROM Hitos_Apoyo WHERE clave_hito = 'ANALISIS_ADMIN' AND fk_id_apoyo = 5 ORDER BY orden_hito),
    directivo_autorizo = NULL,
    fecha_actualizacion = GETDATE(),
    presupuesto_confirmado = 1
WHERE folio = 1000;

-- 2️⃣ ASEGURAR QUE TODOS LOS DOCUMENTOS ESTÉN APROBADOS (Correcto)
UPDATE Documentos_Expediente
SET estado_validacion = 'Correcto'
WHERE fk_folio = 1000;

-- 3️⃣ VERIFICAR RESULTADO
SELECT 
    '=== ESTADO FOLIO 1000 ===' as [Estado],
    folio,
    cuv,
    fk_id_hito_actual,
    directivo_autorizo,
    presupuesto_confirmado,
    fecha_actualizacion
FROM Solicitudes 
WHERE folio = 1000;

SELECT 
    '=== DOCUMENTOS FOLIO 1000 ===' as [Documentos],
    COUNT(*) as total_documentos, 
    COUNT(CASE WHEN estado_validacion = 'Correcto' THEN 1 END) as documentos_aprobados,
    COUNT(CASE WHEN estado_validacion != 'Correcto' THEN 1 END) as documentos_pendientes
FROM Documentos_Expediente 
WHERE fk_folio = 1000;

-- 4️⃣ VER INFORMACIÓN COMPLETA
SELECT 
    '=== INFO COMPLETA ===' as [Info],
    s.folio,
    s.fk_curp,
    s.fk_id_apoyo,
    ap.nombre_apoyo,
    ap.monto_maximo,
    h.clave_hito,
    b.nombre as beneficiario_nombre,
    b.email as beneficiario_email
FROM Solicitudes s
LEFT JOIN Apoyos ap ON s.fk_id_apoyo = ap.id_apoyo
LEFT JOIN Hitos_Apoyo h ON s.fk_id_hito_actual = h.id_hito
LEFT JOIN Beneficiarios b ON s.fk_curp = b.curp
WHERE s.folio = 1000;
