-- ============================================================
-- Script: create_solicitudes_tables.sql
-- Descripción: Crea las tablas faltantes del módulo de solicitudes.
-- Ejecutar en SSMS conectado a BD_SIGO con usuario SA o con
-- permisos CREATE TABLE / ALTER TABLE.
-- ============================================================

USE BD_SIGO;
GO

-- ============================================================
-- 1. Cat_EstadosSolicitud
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Cat_EstadosSolicitud')
BEGIN
    CREATE TABLE Cat_EstadosSolicitud (
        id_estado   INT IDENTITY(1,1) PRIMARY KEY,
        nombre_estado NVARCHAR(30) NOT NULL UNIQUE
    );

    -- Datos semilla (id_estado = 1 → DEFAULT en Solicitudes)
    SET IDENTITY_INSERT Cat_EstadosSolicitud ON;
    INSERT INTO Cat_EstadosSolicitud (id_estado, nombre_estado) VALUES
        (1, N'Pendiente'),
        (2, N'En revisión'),
        (3, N'Aprobada'),
        (4, N'Rechazada');
    SET IDENTITY_INSERT Cat_EstadosSolicitud OFF;

    PRINT 'Tabla Cat_EstadosSolicitud creada.';
END
ELSE
    PRINT 'Cat_EstadosSolicitud ya existe — omitida.';
GO

-- ============================================================
-- 2. Cat_Prioridades
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Cat_Prioridades')
BEGIN
    CREATE TABLE Cat_Prioridades (
        id_prioridad INT PRIMARY KEY,
        nivel        NVARCHAR(20) NOT NULL UNIQUE
    );

    INSERT INTO Cat_Prioridades (id_prioridad, nivel) VALUES
        (1, N'Baja'),
        (2, N'Normal'),
        (3, N'Alta');

    PRINT 'Tabla Cat_Prioridades creada.';
END
ELSE
    PRINT 'Cat_Prioridades ya existe — omitida.';
GO

-- ============================================================
-- 3. Cat_TiposDocumento
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Cat_TiposDocumento')
BEGIN
    CREATE TABLE Cat_TiposDocumento (
        id_tipo_doc      INT IDENTITY(1,1) PRIMARY KEY,
        nombre_documento NVARCHAR(100) NOT NULL UNIQUE,
        tipo_archivo_permitido NVARCHAR(20) NOT NULL DEFAULT 'pdf',
        validar_tipo_archivo BIT NOT NULL DEFAULT 1,
        descripcion      NVARCHAR(MAX) NULL
    );

    PRINT 'Tabla Cat_TiposDocumento creada.';
END
ELSE
    PRINT 'Cat_TiposDocumento ya existe — omitida.';
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('Cat_TiposDocumento') AND name = 'tipo_archivo_permitido'
)
BEGIN
    ALTER TABLE Cat_TiposDocumento ADD tipo_archivo_permitido NVARCHAR(20) NOT NULL CONSTRAINT DF_CatTipoDoc_tipo_archivo DEFAULT 'pdf';
    PRINT 'Columna tipo_archivo_permitido agregada a Cat_TiposDocumento.';
END
ELSE
    PRINT 'Columna tipo_archivo_permitido ya existe — omitida.';
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('Cat_TiposDocumento') AND name = 'validar_tipo_archivo'
)
BEGIN
    ALTER TABLE Cat_TiposDocumento ADD validar_tipo_archivo BIT NOT NULL CONSTRAINT DF_CatTipoDoc_validar_tipo DEFAULT 1;
    PRINT 'Columna validar_tipo_archivo agregada a Cat_TiposDocumento.';
END
ELSE
    PRINT 'Columna validar_tipo_archivo ya existe — omitida.';
GO

-- ============================================================
-- 4. Apoyos — columnas faltantes (foto_ruta, descripcion)
-- ============================================================
IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('Apoyos') AND name = 'foto_ruta'
)
BEGIN
    ALTER TABLE Apoyos ADD foto_ruta NVARCHAR(500) NULL;
    PRINT 'Columna foto_ruta agregada a Apoyos.';
