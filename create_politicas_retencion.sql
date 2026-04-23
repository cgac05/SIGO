-- Crear tabla de políticas de retención de documentos para Caso A
USE BD_SIGO;
GO

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'politicas_retencion_documentos')
BEGIN
    CREATE TABLE politicas_retencion_documentos (
        id_politica INT PRIMARY KEY IDENTITY(1,1),
        fk_id_documento INT NOT NULL,
        folio NVARCHAR(50),
        hito_cierre_apoyo NVARCHAR(100) NULL,
        fecha_cierre_apoyo DATETIME NULL,
        retencion_cumplida BIT DEFAULT 0,
        fecha_borrado DATETIME NULL,
        razon_borrado NVARCHAR(255) NULL,
        CONSTRAINT FK_politicas_documento FOREIGN KEY (fk_id_documento) REFERENCES Documentos_Expediente(id_doc)
    );
    PRINT 'Tabla politicas_retencion_documentos creada exitosamente';
END
ELSE
BEGIN
    PRINT 'Tabla politicas_retencion_documentos ya existe';
END
GO
