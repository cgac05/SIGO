-- Corregir tipos de datos y agregar foreign keys
-- Compatible con SQL Server 2019

USE BD_SIGO;
GO

-- =====================================================================
-- CORREGIR TIPO DE DATO en auditoria_verificacion
-- =====================================================================
IF EXISTS (SELECT * FROM sys.tables WHERE name = 'auditoria_verificacion')
BEGIN
    -- Si hay constraints, eliminarlos primero
    IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_auditoria_verificacion_historico')
        ALTER TABLE auditoria_verificacion DROP CONSTRAINT FK_auditoria_verificacion_historico;
    
    IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_auditoria_verificacion_usuario')
        ALTER TABLE auditoria_verificacion DROP CONSTRAINT FK_auditoria_verificacion_usuario;
    
    -- Modificar la columna id_historico de BIGINT a INT
    BEGIN TRY
        ALTER TABLE auditoria_verificacion ALTER COLUMN id_historico INT NOT NULL;
        ALTER TABLE auditoria_verificacion ALTER COLUMN id_usuario_validador INT;
        
        PRINT 'Tipos de dato corregidos en auditoria_verificacion';
    END TRY
    BEGIN CATCH
        PRINT 'Error al modificar tipos de dato: ' + ERROR_MESSAGE();
    END CATCH
    
    -- Agregar foreign keys corregidas
    BEGIN TRY
        ALTER TABLE auditoria_verificacion ADD 
            CONSTRAINT FK_auditoria_verificacion_historico FOREIGN KEY (id_historico) 
            REFERENCES Historico_Cierre(id_historico) ON DELETE CASCADE;
        
        ALTER TABLE auditoria_verificacion ADD
            CONSTRAINT FK_auditoria_verificacion_usuario FOREIGN KEY (id_usuario_validador) 
            REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION;
        
        PRINT 'Foreign keys agregadas a auditoria_verificacion';
    END TRY
    BEGIN CATCH
        PRINT 'Error al agregar foreign keys: ' + ERROR_MESSAGE();
    END CATCH
END
GO

-- =====================================================================
-- CORREGIR TIPO DE DATO en archivo_certificado
-- =====================================================================
IF EXISTS (SELECT * FROM sys.tables WHERE name = 'archivo_certificado')
BEGIN
    -- Si hay constraints, eliminarlos primero
    IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_archivo_certificado_historico')
        ALTER TABLE archivo_certificado DROP CONSTRAINT FK_archivo_certificado_historico;
    
    IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_archivo_certificado_usuario')
        ALTER TABLE archivo_certificado DROP CONSTRAINT FK_archivo_certificado_usuario;
    
    -- Modificar las columnas
    BEGIN TRY
        ALTER TABLE archivo_certificado ALTER COLUMN id_historico INT NOT NULL;
        ALTER TABLE archivo_certificado ALTER COLUMN id_usuario_archivador INT NOT NULL;
        
        PRINT 'Tipos de dato corregidos en archivo_certificado';
    END TRY
    BEGIN CATCH
        PRINT 'Error al modificar tipos de dato: ' + ERROR_MESSAGE();
    END CATCH
    
    -- Agregar foreign keys
    BEGIN TRY
        ALTER TABLE archivo_certificado ADD
            CONSTRAINT FK_archivo_certificado_historico FOREIGN KEY (id_historico) 
            REFERENCES Historico_Cierre(id_historico) ON DELETE CASCADE;
        
        ALTER TABLE archivo_certificado ADD
            CONSTRAINT FK_archivo_certificado_usuario FOREIGN KEY (id_usuario_archivador) 
            REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION;
        
        PRINT 'Foreign keys agregadas a archivo_certificado';
    END TRY
    BEGIN CATCH
        PRINT 'Error al agregar foreign keys: ' + ERROR_MESSAGE();
    END CATCH
END
GO

-- =====================================================================
-- CORREGIR TIPO DE DATO en version_certificado
-- =====================================================================
IF EXISTS (SELECT * FROM sys.tables WHERE name = 'version_certificado')
BEGIN
    -- Si hay constraints, eliminarlos primero
    IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_version_certificado_historico')
        ALTER TABLE version_certificado DROP CONSTRAINT FK_version_certificado_historico;
    
    IF EXISTS (SELECT * FROM sys.foreign_keys WHERE name = 'FK_version_certificado_usuario')
        ALTER TABLE version_certificado DROP CONSTRAINT FK_version_certificado_usuario;
    
    -- Modificar las columnas
    BEGIN TRY
        ALTER TABLE version_certificado ALTER COLUMN id_historico INT NOT NULL;
        ALTER TABLE version_certificado ALTER COLUMN id_usuario INT NOT NULL;
        
        PRINT 'Tipos de dato corregidos en version_certificado';
    END TRY
    BEGIN CATCH
        PRINT 'Error al modificar tipos de dato: ' + ERROR_MESSAGE();
    END CATCH
    
    -- Agregar foreign keys
    BEGIN TRY
        ALTER TABLE version_certificado ADD
            CONSTRAINT FK_version_certificado_historico FOREIGN KEY (id_historico) 
            REFERENCES Historico_Cierre(id_historico) ON DELETE CASCADE;
        
        ALTER TABLE version_certificado ADD
            CONSTRAINT FK_version_certificado_usuario FOREIGN KEY (id_usuario) 
            REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION;
        
        PRINT 'Foreign keys agregadas a version_certificado';
    END TRY
    BEGIN CATCH
        PRINT 'Error al agregar foreign keys: ' + ERROR_MESSAGE();
    END CATCH
END
GO

-- =====================================================================
-- VERIFICACION FINAL
-- =====================================================================
PRINT '';
PRINT '=== VERIFICACION DE TABLAS CREADAS ===';
SELECT 
    t.name AS 'Tabla',
    COUNT(*) AS 'Columnas',
    (SELECT COUNT(*) FROM sys.foreign_keys WHERE parent_object_id = t.object_id) AS 'Foreign Keys'
FROM sys.tables t
WHERE t.name IN ('auditoria_verificacion', 'archivo_certificado', 'version_certificado')
GROUP BY t.name
ORDER BY t.name;

PRINT '';
PRINT '=== FOREIGN KEYS ===';
SELECT 
    f.name AS 'Nombre FK',
    OBJECT_NAME(f.parent_object_id) AS 'Tabla Origen',
    (SELECT name FROM sys.columns WHERE object_id = f.parent_object_id AND column_id = f.parent_column_id) AS 'Columna Origen',
    OBJECT_NAME(f.referenced_object_id) AS 'Tabla Referencia'
FROM sys.foreign_keys f
WHERE OBJECT_NAME(f.parent_object_id) IN ('auditoria_verificacion', 'archivo_certificado', 'version_certificado')
ORDER BY f.name;

PRINT '';
PRINT '=== ✅ SETUP COMPLETADO ===';
PRINT 'Todas las tablas han sido creadas y configuradas correctamente.';
PRINT 'Ahora puede ejecutar: php artisan migrate:reset';