END
ELSE
    PRINT 'Columna foto_ruta ya existe — omitida.';
GO

IF NOT EXISTS (
    SELECT 1 FROM sys.columns
    WHERE object_id = OBJECT_ID('Apoyos') AND name = 'descripcion'
)
BEGIN
    ALTER TABLE Apoyos ADD descripcion NVARCHAR(MAX) NULL;
    PRINT 'Columna descripcion agregada a Apoyos.';
END
ELSE
    PRINT 'Columna descripcion ya existe — omitida.';
GO

-- ============================================================
-- 5. Requisitos_Apoyo
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Requisitos_Apoyo')
BEGIN
    CREATE TABLE Requisitos_Apoyo (
        fk_id_apoyo    INT NOT NULL,
        fk_id_tipo_doc INT NOT NULL,
        es_obligatorio BIT DEFAULT 1,
        CONSTRAINT PK_Requisitos_Apoyo PRIMARY KEY (fk_id_apoyo, fk_id_tipo_doc),
        CONSTRAINT FK_Requisitos_Apoyo_Apoyo
            FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo) ON DELETE CASCADE,
        CONSTRAINT FK_Requisitos_Apoyo_TipoDoc
            FOREIGN KEY (fk_id_tipo_doc) REFERENCES Cat_TiposDocumento(id_tipo_doc) ON DELETE CASCADE
    );

    PRINT 'Tabla Requisitos_Apoyo creada.';
END
ELSE
    PRINT 'Requisitos_Apoyo ya existe — omitida.';
GO

-- ============================================================
-- 6. Solicitudes
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Solicitudes')
BEGIN
    CREATE TABLE Solicitudes (
        folio                  INT IDENTITY(1000,1) PRIMARY KEY,
        fk_curp                CHAR(18) NOT NULL,
        fk_id_apoyo            INT NOT NULL,
        fk_id_estado           INT NOT NULL DEFAULT 1,
        fk_id_prioridad        INT NULL,
        fecha_creacion         DATETIME2 DEFAULT GETDATE(),
        fecha_actualizacion    DATETIME2 DEFAULT GETDATE(),
        observaciones_internas NVARCHAR(MAX) NULL,
        CONSTRAINT FK_Solicitudes_Beneficiario
            FOREIGN KEY (fk_curp) REFERENCES Beneficiarios(curp) ON DELETE CASCADE,
        CONSTRAINT FK_Solicitudes_Apoyo
            FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo),
        CONSTRAINT FK_Solicitudes_Estado
            FOREIGN KEY (fk_id_estado) REFERENCES Cat_EstadosSolicitud(id_estado),
        CONSTRAINT FK_Solicitudes_Prioridad
            FOREIGN KEY (fk_id_prioridad) REFERENCES Cat_Prioridades(id_prioridad)
    );

    PRINT 'Tabla Solicitudes creada.';
END
ELSE
    PRINT 'Solicitudes ya existe — omitida.';
GO

-- ============================================================
-- 7. Documentos_Expediente
-- ============================================================
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Documentos_Expediente')
BEGIN
    CREATE TABLE Documentos_Expediente (
        id_documento      INT IDENTITY(1,1) PRIMARY KEY,
        fk_folio          INT NOT NULL,
        fk_id_tipo_doc    INT NOT NULL,
        ruta_archivo      NVARCHAR(500) NOT NULL,
        estado_validacion NVARCHAR(20) NOT NULL DEFAULT 'Pendiente',
        version           SMALLINT NOT NULL DEFAULT 1,
        fecha_carga       DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_Documentos_Solicitud
            FOREIGN KEY (fk_folio) REFERENCES Solicitudes(folio) ON DELETE CASCADE,
        CONSTRAINT FK_Documentos_TipoDoc
            FOREIGN KEY (fk_id_tipo_doc) REFERENCES Cat_TiposDocumento(id_tipo_doc)
    );

    PRINT 'Tabla Documentos_Expediente creada.';
END
ELSE
    PRINT 'Documentos_Expediente ya existe — omitida.';
GO

PRINT 'Script co