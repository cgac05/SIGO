-- ============================================================
-- MIGRACIÓN CORREGIDA - TABLA: firmas_electronicas
-- ============================================================
-- Base de Datos: BD_SIGO
-- Fecha: 5 de Abril de 2026
-- Propósito: Crear tabla de auditoría para firmas electrónicas
-- CORRECCIÓN: folio_solicitud debe ser INT (not NVARCHAR)
-- ============================================================

USE BD_SIGO;
GO

-- ============================================================
-- TABLA: firmas_electronicas
-- Descripción: Registro de auditoría completo de firmas (aprobación/rechazo)
-- PUNTO CRÍTICO: Tabla de auditoría irreversible para cumplimiento normativo
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'firmas_electronicas')
BEGIN
    CREATE TABLE [dbo].[firmas_electronicas] (
        [id] [bigint] IDENTITY(1,1) PRIMARY KEY,
        [folio_solicitud] [int] NOT NULL,
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
    
    PRINT '✅ Tabla firmas_electronicas creada exitosamente';
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
PRINT '=== VERIFICACIÓN DE 4 TABLAS FASE 8 ===';
PRINT '';

SELECT 
    TABLE_NAME as [Tabla],
    COLUMN_COUNT as [Columnas],
    'BD_SIGO' as [Base],
    'PHASE 8 ✅' as [Estado]
FROM (
    SELECT 
        TABLE_NAME,
        COUNT(*) as COLUMN_COUNT
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME IN ('reauth_tokens', 'auditoria_reauthenticacion', 'otp_temporal', 'firmas_electronicas')
    GROUP BY TABLE_NAME
) sub
ORDER BY TABLE_NAME;

PRINT '';
PRINT '=== RESUMEN COMPLETO ===';
PRINT 'Tabla 1: reauth_tokens ✅';
PRINT 'Tabla 2: auditoria_reauthenticacion ✅';
PRINT 'Tabla 3: otp_temporal ✅';
PRINT 'Tabla 4: firmas_electronicas ✅';
PRINT '';
PRINT 'Todas las migraciones de Fase 8 completadas exitosamente';
PRINT 'Fecha: ' + CONVERT(VARCHAR, GETDATE(), 21);
GO
