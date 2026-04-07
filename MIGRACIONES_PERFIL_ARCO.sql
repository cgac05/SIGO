-- Migración para agregar campos de Perfil, 2FA y ARCO a tabla Usuarios
-- Ejecutar con: sqlcmd -S localhost -E -d BD_SIGO -i MIGRACIONES_PERFIL_ARCO.sql

USE BD_SIGO
GO

-- Verificar y agregar columna foto_perfil
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='foto_perfil')
    ALTER TABLE Usuarios ADD foto_perfil NVARCHAR(255) NULL
GO

-- Verificar y agregar columnas de 2FA
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='two_factor_enabled')
    ALTER TABLE Usuarios ADD two_factor_enabled BIT DEFAULT 0
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='two_factor_secret')
    ALTER TABLE Usuarios ADD two_factor_secret NVARCHAR(255) NULL
GO

-- Verificar y agregar columnas de preferencias de notificación
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='notif_email_news')
    ALTER TABLE Usuarios ADD notif_email_news BIT DEFAULT 1
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='notif_email_apoyos')
    ALTER TABLE Usuarios ADD notif_email_apoyos BIT DEFAULT 1
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='notif_email_status')
    ALTER TABLE Usuarios ADD notif_email_status BIT DEFAULT 1
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='notif_email_marketing')
    ALTER TABLE Usuarios ADD notif_email_marketing BIT DEFAULT 0
GO

-- Verificar y agregar columnas de ARCO - Cancelación
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='arco_cancelacion_solicitada')
    ALTER TABLE Usuarios ADD arco_cancelacion_solicitada BIT DEFAULT 0
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='arco_cancelacion_fecha')
    ALTER TABLE Usuarios ADD arco_cancelacion_fecha DATETIME NULL
GO

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME='Usuarios' AND COLUMN_NAME='arco_cancelacion_razon')
    ALTER TABLE Usuarios ADD arco_cancelacion_razon NVARCHAR(MAX) NULL
GO

-- Agregar comentarios/descripción a las columnas nuevas
EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Ruta a foto de perfil local' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'foto_perfil'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Autenticación de dos factores habilitada' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'two_factor_enabled'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Secret para autenticador 2FA (Google Authenticator, Authy)' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'two_factor_secret'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Recibir noticias y actualizaciones' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'notif_email_news'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Recibir notificaciones sobre nuevos apoyos' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'notif_email_apoyos'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Recibir notificaciones de cambios de estado en solicitudes' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'notif_email_status'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Recibir promociones y ofertas especiales' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'notif_email_marketing'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Cancelación de cuenta solicitada (Derecho ARCO)' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'arco_cancelacion_solicitada'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Fecha y hora de solicitud de cancelación' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'arco_cancelacion_fecha'
GO

EXEC sys.sp_addextendedproperty @name=N'MS_Description', @value=N'Razón de solicitud de cancelación (ARCO)' , @level0type=N'SCHEMA',@level0name=N'dbo', @level1type=N'TABLE',@level1name=N'Usuarios', @level2type=N'COLUMN',@level2name=N'arco_cancelacion_razon'
GO

-- Verificar que la migración se completó
SELECT 'Migración completada exitosamente' AS Mensaje
SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME='Usuarios' 
AND COLUMN_NAME IN ('foto_perfil', 'two_factor_enabled', 'two_factor_secret', 'notif_email_news', 'notif_email_apoyos', 'notif_email_status', 'notif_email_marketing', 'arco_cancelacion_solicitada', 'arco_cancelacion_fecha', 'arco_cancelacion_razon')
ORDER BY ORDINAL_POSITION
GO
