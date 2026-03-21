-- Ejecutar con usuario con permisos CREATE TABLE sobre BD_SIGO
-- Tabla de hitos configurables por apoyo (base + adicionales)

IF OBJECT_ID('dbo.Hitos_Apoyo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Hitos_Apoyo (
        id_hito INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_apoyo INT NOT NULL,
        slug_hito NVARCHAR(80) NULL,
        titulo_hito NVARCHAR(150) NOT NULL,
        fecha_inicio DATE NULL,
        fecha_fin DATE NULL,
        orden SMALLINT NOT NULL DEFAULT 0,
        es_base BIT NOT NULL DEFAULT 0,
        activo BIT NOT NULL DEFAULT 1,
        fecha_creacion DATETIME NOT NULL DEFAULT GETDATE(),
        fecha_actualizacion DATETIME NULL
    );

    CREATE INDEX IX_Hitos_Apoyo_Apoyo ON dbo.Hitos_Apoyo(fk_id_apoyo);
    CREATE INDEX IX_Hitos_Apoyo_Apoyo_Orden ON dbo.Hitos_Apoyo(fk_id_apoyo, orden);

    ALTER TABLE dbo.Hitos_Apoyo
        ADD CONSTRAINT FK_Hitos_Apoyo_Apoyos
        FOREIGN KEY (fk_id_apoyo) REFERENCES dbo.Apoyos(id_apoyo) ON DELETE CASCADE;
END
GO
