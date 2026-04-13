-- 🔄 SCRIPT COMPLETO PARA RESETEAR FOLIO 1007
-- Incluye hitos, documentos y estados

-- 1️⃣ Ver hitos disponibles para el apoyo
SELECT 
    id_hito,
    fk_id_apoyo,
    clave_hito,
    nombre_hito,
    orden_hito,
    activo
FROM Hitos_Apoyo
WHERE fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1007)
ORDER BY orden_hito ASC;

-- 2️⃣ Ver hito actual de la solicitud
SELECT 
    s.folio,
    s.fk_id_apoyo,
    h.clave_hito,
    h.nombre_hito
FROM Solicitudes s
LEFT JOIN Hitos_Apoyo h ON s.fk_id_hito_actual = h.id_hito
WHERE s.folio = 1007;

-- 3️⃣ RESETEAR EL HITO a ANALISIS_ADMIN (es el primero)
UPDATE Solicitudes 
SET 
    fk_id_hito_actual = (
        SELECT TOP 1 id_hito 
        FROM Hitos_Apoyo 
        WHERE fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1007)
        AND clave_hito = 'INICIO_PUBLICACION'  -- O el PRIMER hito que esté activo
        ORDER BY orden_hito ASC
    )
WHERE folio = 1007;

-- 4️⃣ RESETEAR CAMPOS DE FASES
UPDATE Solicitudes 
SET 
    presupuesto_confirmado = 0,
    cuv = NULL,
    monto_entregado = NULL,
    fecha_entrega_recurso = NULL,
    ruta_pdf_final = NULL,
    fk_id_estado = 1  -- Pendiente
WHERE folio = 1007;

-- 5️⃣ RESETEAR DOCUMENTOS
UPDATE Documentos_Expediente 
SET 
    estado_validacion = 'Pendiente',
    observaciones_revision = NULL,
    revisado_por = NULL,
    fecha_revision = NULL
WHERE fk_folio = 1007;

-- 6️⃣ VERIFICAR ESTADO FINAL
SELECT 
    s.folio,
    s.fk_id_apoyo,
    h.clave_hito AS [Hito Actual],
    s.presupuesto_confirmado AS [Fase 1],
    s.cuv AS [Fase 2],
    s.monto_entregado AS [Fase 3],
    COUNT(DISTINCT de.id_doc) AS [Total Docs],
    SUM(CASE WHEN de.estado_validacion = 'Aprobado' THEN 1 ELSE 0 END) AS [Docs Aprobados]
FROM Solicitudes s
LEFT JOIN Hitos_Apoyo h ON s.fk_id_hito_actual = h.id_hito
LEFT JOIN Documentos_Expediente de ON s.folio = de.fk_folio
WHERE s.folio = 1007
GROUP BY s.folio, s.fk_id_apoyo, h.clave_hito, s.presupuesto_confirmado, s.cuv, s.monto_entregado;
