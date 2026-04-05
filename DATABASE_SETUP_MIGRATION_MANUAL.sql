-- SQL para crear las tablas de Fase 9 Partes 3 y 4
-- Ejecutar como usuario con permisos db_owner en BD_SIGO

-- =====================================================================
-- TABLA: auditoria_verificacion (FASE 9 PARTE 3)
-- =====================================================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'auditoria_verificacion')
BEGIN
    CREATE TABLE dbo.auditoria_verificacion (
        id_auditoria BIGINT PRIMARY KEY IDENTITY(1,1),
        id_historico BIGINT NOT NULL,
        tipo_verificacion NVARCHAR(100) NOT NULL,
        detalles NVARCHAR(MAX) NULL,
        ip_terminal NVARCHAR(45) NULL,
        id_usuario_validador BIGINT NOT NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        CONSTRAINT FK_auditoria_verificacion_historico FOREIGN KEY (id_historico) 
            REFERENCES dbo.Historico_Cierre(id_historico) ON DELETE CASCADE,
        CONSTRAINT FK_auditoria_verificacion_usuario FOREIGN KEY (id_usuario_validador) 
            REFERENCES dbo.usuarios(id_usuario) ON DELETE NO ACTION
    );
    
    -- Índices para auditoria_verificacion
    CREATE INDEX IX_auditoria_verificacion_id_historico ON dbo.auditoria_verificacion(id_historico);
    CREATE INDEX IX_auditoria_verificacion_tipo_verificacion ON dbo.auditoria_verificacion(tipo_verificacion);
    CREATE INDEX IX_auditoria_verificacion_id_usuario ON dbo.auditoria_verificacion(id_usuario_validador);
    CREATE INDEX IX_auditoria_verificacion_created_at ON dbo.auditoria_verificacion(created_at);
    
    PRINT 'Tabla auditoria_verificacion creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La tabla auditoria_verificacion ya existe.';
END

-- =====================================================================
-- TABLA: archivo_certificado (FASE 9 PARTE 4)
-- =====================================================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'archivo_certificado')
BEGIN
    CREATE TABLE dbo.archivo_certificado (
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
        updated_at DATETIME NULL,
        CONSTRAINT FK_archivo_certificado_historico FOREIGN KEY (id_historico) 
            REFERENCES dbo.Historico_Cierre(id_historico) ON DELETE CASCADE,
        CONSTRAINT FK_archivo_certificado_usuario FOREIGN KEY (id_usuario_archivador) 
            REFERENCES dbo.usuarios(id_usuario) ON DELETE NO ACTION
    );
    
    -- Índices para archivo_certificado
    CREATE INDEX IX_archivo_certificado_id_historico ON dbo.archivo_certificado(id_historico);
    CREATE INDEX IX_archivo_certificado_activo ON dbo.archivo_certificado(activo);
    CREATE INDEX IX_archivo_certificado_uuid ON dbo.archivo_certificado(uuid_archivo);
    CREATE INDEX IX_archivo_certificado_created_at ON dbo.archivo_certificado(created_at);
    
    PRINT 'Tabla archivo_certificado creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La tabla archivo_certificado ya existe.';
END

-- =====================================================================
-- TABLA: version_certificado (FASE 9 PARTE 4)
-- =====================================================================
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'version_certificado')
BEGIN
    CREATE TABLE dbo.version_certificado (
        id_version BIGINT PRIMARY KEY IDENTITY(1,1),
        id_historico BIGINT NOT NULL,
        numero_version INT DEFAULT 1 NOT NULL,
        tipo_cambio NVARCHAR(100) NOT NULL,
        datos_version NVARCHAR(MAX) NULL,
        descripcion NVARCHAR(MAX) NULL,
        id_usuario BIGINT NOT NULL,
        ip_terminal NVARCHAR(45) NULL,
        created_at DATETIME NULL,
        updated_at DATETIME NULL,
        CONSTRAINT FK_version_certificado_historico FOREIGN KEY (id_historico) 
            REFERENCES dbo.Historico_Cierre(id_historico) ON DELETE CASCADE,
        CONSTRAINT FK_version_certificado_usuario FOREIGN KEY (id_usuario) 
            REFERENCES dbo.usuarios(id_usuario) ON DELETE NO ACTION
    );
    
    -- Índices para version_certificado
    CREATE INDEX IX_version_certificado_id_historico ON dbo.version_certificado(id_historico);
    CREATE INDEX IX_version_certificado_numero_version ON dbo.version_certificado(numero_version);
    CREATE INDEX IX_version_certificado_tipo_cambio ON dbo.version_certificado(tipo_cambio);
    CREATE INDEX IX_version_certificado_id_usuario ON dbo.version_certificado(id_usuario);
    CREATE INDEX IX_version_certificado_created_at ON dbo.version_certificado(created_at);
    
    PRINT 'Tabla version_certificado creada exitosamente.';
END
ELSE
BEGIN
    PRINT 'La tabla version_certificado ya existe.';
END

-- =====================================================================
-- Resumen de creación
-- =====================================================================
PRINT '---';
PRINT 'Migraciones completadas para FASE 9 PARTES 3 y 4';
PRINT '---';
PRINT 'Tablas creadas:';
PRINT '- auditoria_verificacion (PARTE 3)';
PRINT '- archivo_certificado (PARTE 4)';
PRINT '- version_certificado (PARTE 4)';
PRINT '---';
PRINT 'Después de ejecutar este script, puede ejecutar: php artisan migrate --database=sqlsrv';
