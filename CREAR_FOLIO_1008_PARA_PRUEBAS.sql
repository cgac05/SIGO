-- 🧪 CREAR FOLIO 1008 DUPLICANDO 1007 PARA PRUEBAS LIMPIAS

-- 1️⃣ VER DATOS DE 1007
SELECT * FROM Solicitudes WHERE folio = 1007;

-- 2️⃣ DUPLICAR SOLICITUD 1007 → 1008
INSERT INTO Solicitudes 
(
    folio,
    fk_id_apoyo,
    fk_curp,
    fk_id_estado,
    permite_correcciones,
    cuv,
    folio_institucional,
    fecha_creacion,
    presupuesto_confirmado,
    monto_entregado,
    fk_id_hito_actual,
    fecha_actualizacion,
    observaciones_internas
)
SELECT
    1008 as folio,  -- Nuevo folio
    fk_id_apoyo,
    fk_curp,
    1 as fk_id_estado,  -- Estado inicial: Pendiente
    0 as permite_correcciones,
    NULL as cuv,  -- Sin CUV
    CONCAT('SIGO-2026-XAL-1008') as folio_institucional,
    GETDATE() as fecha_creacion,
    0 as presupuesto_confirmado,  -- Fase 1 no iniciada
    NULL as monto_entregado,  -- Fase 3 no iniciada
    (SELECT TOP 1 id_hito FROM Hitos_Apoyo 
     WHERE fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1007)
     ORDER BY orden_hito ASC) as fk_id_hito_actual,  -- Primer hito
    GETDATE() as fecha_actualizacion,
    'Copia de 1007 para pruebas' as observaciones_internas
FROM Solicitudes 
WHERE folio = 1007;

-- 3️⃣ DUPLICAR DOCUMENTOS 1007 → 1008
INSERT INTO Documentos_Expediente
(
    fk_folio,
    fk_id_tipo_documento,
    nombre_archivo,
    nombre_digital,
    tamano,
    ruta_almacenamiento,
    estado_validacion,
    fecha_creacion,
    observaciones_revision,
    revisado_por,
    fecha_revision,
    webview_link,
    official_file_id,
    source_file_id
)
SELECT
    1008 as fk_folio,  -- Nuevo folio
    fk_id_tipo_documento,
    nombre_archivo,
    nombre_digital,
    tamano,
    REPLACE(ruta_almacenamiento, 'folio_1007', 'folio_1008') as ruta_almacenamiento,
    'Pendiente' as estado_validacion,  -- Estado inicial
    GETDATE() as fecha_creacion,
    NULL as observaciones_revision,
    NULL as revisado_por,
    NULL as fecha_revision,
    NULL as webview_link,
    NULL as official_file_id,
    NULL as source_file_id
FROM Documentos_Expediente
WHERE fk_folio = 1007;

-- 4️⃣ VERIFICAR CREACIÓN
SELECT 
    s.folio,
    s.fk_id_apoyo,
    ap.nombre_apoyo,
    h.clave_hito,
    s.presupuesto_confirmado,
    s.cuv,
    s.monto_entregado,
    COUNT(DISTINCT d.id_doc) as total_documentos
FROM Solicitudes s
LEFT JOIN Apoyos ap ON s.fk_id_apoyo = ap.id_apoyo
LEFT JOIN Hitos_Apoyo h ON s.fk_id_hito_actual = h.id_hito
LEFT JOIN Documentos_Expediente d ON s.folio = d.fk_folio
WHERE s.folio IN (1007, 1008)
GROUP BY s.folio, s.fk_id_apoyo, ap.nombre_apoyo, h.clave_hito, s.presupuesto_confirmado, s.cuv, s.monto_entregado
ORDER BY s.folio;

-- 5️⃣ VER DOCUMENTOS DE 1008
SELECT 
    id_doc,
    fk_folio,
    nombre_archivo,
    estado_validacion
FROM Documentos_Expediente
WHERE fk_folio = 1008
ORDER BY id_doc;
