-- ============================================================
-- MIGRACIONES - FASE 8: FIRMA ELECTRÓNICA
-- ============================================================
-- Base de Datos: BD_SIGO
-- Fecha: 5 de Abril de 2026
-- Propósito: Crear tablas necesarias para re-autenticación y firma electrónica
-- ============================================================

USE BD_SIGO;
GO

-- ============================================================
-- TABLA 1: reauth_tokens
-- Descripción: Almacena tokens temporales de re-autenticación
-- Válidos por 10 minutos, se marcan como usados después de validar
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'reauth_tokens')
BEGIN
    CREATE TABLE [dbo].[reauth_tokens] (
        [id] [bigint] IDENTITY(1,1) PRIMARY KEY,
        [usuario_id] [int] NOT NULL,
        [token] [varchar](64) NOT NULL UNIQUE,
        [expira_en] [datetime] NOT NULL,
        [usado] [bit] NOT NULL DEFAULT 0,
        [usado_en] [datetime] NULL,
        [creado_en] [datetime] DEFAULT GETDATE(),
        
        -- Foreign Key
        CONSTRAINT [FK_reauth_tokens_usuarios] 
            FOREIGN KEY ([usuario_id]) 
            REFERENCES [dbo].[Usuarios]([id_usuario])
            ON DELETE CASCADE
            ON UPDATE CASCADE,
        
        -- Índices
        INDEX [IX_reauth_tokens_usuario] NONCLUSTERED ([usuario_id]),
        INDEX [IX_reauth_tokens_token] NONCLUSTERED ([token]),
        INDEX [IX_reauth_tokens_expira] NONCLUSTERED ([expira_en])
    );
    
    PRINT 'Tabla reauth_tokens creada exitosamente';
END
ELSE
BEGIN
    PRINT 'Tabla reauth_tokens ya existe';
END;
GO

-- ============================================================
-- TABLA 2: auditoria_reauthenticacion
-- Descripción: Registro de auditoría LGPDP para intentos de re-autenticación
-- Captura: IP, navegador, SO, resultado, timestamp
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'auditoria_reauthenticacion')
BEGIN
    CREATE TABLE [dbo].[auditoria_reauthenticacion] (
        [id] [bigint] IDENTITY(1,1) PRIMARY KEY,
        [usuario_id] [int] NOT NULL,
        [exitoso] [bit] NOT NULL,
        [razon] [nvarchar](255) NULL,
        [ip_address] [nvarchar](45) NULL,
        [user_agent] [nvarchar](MAX) NULL,
        [timestamp] [datetime] DEFAULT GETDATE(),
        
        -- Foreign Key
        CONSTRAINT [FK_auditoria_reauth_usuarios] 
            FOREIGN KEY ([usuario_id]) 
            REFERENCES [dbo].[Usuarios]([id_usuario])
            ON DELETE CASCADE
            ON UPDATE CASCADE,
        
        -- Índices
        INDEX [IX_auditoria_reauth_usuario] NONCLUSTERED ([usuario_id]),
        INDEX [IX_auditoria_reauth_timestamp] NONCLUSTERED ([timestamp]),
        INDEX [IX_auditoria_reauth_exitoso] NONCLUSTERED ([exitoso])
    );
    
    PRINT 'Tabla auditoria_reauthenticacion creada exitosamente';
END
ELSE
BEGIN
    PRINT 'Tabla auditoria_reauthenticacion ya existe';
END;
GO

-- ============================================================
-- TABLA 3: otp_temporal
-- Descripción: Almacena códigos OTP temporales para 2FA
-- Usados en Google Authenticator, Authy, etc.
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'otp_temporal')
BEGIN
    CREATE TABLE [dbo].[otp_temporal] (
        [id] [bigint] IDENTITY(1,1) PRIMARY KEY,
        [usuario_id] [int] NOT NULL,
        [codigo] [varchar](6) NOT NULL,
        [expira_en] [datetime] NOT NULL,
        [intentos] [int] DEFAULT 0,
        [creado_en] [datetime] DEFAULT GETDATE(),
        
        -- Foreign Key
        CONSTRAINT [FK_otp_temporal_usuarios] 
            FOREIGN KEY ([usuario_id]) 
            REFERENCES [dbo].[Usuarios]([id_usuario])
            ON DELETE CASCADE
            ON UPDATE CASCADE,
        
        -- Índices
        INDEX [IX_otp_temporal_usuario] NONCLUSTERED ([usuario_id]),
        INDEX [IX_otp_temporal_expira] NONCLUSTERED ([expira_en]),
        INDEX [IX_otp_temporal_codigo] NONCLUSTERED ([codigo])
    );
    
    PRINT 'Tabla otp_temporal creada exitosamente';
