-- Crear tabla de notificaciones manualmente
-- Este script se ejecuta con permisos de SA

USE BD_SIGO;
GO

-- Crear tabla notificaciones
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'notificaciones')
BEGIN
    CREATE TABLE notificaciones (
        id BIGINT PRIMARY KEY IDENTITY(1,1),
        id_beneficiario BIGINT NOT NULL,
        tipo NVARCHAR(255) NOT NULL CHECK (tipo IN ('documento_rechazado', 'hito_cambio', 'solicitud_rechazada')),
        titulo NVARCHAR(255) NOT NULL,
        mensaje NVARCHAR(MAX) NOT NULL,
        datos NVARCHAR(MAX) NULL,
        accion_url NVARCHAR(255) NULL,
        leida BIT NOT NULL DEFAULT 0,
        created_at DATETIME NULL DEFAULT GETDATE(),
        updated_at DATETIME NULL DEFAULT GETDATE(),
        
        -- Índices
        CONSTRAINT FK_notificaciones_usuarios FOREIGN KEY (id_beneficiario) 
            REFERENCES usuarios(id_usuario) ON DELETE CASCADE
    );
    
    -- Crear índices
    CREATE INDEX idx_notificaciones_beneficiario ON notificaciones(id_beneficiario);
    CREATE INDEX idx_notificaciones_tipo ON notificaciones(tipo);
    CREATE INDEX idx_notificaciones_leida ON notificaciones(leida);
    CREATE INDEX idx_notificaciones_created_at ON notificaciones(created_at);
    
    PRINT 'Tabla notificaciones creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La tabla notificaciones ya existe.';
END

-- Registrar en migrations para que Laravel no intente crearla
IF NOT EXISTS (SELECT 1 FROM migrations WHERE migration = '2026_04_03_154013_create_notificaciones_table')
BEGIN
    INSERT INTO migrations (migration, batch) 
    VALUES ('2026_04_03_154013_create_notificaciones_table', 6);
    PRINT 'Migración registrada en tabla migrations.';
END
ELSE
BEGIN
    PRINT 'Migración ya está registrada.';
END

GO
