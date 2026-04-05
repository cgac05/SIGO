-- SQL para crear las tablas de Fase 9 Partes 3 y 4
-- Compatible con SQL Server 2019
-- Ejecutar en contexto de BD_SIGO

USE BD_SIGO;
GO

-- =====================================================================
-- TABLA: auditoria_verificacion (FASE 9 PARTE 3)
-- =====================================================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'auditoria_verificacion')
BEGIN
    CREATE TABLE auditoria_verificacion (
        id_auditoria BIGINT PRIMARY KEY IDENTITY(1,1),
        id_historico BIGINT NOT NULL,
        tipo_verificacion NVARCHAR(100) NOT NULL,
        detalles NVARCHAR(MAX) NULL,
        ip_terminal NVARCHAR(45) NULL,
        id_usuario_validador BIGINT NOT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    
    -- Foreign keys
    ALTER TABLE auditoria_verificacion ADD 
        CONSTRAINT FK_auditoria_verificacion_historico FOREIGN KEY (id_historico) 
        REFERENCES Historico_Cierre(id_historico) ON DELETE CASCADE;
    
    ALTER TABLE auditoria_verificacion ADD
        CONSTRAINT FK_auditoria_verificacion_usuario FOREIGN KEY (id_usuario_validador) 
        REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION;
    
    -- Indices
    CREATE INDEX IX_auditoria_verificacion_id_historico ON auditoria_verificacion(id_historico);
    CREATE INDEX IX_auditoria_verificacion_tipo_verificacion ON auditoria_verificacion(tipo_verificacion);
    CREATE INDEX IX_auditoria_verificacion_id_usuario ON auditoria_verificacion(id_usuario_validador);
    CREATE INDEX IX_auditoria_verificacion_created_at ON auditoria_verificacion(created_at);
    
    PRINT 'Tabla auditoria_verificacion creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La tabla auditoria_verificacion ya existe.';
END
GO

-- =====================================================================
-- TABLA: archivo_certificado (FASE 9 PARTE 4)
-- =====================================================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'archivo_certificado')
BEGIN
    CREATE TABLE archivo_certificado (
        id_archivo BIGINT PRIMARY KEY IDENTITY(1,1),
        id_historico BIGINT NOT NULL,
        uuid_archivo NVARCHAR(36) NOT NULL UNIQUE,
        nombre_archivo NVARCHAR(255) NOT NULL,
        ruta_almacenamiento NVARCHAR(MAX) NOT NULL,
        tamanio_bytes BIGINT NOT NULL,
        hash_integridad NVARCHAR(64) NOT NULL,
        tipo_compresion NVARCHAR(50) DEFAULT 'zip' NOT NULL,
        motivo_archivado NVARCHAR(MAX) NULL,
        activo BIT DEFAULT 1 NOT NULL,
        id_usuario_archivador BIGINT NOT NULL,
        fecha_eliminacion DATETIME NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    
    -- Foreign keys
    ALTER TABLE archivo_certificado ADD
        CONSTRAINT FK_archivo_certificado_historico FOREIGN KEY (id_historico) 
        REFERENCES Historico_Cierre(id_historico) ON DELETE CASCADE;
    
    ALTER TABLE archivo_certificado ADD
        CONSTRAINT FK_archivo_certificado_usuario FOREIGN KEY (id_usuario_archivador) 
        REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION;
    
    -- Indices
    CREATE INDEX IX_archivo_certificado_id_historico ON archivo_certificado(id_historico);
    CREATE INDEX IX_archivo_certificado_activo ON archivo_certificado(activo);
    CREATE INDEX IX_archivo_certificado_uuid ON archivo_certificado(uuid_archivo);
    CREATE INDEX IX_archivo_certificado_created_at ON archivo_certificado(created_at);
    
    PRINT 'Tabla archivo_certificado creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La tabla archivo_certificado ya existe.';
END
GO

-- =====================================================================
-- TABLA: version_certificado (FASE 9 PARTE 4)
-- =====================================================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'version_certificado')
BEGIN
    CREATE TABLE version_certificado (
        id_version BIGINT PRIMARY KEY IDENTITY(1,1),
        id_historico BIGINT NOT NULL,
        numero_version INT DEFAULT 1 NOT NULL,
        tipo_cambio NVARCHAR(100) NOT NULL,
        datos_version NVARCHAR(MAX) NULL,
        descripcion NVARCHAR(MAX) NULL,
        id_usuario BIGINT NOT NULL,
        ip_terminal NVARCHAR(45) NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL
    );
    
    -- Foreign keys
    ALTER TABLE version_certificado ADD
        CONSTRAINT FK_version_certificado_historico FOREIGN KEY (id_historico) 
        REFERENCES Historico_Cierre(id_historico) ON DELETE CASCADE;
    
    ALTER TABLE version_certificado ADD
        CONSTRAINT FK_version_certificado_usuario FOREIGN KEY (id_usuario) 
        REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION;
    
    -- Indices
    CREATE INDEX IX_version_certificado_id_historico ON version_certificado(id_historico);
    CREATE INDEX IX_version_certificado_numero_version ON version_certificado(numero_version);
    CREATE INDEX IX_version_certificado_tipo_cambio ON version_certificado(tipo_cambio);
    CREATE INDEX IX_version_certificado_id_usuario ON version_certificado(id_usuario);
    CREATE INDEX IX_version_certificado_created_at ON version_certificado(created_at);
    
    PRINT 'Tabla version_certificado creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La tabla version_certificado ya existe.';
END
GO

-- =====================================================================
-- RESUMEN
-- =====================================================================
PRINT '---';
PRINT 'Migraciones completadas para FASE 9 PARTES 3 y 4';
PRINT '---';
PRINT 'Verificando tablas creadas:';
GO

USE BD_SIGO;
SELECT name FROM sys.tables 
WHERE name IN ('auditoria_verificacion', 'archivo_certificado', 'version_certificado')
ORDER BY name;
GO

PRINT '';
PRINT 'Setup completado. Las migraciones de Laravel pueden ahora ser marcadas como ejecutadas.';
