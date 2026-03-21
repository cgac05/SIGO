-- Ejecutar con un usuario que tenga permisos CREATE TABLE sobre BD_SIGO
-- Crea tablas de comentarios publicos por apoyo + reacciones like

IF OBJECT_ID('dbo.Comentarios_Apoyo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Comentarios_Apoyo (
        id_comentario INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_apoyo INT NOT NULL,
        fk_id_usuario INT NOT NULL,
        fk_id_comentario_padre INT NULL,
        contenido NVARCHAR(MAX) NOT NULL,
        editado BIT NOT NULL DEFAULT 0,
        fecha_creacion DATETIME NOT NULL DEFAULT GETDATE(),
        fecha_actualizacion DATETIME NULL
    );

    CREATE INDEX IX_Comentarios_Apoyo_Apoyo ON dbo.Comentarios_Apoyo(fk_id_apoyo);
    CREATE INDEX IX_Comentarios_Apoyo_Usuario ON dbo.Comentarios_Apoyo(fk_id_usuario);
    CREATE INDEX IX_Comentarios_Apoyo_Padre ON dbo.Comentarios_Apoyo(fk_id_comentario_padre);

    ALTER TABLE dbo.Comentarios_Apoyo
        ADD CONSTRAINT FK_Comentarios_Apoyo_Apoyos
        FOREIGN KEY (fk_id_apoyo) REFERENCES dbo.Apoyos(id_apoyo) ON DELETE CASCADE;

    ALTER TABLE dbo.Comentarios_Apoyo
        ADD CONSTRAINT FK_Comentarios_Apoyo_Usuarios
        FOREIGN KEY (fk_id_usuario) REFERENCES dbo.Usuarios(id_usuario) ON DELETE CASCADE;

    ALTER TABLE dbo.Comentarios_Apoyo
        ADD CONSTRAINT FK_Comentarios_Apoyo_Padre
        FOREIGN KEY (fk_id_comentario_padre) REFERENCES dbo.Comentarios_Apoyo(id_comentario) ON DELETE CASCADE;
END
GO

IF OBJECT_ID('dbo.Reacciones_ComentarioApoyo', 'U') IS NULL
BEGIN
    CREATE TABLE dbo.Reacciones_ComentarioApoyo (
        id_reaccion INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_comentario INT NOT NULL,
        fk_id_usuario INT NOT NULL,
        tipo_reaccion NVARCHAR(20) NOT NULL DEFAULT 'like',
        fecha_creacion DATETIME NOT NULL DEFAULT GETDATE()
    );

    CREATE UNIQUE INDEX UQ_Reaccion_Comentario_Usuario_Tipo
        ON dbo.Reacciones_ComentarioApoyo(fk_id_comentario, fk_id_usuario, tipo_reaccion);

    CREATE INDEX IX_Reaccion_Usuario ON dbo.Reacciones_ComentarioApoyo(fk_id_usuario);

    ALTER TABLE dbo.Reacciones_ComentarioApoyo
        ADD CONSTRAINT FK_Reaccion_Comentario
        FOREIGN KEY (fk_id_comentario) REFERENCES dbo.Comentarios_Apoyo(id_comentario) ON DELETE CASCADE;

    ALTER TABLE dbo.Reacciones_ComentarioApoyo
        ADD CONSTRAINT FK_Reaccion_Usuario
        FOREIGN KEY (fk_id_usuario) REFERENCES dbo.Usuarios(id_usuario) ON DELETE CASCADE;
END
GO
