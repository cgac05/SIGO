/*
 Script manual para SQL Server: Proceso de Cierre y Validacion SIGO
 Ejecutar con un usuario que tenga permisos DDL en BD_SIGO.
*/

SET NOCOUNT ON;

/* 1) Hitos_Apoyo */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Hitos_Apoyo')
BEGIN
    CREATE TABLE Hitos_Apoyo (
        id_hito INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_apoyo INT NOT NULL,
        clave_hito NVARCHAR(30) NOT NULL,
        nombre_hito NVARCHAR(100) NOT NULL,
        orden_hito SMALLINT NOT NULL,
        fecha_inicio DATETIME2 NULL,
        fecha_fin DATETIME2 NULL,
        activo BIT NOT NULL DEFAULT 1,
        fecha_creacion DATETIME2 NOT NULL DEFAULT GETDATE(),
        fecha_actualizacion DATETIME2 NOT NULL DEFAULT GETDATE(),
        CONSTRAINT FK_HitosApoyo_Apoyo FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo) ON DELETE CASCADE,
        CONSTRAINT UQ_HitosApoyo_Clave UNIQUE (fk_id_apoyo, clave_hito),
        CONSTRAINT UQ_HitosApoyo_Orden UNIQUE (fk_id_apoyo, orden_hito)
    );
END

/* 2) Solicitudes - columnas nuevas */
IF COL_LENGTH('Solicitudes', 'folio_institucional') IS NULL
    ALTER TABLE Solicitudes ADD folio_institucional NVARCHAR(40) NULL;

IF COL_LENGTH('Solicitudes', 'permite_correcciones') IS NULL
    ALTER TABLE Solicitudes ADD permite_correcciones BIT NOT NULL CONSTRAINT DF_Solicitudes_permite_corr DEFAULT 1;

IF COL_LENGTH('Solicitudes', 'monto_entregado') IS NULL
    ALTER TABLE Solicitudes ADD monto_entregado DECIMAL(19,4) NULL;

IF COL_LENGTH('Solicitudes', 'fecha_entrega_recurso') IS NULL
    ALTER TABLE Solicitudes ADD fecha_entrega_recurso DATE NULL;

IF COL_LENGTH('Solicitudes', 'fecha_cierre_financiero') IS NULL
    ALTER TABLE Solicitudes ADD fecha_cierre_financiero DATETIME2 NULL;

IF COL_LENGTH('Solicitudes', 'cuv') IS NULL
    ALTER TABLE Solicitudes ADD cuv NVARCHAR(20) NULL;

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Solicitudes_folio_institucional')
    CREATE INDEX IX_Solicitudes_folio_institucional ON Solicitudes(folio_institucional);

IF NOT EXISTS (SELECT 1 FROM sys.indexes WHERE name = 'IX_Solicitudes_cuv')
    CREATE INDEX IX_Solicitudes_cuv ON Solicitudes(cuv);

/* 3) Documentos_Expediente - columnas nuevas */
IF COL_LENGTH('Documentos_Expediente', 'webview_link') IS NULL
    ALTER TABLE Documentos_Expediente ADD webview_link NVARCHAR(500) NULL;

IF COL_LENGTH('Documentos_Expediente', 'source_file_id') IS NULL
    ALTER TABLE Documentos_Expediente ADD source_file_id NVARCHAR(200) NULL;

IF COL_LENGTH('Documentos_Expediente', 'official_file_id') IS NULL
    ALTER TABLE Documentos_Expediente ADD official_file_id NVARCHAR(200) NULL;

IF COL_LENGTH('Documentos_Expediente', 'observaciones_revision') IS NULL
    ALTER TABLE Documentos_Expediente ADD observaciones_revision NVARCHAR(MAX) NULL;

IF COL_LENGTH('Documentos_Expediente', 'revisado_por') IS NULL
    ALTER TABLE Documentos_Expediente ADD revisado_por INT NULL;

IF COL_LENGTH('Documentos_Expediente', 'fecha_revision') IS NULL
    ALTER TABLE Documentos_Expediente ADD fecha_revision DATETIME2 NULL;

IF NOT EXISTS (SELECT 1 FROM sys.foreign_keys WHERE name = 'FK_DocExp_RevisadoPor_Usuarios')
    ALTER TABLE Documentos_Expediente
    ADD CONSTRAINT FK_DocExp_RevisadoPor_Usuarios FOREIGN KEY (revisado_por) REFERENCES Usuarios(id_usuario);

