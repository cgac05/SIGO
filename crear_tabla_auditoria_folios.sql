-- ============================================================================
-- SCRIPT: Crear tabla auditoria_folios
-- DESCRIPCIÓN: Esta tabla registra la auditoría de folios institucionales
-- ESTADO: Necesaria para que funcione SolicitudController y FolioService
-- EJECUTAR COMO: Administrador de SQL Server
-- ============================================================================

-- Verificar si existe, si no existe crearla
IF NOT EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'[dbo].[auditoria_folios]') AND type in (N'U'))
BEGIN
    PRINT 'Creando tabla auditoria_folios...';
    
    CREATE TABLE [dbo].[auditoria_folios] (
        [id_auditoria_folio] INT PRIMARY KEY IDENTITY(1,1),
        [folio_completo] VARCHAR(50) NOT NULL UNIQUE,
        [numero_base] VARCHAR(5) NOT NULL,
        [digito_verificador] INT NOT NULL,
        [fk_id_beneficiario] INT NULL,
        [fk_folio_solicitud] INT NULL,
        [año_fiscal] INT NOT NULL,
        [fecha_generacion] DATETIME2 NOT NULL DEFAULT GETDATE(),
        [generado_por] INT NULL,
        [ip_generacion] VARCHAR(45) NULL,
        [created_at] DATETIME2 NULL DEFAULT GETDATE(),
        [updated_at] DATETIME2 NULL DEFAULT GETDATE()
    );
    
    PRINT 'Creando índices...';
    
    CREATE INDEX IX_auditoria_folios_fecha 
        ON [dbo].[auditoria_folios]([fecha_generacion] DESC);
    
    CREATE INDEX IX_auditoria_folios_año_fiscal 
        ON [dbo].[auditoria_folios]([año_fiscal]);
    
    CREATE INDEX IX_auditoria_folios_fk_solicitud 
        ON [dbo].[auditoria_folios]([fk_folio_solicitud]);
    
    CREATE INDEX IX_auditoria_folios_fk_beneficiario 
        ON [dbo].[auditoria_folios]([fk_id_beneficiario]);
    
    CREATE INDEX IX_auditoria_folios_año_fiscal_pendientes 
        ON [dbo].[auditoria_folios]([año_fiscal]) 
        WHERE [fk_folio_solicitud] IS NULL;
    
    PRINT 'Tabla auditoria_folios creada exitosamente.';
    
END
ELSE
BEGIN
    PRINT 'La tabla auditoria_folios ya existe.';
END

-- ============================================================================
-- ESTRUCTURA DE LA TABLA
-- ============================================================================
/*
Columnas:
  - id_auditoria_folio: INT (PRIMARY KEY) - Identificador único
  - folio_completo: VARCHAR(50) UNIQUE - Folio institucional completo (ej: SIGO-2026-00001-3)
  - numero_base: VARCHAR(5) - Número sin dígito verificador (ej: 00001)
  - digito_verificador: INT - Dígito de validación Verhoeff (0-9)
  - fk_id_beneficiario: INT NULL - Referencia al beneficiario (si aplica)
  - fk_folio_solicitud: INT NULL - Referencia a la solicitud cuando se usa el folio
  - año_fiscal: INT - Año fiscal de generación del folio
  - fecha_generacion: DATETIME2 - Fecha/hora de generación
  - generado_por: INT NULL - ID del usuario que generó
  - ip_generacion: VARCHAR(45) NULL - IP desde donde se generó (IPv4 o IPv6)
  - created_at: DATETIME2 NULL - Timestamp de creación (Laravel)
  - updated_at: DATETIME2 NULL - Timestamp de actualización (Laravel)

Índices:
  - IX_auditoria_folios_fecha: Para búsquedas por fecha
  - IX_auditoria_folios_año_fiscal: Para búsquedas por año
  - IX_auditoria_folios_fk_solicitud: Para relaciones con solicitudes
  - IX_auditoria_folios_fk_beneficiario: Para relaciones con beneficiarios
  - IX_auditoria_folios_año_fiscal_pendientes: Para folios no usados

Permisos requeridos:
  - debe permitir SELECT, INSERT, UPDATE en la tabla
*/

-- ============================================================================
-- PRUEBAS POSTERIORES A CREAR
-- ============================================================================
/*
-- Verificar que la tabla se creó correctamente
SELECT COUNT(*) as 'Registros' FROM [dbo].[auditoria_folios];

-- Ver estructura
EXEC sp_help '[dbo].[auditoria_folios]';

-- Ver índices
SELECT name FROM sys.indexes WHERE object_id = OBJECT_ID('[dbo].[auditoria_folios]');
*/

