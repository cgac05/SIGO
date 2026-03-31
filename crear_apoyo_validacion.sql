-- Script SQL para crear Apoyo de Validación
-- Ejecutar en SQL Server

-- 1. Crear el Apoyo
INSERT INTO Apoyos (
    nombre_apoyo,
    descripcion,
    sincronizar_calendario,
    recordatorio_dias,
    activo,
    fecha_creacion,
    fecha_actualizacion
) VALUES (
    N'✅ VALIDACIÓN - Test Evento Simple',
    N'Apoyo para validar creación en Google Calendar en fecha correcta',
    1,
    1,
    1,
    GETDATE(),
    GETDATE()
);

-- Obtener el ID del apoyo creado
SELECT @apoyo_id = SCOPE_IDENTITY();
DECLARE @apoyo_id INT = SCOPE_IDENTITY();

-- 2. Crear el Hito para mañana (31 de marzo de 2026)
INSERT INTO hitos_apoyo (
    fk_id_apoyo,
    nombre_hito,
    descripcion,
    fecha_inicio,
    fecha_fin,
    estado,
    activo,
    fecha_creacion,
    fecha_actualizacion
) VALUES (
    @apoyo_id,
    N'🎯 Evento de Validación',
    N'Hito único para validar que se crea correctamente en Google Calendar',
    CAST(DATEADD(DAY, 1, CAST(GETDATE() AS DATE)) AS DATETIME),
    CAST(DATEADD(DAY, 1, CAST(GETDATE() AS DATE)) AS DATETIME),
    N'programado',
    1,
    GETDATE(),
    GETDATE()
);

-- Mostrar resultado
SELECT 
    'Apoyo Creado' as Resultado,
    @apoyo_id as apoyo_id,
    N'✅ VALIDACIÓN - Test Evento Simple' as nombre_apoyo,
    1 as sincronizar_calendario,
    1 as recordatorio_dias;

SELECT 
    'Hito Creado' as Resultado,
    id_hito,
    fk_id_apoyo,
    nombre_hito,
    fecha_inicio,
    fecha_fin
FROM hitos_apoyo 
WHERE fk_id_apoyo = @apoyo_id
ORDER BY id_hito DESC;

SELECT 
    'Status' as Info,
    'Listo para crear evento en Google Calendar' as Accion;
