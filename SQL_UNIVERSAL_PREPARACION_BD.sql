-- ============================================================================
-- SQL UNIVERSAL PARA PREPARAR BD - APLICABLE A CUALQUIER FOLIOS
-- ============================================================================

-- 1. CORREGIR TODAS LAS RUTAS DE DOCUMENTOS
-- Cambiar "storage/solicitudes/..." → "solicitudes/..."
UPDATE Documentos_Expediente 
SET ruta_archivo = REPLACE(ruta_archivo, 'storage/', '') 
WHERE ruta_archivo LIKE 'storage/%';

-- 2. ACTUALIZAR ESTADOS DE DOCUMENTOS
-- Documentos aprobados por admin pero en estado "Pendiente" → cambiar a "Correcto"
UPDATE Documentos_Expediente 
SET estado_validacion = 'Correcto' 
WHERE admin_status = 'aceptado' 
AND estado_validacion = 'Pendiente';

-- 3. ACTUALIZAR ESTADOS DE SOLICITUDES
-- Solicitudes sin CUV (sin firmar) con documentos verificados → estado 10 (DOCUMENTOS_VERIFICADOS)
UPDATE Solicitudes 
SET fk_id_estado = 10
WHERE cuv IS NULL 
AND fk_id_estado != 3
AND EXISTS (
    SELECT 1 FROM Documentos_Expediente d 
    WHERE d.fk_folio = Solicitudes.folio 
    AND d.estado_validacion = 'Correcto'
);

-- 4. VERIFICAR RESULTADOS
SELECT 
    'Solicitudes Sin Firmar (Listas para firma)' as Descripcion,
    COUNT(*) as Total
FROM Solicitudes 
WHERE cuv IS NULL;

UNION ALL

SELECT 
    'Solicitudes Firmadas',
    COUNT(*)
FROM Solicitudes 
WHERE cuv IS NOT NULL;

UNION ALL

SELECT 
    'Documentos Validados (Correcto)',
    COUNT(*)
FROM Documentos_Expediente 
WHERE estado_validacion = 'Correcto';