END
ELSE
BEGIN
    PRINT 'Tabla otp_temporal ya existe';
END;
GO

-- ============================================================
-- TABLA 4: firmas_electronicas
-- Descripción: Registro de auditoría completo de firmas (aprobación/rechazo)
-- PUNTO CRÍTICO: Tabla de auditoría irreversible para cumplimiento normativo
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'firmas_electronicas')
BEGIN
    CREATE TABLE [dbo].[firmas_electronicas] (
        [id] [bigint] IDENTITY(1,1) PRIMARY KEY,
        [folio_solicitud] [nvarchar](20) NOT NULL,
        [id_directivo] [int] NOT NULL,
        [tipo_firma] [nvarchar](20) NOT NULL,
        [sello_digital] [varchar](64) NOT NULL,
        [cuv] [varchar](20) NOT NULL UNIQUE,
        [estado] [nvarchar](20) NOT NULL DEFAULT 'EXITOSA',
        [metadata] [nvarchar](MAX) NULL,
        [fecha_firma] [datetime] DEFAULT GETDATE(),
        [created_at] [datetime] DEFAULT GETDATE(),
        [updated_at] [datetime] DEFAULT GETDATE(),
        
        -- Constraint: tipo_firma solo puede ser APROBACION o RECHAZO
        CONSTRAINT [CK_firmas_tipo_firma] 
            CHECK ([tipo_firma] IN ('APROBACION', 'RECHAZO')),
        
        -- Constraint: estado solo puede ser EXITOSA, FALLIDA, PENDIENTE
        CONSTRAINT [CK_firmas_estado] 
            CHECK ([estado] IN ('EXITOSA', 'FALLIDA', 'PENDIENTE')),
        
        -- Foreign Key
        CONSTRAINT [FK_firmas_solicitudes] 
            FOREIGN KEY ([folio_solicitud]) 
            REFERENCES [dbo].[Solicitudes]([folio])
            ON DELETE CASCADE
            ON UPDATE CASCADE,
        
        CONSTRAINT [FK_firmas_directivos] 
            FOREIGN KEY ([id_directivo]) 
            REFERENCES [dbo].[Usuarios]([id_usuario])
            ON DELETE CASCADE
            ON UPDATE CASCADE,
        
        -- Índices
        INDEX [IX_firmas_folio] NONCLUSTERED ([folio_solicitud]),
        INDEX [IX_firmas_directivo] NONCLUSTERED ([id_directivo]),
        INDEX [IX_firmas_cuv] NONCLUSTERED ([cuv]),
        INDEX [IX_firmas_tipo] NONCLUSTERED ([tipo_firma]),
        INDEX [IX_firmas_fecha] NONCLUSTERED ([fecha_firma])
    );
    
    PRINT 'Tabla firmas_electronicas creada exitosamente';
END
ELSE
BEGIN
    PRINT 'Tabla firmas_electronicas ya existe';
END;
GO

-- ============================================================
-- VERIFICACIÓN FINAL
-- ============================================================
PRINT '';
PRINT '=== VERIFICACIÓN DE TABLAS CREADAS ===';
PRINT '';

SELECT 
    'BD_SIGO' as Base_Datos,
    OBJECT_NAME(object_id) as Tabla,
    CASE 
        WHEN OBJECT_NAME(object_id) IN ('reauth_tokens', 'auditoria_reauthenticacion', 'otp_temporal', 'firmas_electronicas')
        THEN '✅ NUEVA'
        ELSE 'Existente'
    END as Estado
FROM sys.columns
WHERE object_id IN (
    SELECT object_id FROM sys.objects 
    WHERE name IN ('reauth_tokens', 'auditoria_reauthenticacion', 'otp_temporal', 'firmas_electronicas')
)
GROUP BY object_id
ORDER BY OBJECT_NAME(object_id);

PRINT '';
PRINT '=== RESUMEN DE MIGRACIONES ===';
SELECT 
    TABLE_NAME as Tabla,
    'BD_SIGO' as Base,
    'PHASE 8 - FIRMA ELECTRÓNICA' as Fase
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME IN ('reauth_tokens', 'auditoria_reauthenticacion', 'otp_temporal', 'firmas_electronicas')
ORDER BY TABLE_NAME;

PRINT '';
PRINT '✅ MIGRACIONES FASE 8 COMPLETADAS';
PRINT 'Fecha: ' + CONVERT(VARCHAR, GETDATE(), 21);
GO
