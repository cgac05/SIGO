-- Recrear tabla notificaciones con estructura correcta (tipos ajustados)
-- Ejecutar como SA

USE BD_SIGO;
GO

-- Respaldar tabla antigua si tiene datos importantes
IF EXISTS (SELECT * FROM sys.tables WHERE name = 'notificaciones')
BEGIN
    -- Respaldar
    SELECT * INTO notificaciones_old_backup FROM notificaciones WHERE 1=0;
    
    -- Renombrar tabla antigua
    EXEC sp_rename 'notificaciones', 'notificaciones_tmp_old';
    PRINT 'Tabla antigua respaldada';
END

-- Crear tabla notificaciones con estructura correcta (INT para coincid con usuarios.id_usuario)
CREATE TABLE notificaciones (
    id BIGINT PRIMARY KEY IDENTITY(1,1),
    id_beneficiario INT NOT NULL,
    tipo NVARCHAR(255) NOT NULL CHECK (tipo IN ('documento_rechazado', 'hito_cambio', 'solicitud_rechazada')),
    titulo NVARCHAR(255) NOT NULL,
    mensaje NVARCHAR(MAX) NOT NULL,
    datos NVARCHAR(MAX) NULL,
    accion_url NVARCHAR(255) NULL,
    leida BIT NOT NULL DEFAULT 0,
    created_at DATETIME NULL DEFAULT GETDATE(),
    updated_at DATETIME NULL DEFAULT GETDATE(),
    
    -- Constraint
    CONSTRAINT FK_notificaciones_usuarios FOREIGN KEY (id_beneficiario) 
        REFERENCES usuarios(id_usuario) ON DELETE CASCADE
);

-- Crear índices para optimización
CREATE INDEX idx_notificaciones_beneficiario ON notificaciones(id_beneficiario);
CREATE INDEX idx_notificaciones_tipo ON notificaciones(tipo);
CREATE INDEX idx_notificaciones_leida ON notificaciones(leida);
CREATE INDEX idx_notificaciones_created_at ON notificaciones(created_at);

PRINT 'Tabla notificaciones creada exitosamente con estructura correcta.';

-- Limpiar tabla antigua si existe
IF OBJECT_ID('notificaciones_tmp_old', 'U') IS NOT NULL
BEGIN
    DROP TABLE notificaciones_tmp_old;
    PRINT 'Tabla temporal eliminada.';
END

-- Actualizar migrations si no existe registro
IF NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_04_03_154013_create_notificaciones_table')
BEGIN
    INSERT INTO migrations (migration, batch) 
    VALUES ('2026_04_03_154013_create_notificaciones_table', 6);
    PRINT 'Migración registrada en tabla migrations ofreciendo batch 6.';
END
ELSE
BEGIN
    PRINT 'Migración ya está registrada.';
END

-- Verificación final
PRINT '';
PRINT 'VERIFICACIÓN - Estructura de tabla notificaciones:';
SELECT COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'notificaciones' 
ORDER BY ORDINAL_POSITION;

PRINT '';
PRINT 'Validación completada exitosamente.';

GO
