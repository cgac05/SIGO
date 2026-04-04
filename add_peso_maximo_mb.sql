-- Add peso_maximo_mb column to Cat_TiposDocumento table
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Cat_TiposDocumento' AND COLUMN_NAME = 'peso_maximo_mb')
BEGIN
    ALTER TABLE Cat_TiposDocumento
    ADD peso_maximo_mb INT NULL DEFAULT 5;
    
    PRINT 'Column peso_maximo_mb added successfully';
END
ELSE
BEGIN
    PRINT 'Column peso_maximo_mb already exists';
END
