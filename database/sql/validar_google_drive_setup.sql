-- Script de validación para verificar la integridad de las tablas de Google Drive
-- Ejecutar después de crear las tablas

-- 1. Verificar que la tabla google_drive_files existe y tiene la estructura correcta
PRINT '=== VALIDACIÓN TABLA google_drive_files ===';

IF OBJECT_ID('google_drive_files', 'U') IS NOT NULL
BEGIN
    PRINT '✓ Tabla google_drive_files existe';
    
    -- Verificar columnas
    PRINT '';
    PRINT 'Columnas:';
    SELECT 
        COLUMN_NAME, 
        DATA_TYPE, 
        IS_NULLABLE,
        COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'google_drive_files'
    ORDER BY ORDINAL_POSITION;
    
    -- Verificar constraints
    PRINT '';
    PRINT 'Constraints:';
    SELECT 
        CONSTRAINT_NAME,
        CONSTRAINT_TYPE
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
    WHERE TABLE_NAME = 'google_drive_files';
    
    -- Verificar índices
    PRINT '';
    PRINT 'Índices:';
    SELECT 
        INDEX_NAME,
        COLUMN_NAME
    FROM INFORMATION_SCHEMA.STATISTICS
    WHERE TABLE_NAME = 'google_drive_files'
    ORDER BY INDEX_NAME;
END
ELSE
BEGIN
    PRINT '✗ Tabla google_drive_files NO existe - revisar script de creación';
END

-- 2. Verificar que la tabla google_drive_audit_logs existe
PRINT '';
PRINT '=== VALIDACIÓN TABLA google_drive_audit_logs ===';

IF OBJECT_ID('google_drive_audit_logs', 'U') IS NOT NULL
BEGIN
    PRINT '✓ Tabla google_drive_audit_logs existe';
    
    -- Verificar columnas
    PRINT '';
    PRINT 'Columnas:';
    SELECT 
        COLUMN_NAME, 
        DATA_TYPE, 
        IS_NULLABLE,
        COLUMN_DEFAULT
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'google_drive_audit_logs'
    ORDER BY ORDINAL_POSITION;
END
ELSE
BEGIN
    PRINT '✗ Tabla google_drive_audit_logs NO existe';
END

-- 3. Verificar que la columna google_token_expires_at existe en Usuarios
PRINT '';
PRINT '=== VALIDACIÓN TABLA Usuarios ===';

IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'google_token_expires_at')
BEGIN
    PRINT '✓ Columna google_token_expires_at existe en tabla Usuarios';
END
ELSE
BEGIN
    PRINT '✗ Columna google_token_expires_at NO existe - agregando...';
    IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'google_token_expires_at')
    BEGIN
        ALTER TABLE Usuarios ADD google_token_expires_at DATETIME2 NULL;
        PRINT '✓ Columna google_token_expires_at agregada exitosamente';
    END
END

-- 4. Verificar que existan columnas de Google en Usuarios
PRINT '';
PRINT 'Verificando columnas de Google en Usuarios:';
IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'google_id')
    PRINT '✓ google_id';
ELSE
    PRINT '✗ google_id (FALTA)';

IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'google_token')
    PRINT '✓ google_token';
ELSE
    PRINT '✗ google_token (FALTA)';

IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'google_refresh_token')
    PRINT '✓ google_refresh_token';
ELSE
    PRINT '✗ google_refresh_token (FALTA)';

IF EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'google_token_expires_at')
    PRINT '✓ google_token_expires_at';
ELSE
    PRINT '✗ google_token_expires_at (FALTA)';

-- 5. Verificar integridad referencial
PRINT '';
PRINT '=== INTEGRIDAD REFERENCIAL ===';
PRINT 'Verificando constraints...';

SELECT 
    CONSTRAINT_NAME,
    TABLE_NAME,
    REFERENCED_TABLE_NAME
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_NAME LIKE '%google%';

-- 6. Resumen
PRINT '';
PRINT '=== RESUMEN DE VALIDACIÓN ===';
PRINT 'Estructura de base de datos para Google Drive API validada.';
PRINT 'Si todos los checks mostraron ✓, la implementación está lista.';
