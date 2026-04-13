-- 🧪 ACTUALIZAR FOLIO 1000 A HITO ANÁLISIS ADMINISTRATIVO

-- 1️⃣ VER ESTADO ACTUAL DE 1000
SELECT 
    s.folio,
    s.fk_id_apoyo,
    ap.nombre_apoyo,
    s.fk_id_hito_actual,
    h.clave_hito,
    h.nombre_hito,
    s.presupuesto_confirmado,
    s.cuv,
    s.monto_entregado
FROM Solicitudes s
LEFT JOIN Apoyos ap ON s.fk_id_apoyo = ap.id_apoyo
LEFT JOIN Hitos_Apoyo h ON s.fk_id_hito_actual = h.id_hito
WHERE s.folio = 1000;

-- 2️⃣ VER HITOS DISPONIBLES PARA EL APOYO DE 1000
SELECT 
    ha.id_hito,
    ha.clave_hito,
    ha.nombre_hito,
    ha.orden_hito,
    ha.fecha_inicio,
    ha.fecha_fin
FROM Hitos_Apoyo ha
WHERE ha.fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1000)
AND ha.activo = 1
ORDER BY ha.orden_hito;

-- 3️⃣ OBTENER ID DEL HITO ANALISIS_ADMIN PARA EL FOLIO 1000
DECLARE @id_hito_admin INT = (
    SELECT TOP 1 ha.id_hito
    FROM Hitos_Apoyo ha
    WHERE ha.fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1000)
    AND ha.clave_hito = 'ANALISIS_ADMIN'
    AND ha.activo = 1
);

IF @id_hito_admin IS NOT NULL
BEGIN
    -- 4️⃣ ACTUALIZAR FOLIO 1000 AL HITO ANÁLISIS_ADMIN
    UPDATE Solicitudes
    SET 
        fk_id_hito_actual = @id_hito_admin,
        fecha_actualizacion = GETDATE()
    WHERE folio = 1000;
    
    SELECT '✅ FOLIO 1000 ACTUALIZADO A ANÁLISIS_ADMIN' as resultado;
    
    -- 5️⃣ VERIFICAR CAMBIO
    SELECT 
        s.folio,
        s.fk_id_apoyo,
        ap.nombre_apoyo,
        h.clave_hito,
        h.nombre_hito,
        h.orden_hito,
        s.presupuesto_confirmado,
        s.cuv,
        s.monto_entregado,
        s.fecha_actualizacion
    FROM Solicitudes s
    LEFT JOIN Apoyos ap ON s.fk_id_apoyo = ap.id_apoyo
    LEFT JOIN Hitos_Apoyo h ON s.fk_id_hito_actual = h.id_hito
    WHERE s.folio = 1000;
END
ELSE
BEGIN
    SELECT '❌ NO SE ENCONTRÓ HITO ANALISIS_ADMIN PARA EL APOYO DEL FOLIO 1000' as error;
    
    -- Mostrar qué hitos están disponibles
    SELECT 'Hitos disponibles:' as info;
    SELECT 
        ha.id_hito,
        ha.clave_hito,
        ha.nombre_hito
    FROM Hitos_Apoyo ha
    WHERE ha.fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1000)
    AND ha.activo = 1;
END
