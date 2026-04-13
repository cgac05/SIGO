-- 🔄 SCRIPT PARA RESETEAR FOLIO 1007 A ESTADO INICIAL
-- Este script pone la solicitud "de cero" para hacer pruebas manuales

-- 1️⃣ RESETEAR campos de fases en Solicitudes
UPDATE Solicitudes 
SET 
    presupuesto_confirmado = 0,
    cuv = NULL,
    monto_entregado = NULL,
    fecha_entrega_recurso = NULL,
    ruta_pdf_final = NULL,
    fk_id_estado = 1  -- Estado: "Pendiente" o inicial
WHERE folio = 1007;

-- 2️⃣ RESETEAR estado de documentos relacionados
UPDATE Documentos_Expediente 
SET 
    estado_validacion = 'Pendiente',
    observaciones_revision = NULL,
    revisado_por = NULL,
    fecha_revision = NULL
WHERE fk_folio = 1007;

-- 3️⃣ VERIFICAR estado actual
SELECT 
    s.folio,
    s.presupuesto_confirmado AS [F1: Presupuesto?],
    s.cuv AS [F2: CUV],
    s.monto_entregado AS [F3: Monto?],
    CASE 
        WHEN s.presupuesto_confirmado = 0 THEN '🔴 FASE 1 - Listo para revisar'
        WHEN s.presupuesto_confirmado = 1 AND s.cuv IS NULL THEN '🟡 FASE 2 - Listo para firmar'
        WHEN s.presupuesto_confirmado = 1 AND s.cuv IS NOT NULL AND s.monto_entregado IS NULL THEN '🟡 FASE 3 - Listo para cerrar'
        WHEN s.monto_entregado IS NOT NULL THEN '🟢 COMPLETA'
    END AS [Estado Actual]
FROM Solicitudes s
WHERE s.folio = 1007;

-- 4️⃣ VER documentos disponibles para revisar
SELECT 
    d.id_doc,
    d.fk_folio,
    t.nombre_tipo_documento,
    d.nombre_archivo,
    d.estado_validacion,
    d.fecha_creacion
FROM Documentos_Expediente d
LEFT JOIN Tipo_Documento t ON d.fk_id_tipo_documento = t.id_tipo_documento
WHERE d.fk_folio = 1007
ORDER BY d.id_doc ASC;
