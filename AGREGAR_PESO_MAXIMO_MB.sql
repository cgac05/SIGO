-- SQL Server Administrator: Execute this command to add the peso_maximo_mb column
-- This allows documents to have weight limits (default 5 MB, max 500 MB)

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Cat_TiposDocumento' 
              AND COLUMN_NAME = 'peso_maximo_mb')
BEGIN
    ALTER TABLE Cat_TiposDocumento 
    ADD peso_maximo_mb INT DEFAULT 5 NULL;
    
    PRINT 'Successfully added peso_maximo_mb column to Cat_TiposDocumento';
END
ELSE
BEGIN
    PRINT 'Column peso_maximo_mb already exists';
END

-- Update existing documents to have default 5 MB weight limit
UPDATE Cat_TiposDocumento 
SET peso_maximo_mb = 5 
WHERE peso_maximo_mb IS NULL;

PRINT 'Complete! All documents now have weight limits.';

-- Verify
SELECT id_tipo_doc, nombre_documento, peso_maximo_mb 
FROM Cat_TiposDocumento 
ORDER BY nombre_documento;
