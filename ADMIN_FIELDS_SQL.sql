-- Agregar campos de verificación administrativa a DOCUMENTOS_EXPEDIENTE

-- 1. Campo de estado de verificación
ALTER TABLE DOCUMENTOS_EXPEDIENTE
ADD admin_status NVARCHAR(40) NULL DEFAULT 'pendiente';

-- 2. Campo de observaciones del administrador
ALTER TABLE DOCUMENTOS_EXPEDIENTE
ADD admin_observations NVARCHAR(MAX) NULL;

-- 3. Campo de token de verificación (único)
ALTER TABLE DOCUMENTOS_EXPEDIENTE
ADD verification_token NVARCHAR(255) NULL;

-- Crear índice único para el token
CREATE UNIQUE INDEX IX_verification_token 
ON DOCUMENTOS_EXPEDIENTE(verification_token) 
WHERE verification_token IS NOT NULL;

-- 4. Campo de usuario administrador que verifica
ALTER TABLE DOCUMENTOS_EXPEDIENTE
ADD id_admin INT NULL;

-- Agregar constraint de llave foránea
ALTER TABLE DOCUMENTOS_EXPEDIENTE
ADD CONSTRAINT FK_DOCUMENTOS_ID_ADMIN
FOREIGN KEY (id_admin) REFERENCES Usuarios(id_usuario);

-- 5. Fecha de verificación
ALTER TABLE DOCUMENTOS_EXPEDIENTE
ADD fecha_verificacion DATETIME2 NULL;

-- Validar que todos los campos se agregaron exitosamente
SELECT 
    COLUMN_NAME, 
    DATA_TYPE, 
    IS_NULLABLE, 
    COLUMN_DEFAULT
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'DOCUMENTOS_EXPEDIENTE' 
AND COLUMN_NAME IN ('admin_status', 'admin_observations', 'verification_token', 'id_admin', 'fecha_verificacion')
ORDER BY ORDINAL_POSITION DESC;
