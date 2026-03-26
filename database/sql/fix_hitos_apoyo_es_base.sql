-- ✅ Script FINAL para corregir estructura de Hitos_Apoyo
-- ⚠️ IMPORTANTE: Columnas NOT NULL necesitan DEFAULT para poder ser NULL en inserciones
-- Ejecución: SQL Server Management Studio en BD_SIGO

USE BD_SIGO;
GO

PRINT '🔧 Iniciando corrección FINAL de tabla Hitos_Apoyo...'
PRINT '';

-- =============================================================================
-- 1️⃣ ASEGURAR QUE es_base EXISTS
-- =============================================================================

IF NOT EXISTS (
    SELECT 1 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Hitos_Apoyo' AND COLUMN_NAME = 'es_base'
)
BEGIN
    ALTER TABLE Hitos_Apoyo
    ADD es_base BIT NOT NULL DEFAULT 0;
    PRINT '✓ Columna es_base agregada';
END
ELSE
BEGIN
    PRINT '✓ Columna es_base ya existe';
END

-- =============================================================================
-- 2️⃣ CAMBIAR COLUMNAS NOT NULL A NULLABLE O AGREGAR DEFAULT EMPTY
-- =============================================================================

-- clave_hito: Cambiar a NULL DEFAULT
DECLARE @DefaultName1 NVARCHAR(MAX) = (
    SELECT name FROM sys.default_constraints 
    WHERE parent_object_id = OBJECT_ID('Hitos_Apoyo') 
    AND parent_column_id = (
        SELECT column_id FROM sys.columns 
        WHERE object_id = OBJECT_ID('Hitos_Apoyo') 
        AND name = 'clave_hito'
    )
);

IF @DefaultName1 IS NOT NULL
BEGIN
    EXEC ('ALTER TABLE Hitos_Apoyo DROP CONSTRAINT ' + @DefaultName1);
END

-- Cambiar clave_hito a nullable
DECLARE @IsNullable1 BIT = (
    SELECT CASE WHEN IS_NULLABLE = 'YES' THEN 1 ELSE 0 END
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Hitos_Apoyo'
    AND COLUMN_NAME = 'clave_hito'
);

IF @IsNullable1 = 0
BEGIN
    ALTER TABLE Hitos_Apoyo ALTER COLUMN clave_hito NVARCHAR(80) NULL;
    PRINT '✓ clave_hito ahora permite NULL';
END

-- nombre_hito: Cambiar a NULL DEFAULT
DECLARE @DefaultName2 NVARCHAR(MAX) = (
    SELECT name FROM sys.default_constraints 
    WHERE parent_object_id = OBJECT_ID('Hitos_Apoyo') 
    AND parent_column_id = (
        SELECT column_id FROM sys.columns 
        WHERE object_id = OBJECT_ID('Hitos_Apoyo') 
        AND name = 'nombre_hito'
    )
);

IF @DefaultName2 IS NOT NULL
BEGIN
    EXEC ('ALTER TABLE Hitos_Apoyo DROP CONSTRAINT ' + @DefaultName2);
END

-- Cambiar nombre_hito a nullable
DECLARE @IsNullable2 BIT = (
    SELECT CASE WHEN IS_NULLABLE = 'YES' THEN 1 ELSE 0 END
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Hitos_Apoyo'
    AND COLUMN_NAME = 'nombre_hito'
);

IF @IsNullable2 = 0
BEGIN
    ALTER TABLE Hitos_Apoyo ALTER COLUMN nombre_hito NVARCHAR(150) NULL;
    PRINT '✓ nombre_hito ahora permite NULL';
END

-- orden_hito: Cambiar a DEFAULT 0
DECLARE @DefaultName3 NVARCHAR(MAX) = (
    SELECT name FROM sys.default_constraints 
    WHERE parent_object_id = OBJECT_ID('Hitos_Apoyo') 
    AND parent_column_id = (
        SELECT column_id FROM sys.columns 
        WHERE object_id = OBJECT_ID('Hitos_Apoyo') 
        AND name = 'orden_hito'
    )
);

IF @DefaultName3 IS NOT NULL
BEGIN
    EXEC ('ALTER TABLE Hitos_Apoyo DROP CONSTRAINT ' + @DefaultName3);
    ALTER TABLE Hitos_Apoyo ADD CONSTRAINT DF_orden_hito DEFAULT 0 FOR orden_hito;
    PRINT '✓ orden_hito ahora tiene DEFAULT 0';
END
ELSE
BEGIN
    ALTER TABLE Hitos_Apoyo ADD CONSTRAINT DF_orden_hito DEFAULT 0 FOR orden_hito;
    PRINT '✓ orden_hito DEFAULT 0 agregado';
END

-- =============================================================================
-- 3️⃣ ASEGURAR fecha_actualizacion PERMITE NULL
-- =============================================================================

DECLARE @DefaultName4 NVARCHAR(MAX) = (
    SELECT name FROM sys.default_constraints 
    WHERE parent_object_id = OBJECT_ID('Hitos_Apoyo') 
    AND parent_column_id = (
        SELECT column_id FROM sys.columns 
        WHERE object_id = OBJECT_ID('Hitos_Apoyo') 
        AND name = 'fecha_actualizacion'
    )
);

IF @DefaultName4 IS NOT NULL
BEGIN
    EXEC ('ALTER TABLE Hitos_Apoyo DROP CONSTRAINT ' + @DefaultName4);
END

DECLARE @IsNullable4 BIT = (
    SELECT CASE WHEN IS_NULLABLE = 'YES' THEN 1 ELSE 0 END
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_NAME = 'Hitos_Apoyo'
    AND COLUMN_NAME = 'fecha_actualizacion'
);

IF @IsNullable4 = 0
BEGIN
    ALTER TABLE Hitos_Apoyo ALTER COLUMN fecha_actualizacion DATETIME2 NULL;
    PRINT '✓ fecha_actualizacion ahora permite NULL';
END

-- =============================================================================
-- 📊 MOSTRAR ESTRUCTURA FINAL
-- =============================================================================

PRINT '';
PRINT '✅ Corrección completada. Estructura FINAL de Hitos_Apoyo:';
PRINT '';

SELECT 
    ORDINAL_POSITION as [#],
    COLUMN_NAME as [Columna],
    DATA_TYPE as [Tipo],
    IS_NULLABLE as [Null?],
    COLUMN_DEFAULT as [Default]
FROM INFORMATION_SCHEMA.COLUMNS 
WHERE TABLE_NAME = 'Hitos_Apoyo'
ORDER BY ORDINAL_POSITION;

GO