/* 4) Seguimiento_Solicitud */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Seguimiento_Solicitud')
BEGIN
    CREATE TABLE Seguimiento_Solicitud (
        id_seguimiento INT IDENTITY(1,1) PRIMARY KEY,
        fk_folio INT NOT NULL,
        fk_id_directivo INT NULL,
        sello_digital NVARCHAR(64) NULL,
        cuv NVARCHAR(20) NULL,
        estado_proceso NVARCHAR(30) NOT NULL DEFAULT 'EN_PROCESO',
        metadata_seguridad NVARCHAR(MAX) NULL,
        fecha_firma DATETIME2 NULL,
        fecha_cierre DATETIME2 NULL,
        fecha_creacion DATETIME2 NOT NULL DEFAULT GETDATE(),
        fecha_actualizacion DATETIME2 NOT NULL DEFAULT GETDATE(),
        CONSTRAINT FK_SegSol_Solicitud FOREIGN KEY (fk_folio) REFERENCES Solicitudes(folio) ON DELETE CASCADE,
        CONSTRAINT FK_SegSol_Directivo FOREIGN KEY (fk_id_directivo) REFERENCES Usuarios(id_usuario)
    );

    CREATE INDEX IX_SegSol_CUV ON Seguimiento_Solicitud(cuv);
    CREATE INDEX IX_SegSol_FolioEstado ON Seguimiento_Solicitud(fk_folio, estado_proceso);
END

/* 5) Notificaciones */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Notificaciones')
BEGIN
    CREATE TABLE Notificaciones (
        id_notificacion INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_usuario INT NOT NULL,
        mensaje NVARCHAR(MAX) NOT NULL,
        leido BIT NOT NULL DEFAULT 0,
        evento NVARCHAR(40) NULL,
        canal NVARCHAR(20) NOT NULL DEFAULT 'sistema',
        data NVARCHAR(MAX) NULL,
        fecha_creacion DATETIME2 NOT NULL DEFAULT GETDATE(),
        fecha_lectura DATETIME2 NULL,
        CONSTRAINT FK_Notif_Usuario FOREIGN KEY (fk_id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE
    );
END

/* 6) Historico_Cierre */
IF NOT EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Historico_Cierre')
BEGIN
    CREATE TABLE Historico_Cierre (
        id_historico INT IDENTITY(1,1) PRIMARY KEY,
        fk_folio INT NOT NULL,
        fk_id_usuario_cierre INT NULL,
        snapshot_json NVARCHAR(MAX) NOT NULL,
        monto_entregado DECIMAL(19,4) NULL,
        fecha_entrega DATE NULL,
        folio_institucional NVARCHAR(40) NULL,
        ruta_pdf_final NVARCHAR(500) NULL,
        fecha_creacion DATETIME2 NOT NULL DEFAULT GETDATE(),
        CONSTRAINT FK_Historico_Solicitud FOREIGN KEY (fk_folio) REFERENCES Solicitudes(folio) ON DELETE CASCADE,
        CONSTRAINT FK_Historico_UsuarioCierre FOREIGN KEY (fk_id_usuario_cierre) REFERENCES Usuarios(id_usuario)
    );

    CREATE INDEX IX_Historico_Folio ON Historico_Cierre(fk_folio);
END

/* 7) Hitos base por apoyo existente */
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Apoyos') AND EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Hitos_Apoyo')
BEGIN
    ;WITH HitosBase AS (
        SELECT 'PUBLICACION' AS clave_hito, 'Publicacion' AS nombre_hito, 1 AS orden_hito
        UNION ALL SELECT 'RECEPCION', 'Recepcion', 2
        UNION ALL SELECT 'ANALISIS_ADMIN', 'Analisis Administrativo', 3
        UNION ALL SELECT 'RESULTADOS', 'Resultados', 4
        UNION ALL SELECT 'CIERRE', 'Cierre', 5
    )
    INSERT INTO Hitos_Apoyo (fk_id_apoyo, clave_hito, nombre_hito, orden_hito, fecha_inicio, fecha_fin, activo, fecha_creacion, fecha_actualizacion)
    SELECT A.id_apoyo, H.clave_hito, H.nombre_hito, H.orden_hito, A.fecha_inicio, A.fecha_fin, 1, GETDATE(), GETDATE()
    FROM Apoyos A
    CROSS JOIN HitosBase H
    WHERE NOT EXISTS (
        SELECT 1
        FROM Hitos_Apoyo X
        WHERE X.fk_id_apoyo = A.id_apoyo
    );
END

/* 8) Seguimiento inicial por solicitud existente */
IF EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Solicitudes') AND EXISTS (SELECT 1 FROM sys.tables WHERE name = 'Seguimiento_Solicitud')
BEGIN
    INSERT INTO Seguimiento_Solicitud (fk_folio, estado_proceso, fecha_creacion, fecha_actualizacion)
    SELECT S.folio, 'EN_PROCESO', GETDATE(), GETDATE()
    FROM Solicitudes S
    WHERE NOT EXISTS (
        SELECT 1
        FROM Seguimiento_Solicitud X
        WHERE X.fk_folio = S.folio
    );
END

PRINT 'Script de workflow SIGO aplicado correctamente.';
