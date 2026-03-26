-- Script SQL para crear la tabla google_drive_files en SQL Server
-- Ejecutar este script si la migración de Laravel no funciona por permisos

-- Eliminar tablas si existen (DROP IF EXISTS para SQL Server 2016+)
IF OBJECT_ID('google_drive_audit_logs', 'U') IS NOT NULL
BEGIN
    DROP TABLE google_drive_audit_logs;
END

IF OBJECT_ID('google_drive_files', 'U') IS NOT NULL
BEGIN
    DROP TABLE google_drive_files;
END

CREATE TABLE google_drive_files (
    id INT PRIMARY KEY IDENTITY(1,1),
    user_id INT NOT NULL,
    google_file_id NVARCHAR(255) NOT NULL UNIQUE,
    file_name NVARCHAR(255) NOT NULL,
    file_size BIGINT NOT NULL,
    mime_type NVARCHAR(255) NOT NULL,
    storage_path NVARCHAR(MAX) NOT NULL,
    created_at DATETIME2 DEFAULT GETDATE(),
    updated_at DATETIME2 DEFAULT GETDATE(),
    
    -- Índices
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at),
    INDEX idx_google_file_id (google_file_id),
    
    -- Foreign Key
    CONSTRAINT fk_google_drive_files_usuarios 
        FOREIGN KEY (user_id) 
        REFERENCES Usuarios(id_usuario) 
        ON DELETE CASCADE
);

-- Crear columnas adicionales en la tabla Usuarios si no existen
-- Nota: estas columnas ya pueden existir de migraciones previas
IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Usuarios' AND COLUMN_NAME = 'google_token_expires_at')
BEGIN
    ALTER TABLE Usuarios ADD google_token_expires_at DATETIME2 NULL;
END
GO

-- Crear tabla para logs de Google Drive
CREATE TABLE google_drive_audit_logs (
    id INT PRIMARY KEY IDENTITY(1,1),
    user_id INT NOT NULL,
    google_drive_file_id INT NULL,
    action NVARCHAR(50) NOT NULL,
    ip_address NVARCHAR(45) NULL,
    user_agent NVARCHAR(MAX) NULL,
    created_at DATETIME2 DEFAULT GETDATE(),
    
    CONSTRAINT fk_google_audit_usuarios 
        FOREIGN KEY (user_id) 
        REFERENCES Usuarios(id_usuario) 
        ON DELETE NO ACTION,
    
    CONSTRAINT fk_google_audit_files 
        FOREIGN KEY (google_drive_file_id) 
        REFERENCES google_drive_files(id) 
        ON DELETE SET NULL
);

-- Crear índice en audit_logs
CREATE INDEX idx_audit_user_action ON google_drive_audit_logs(user_id, action);
CREATE INDEX idx_audit_created_at ON google_drive_audit_logs(created_at);
