-- ============================================================================
-- SIGO - PRESUPUESTACIÓN SETUP
-- Script manual para crear tablas (ejecutar en SSMS como DBA)
-- ============================================================================
-- IMPORTANTE: Ejecutar esto en SQL Server Management Studio como usuario admin
-- Database: BD_SIGO
-- ============================================================================

USE BD_SIGO;
GO

-- PASO 1: Dar permisos al usuario SigoWebAppUser
-- (Descomenta si es necesario y tienes permisos de DBA)
-- ALTER AUTHORIZATION ON DATABASE::BD_SIGO TO sa;
-- GO

-- TABLA 1: Ciclos Presupuestarios (crear PRIMERO porque otras la referencian)
DROP TABLE IF EXISTS ciclos_presupuestarios;
GO

CREATE TABLE ciclos_presupuestarios (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    ano_fiscal INT NOT NULL UNIQUE,
    estado NVARCHAR(255) NOT NULL CHECK (estado IN ('ABIERTO', 'CERRADO')) DEFAULT 'ABIERTO',
    fecha_inicio DATE NOT NULL,
    fecha_cierre DATE NULL,
    presupuesto_total_inicial DECIMAL(15, 2) NOT NULL,
    presupuesto_total_aprobado DECIMAL(15, 2) NOT NULL DEFAULT 0,
    cantidad_solicitudes_totales INT NOT NULL DEFAULT 0,
    cantidad_solicitudes_aprobadas INT NOT NULL DEFAULT 0,
    cantidad_beneficiarios_atendidos INT NOT NULL DEFAULT 0,
    creada_por BIGINT NOT NULL,
    created_at DATETIME2 DEFAULT GETUTCDATE(),
    updated_at DATETIME2 DEFAULT GETUTCDATE(),
    
    CONSTRAINT FK_ciclos_usuarios FOREIGN KEY (creada_por) 
        REFERENCES Usuarios(id_usuario) ON DELETE RESTRICT
);

CREATE INDEX IX_ciclos_ano ON ciclos_presupuestarios(ano_fiscal);
GO

-- TABLA 2: Presupuesto Categorías
DROP TABLE IF EXISTS presupuesto_categorias;
GO

CREATE TABLE presupuesto_categorias (
    id_presupuesto BIGINT IDENTITY(1,1) PRIMARY KEY,
    id_ciclo BIGINT NOT NULL,
    nombre NVARCHAR(100) NOT NULL,
    presupuesto_anual DECIMAL(15, 2) NOT NULL,
    disponible DECIMAL(15, 2) NOT NULL,
    estado NVARCHAR(50) DEFAULT 'ABIERTO',
    fecha_creacion DATETIME2 DEFAULT GETUTCDATE(),
    
    CONSTRAINT FK_presupuesto_cat_ciclos FOREIGN KEY (id_ciclo) 
        REFERENCES ciclos_presupuestarios(id) ON DELETE CASCADE,
    CONSTRAINT UC_presupuesto_cat_ciclo_nombre UNIQUE (id_ciclo, nombre)
);

CREATE INDEX IX_presupuesto_cat_ciclo ON presupuesto_categorias(id_ciclo);
GO

-- TABLA 3: Presupuesto Apoyos
DROP TABLE IF EXISTS presupuesto_apoyos;
GO

CREATE TABLE presupuesto_apoyos (
    id_presupuesto_apoyo BIGINT IDENTITY(1,1) PRIMARY KEY,
    id_categoria BIGINT NOT NULL,
    id_apoyo BIGINT NOT NULL,
    ano_fiscal INT NOT NULL,
    presupuesto_total DECIMAL(15, 2) NOT NULL,
    disponible DECIMAL(15, 2) NOT NULL,
    estado NVARCHAR(50) DEFAULT 'ACTIVO',
    fecha_creacion DATETIME2 DEFAULT GETUTCDATE(),
    
    CONSTRAINT FK_presupuesto_apo_cat FOREIGN KEY (id_categoria) 
        REFERENCES presupuesto_categorias(id_presupuesto) ON DELETE CASCADE,
    CONSTRAINT FK_presupuesto_apo_apoyos FOREIGN KEY (id_apoyo) 
        REFERENCES Apoyos(id) ON DELETE RESTRICT
);

CREATE INDEX IX_presupuesto_apo_cat ON presupuesto_apoyos(id_categoria);
CREATE INDEX IX_presupuesto_apo_apoyo ON presupuesto_apoyos(id_apoyo);
GO

-- TABLA 4: Movimientos Presupuestarios (Auditoría)
DROP TABLE IF EXISTS movimientos_presupuestarios;
GO

CREATE TABLE movimientos_presupuestarios (
    id_movimiento BIGINT IDENTITY(1,1) PRIMARY KEY,
    id_solicitud BIGINT NULL,
    id_categoria BIGINT NOT NULL,
    id_apoyo BIGINT NOT NULL,
    tipo_movimiento NVARCHAR(50) NOT NULL,  -- RESERVA, ASIGNACION, LIBERACION, RECHAZO
    monto DECIMAL(15, 2) NOT NULL,
    directivo_id BIGINT NULL,
    fecha_movimiento DATETIME2 DEFAULT GETUTCDATE(),
    estado NVARCHAR(50) DEFAULT 'CONFIRMADO',  -- PENDIENTE, CONFIRMADO, REVERTIDO
    observaciones NVARCHAR(MAX) NULL,
    
    CONSTRAINT FK_movimientos_solicitud FOREIGN KEY (id_solicitud) 
        REFERENCES Solicitudes(id) ON DELETE SET NULL,
    CONSTRAINT FK_movimientos_categoria FOREIGN KEY (id_categoria) 
        REFERENCES presupuesto_categorias(id_presupuesto) ON DELETE RESTRICT,
    CONSTRAINT FK_movimientos_apoyo FOREIGN KEY (id_apoyo) 
        REFERENCES Apoyos(id) ON DELETE RESTRICT,
    CONSTRAINT FK_movimientos_directivo FOREIGN KEY (directivo_id) 
        REFERENCES Usuarios(id_usuario) ON DELETE SET NULL
);

CREATE INDEX IX_movimientos_solicitud ON movimientos_presupuestarios(id_solicitud);
CREATE INDEX IX_movimientos_fecha ON movimientos_presupuestarios(fecha_movimiento);
CREATE INDEX IX_movimientos_tipo ON movimientos_presupuestarios(tipo_movimiento);
GO

-- TABLA 5: Alertas Presupuesto
DROP TABLE IF EXISTS alertas_presupuesto;
GO

CREATE TABLE alertas_presupuesto (
    id BIGINT IDENTITY(1,1) PRIMARY KEY,
    id_categoria BIGINT NOT NULL,
    nivel_alerta NVARCHAR(50) NOT NULL,  -- NORMAL, AMARILLA, ROJA, CRITICA
    mensaje NVARCHAR(MAX) NOT NULL,
    fecha_alerta DATETIME2 DEFAULT GETUTCDATE(),
    vista BIT DEFAULT 0,
    fecha_vista DATETIME2 NULL,
    
    CONSTRAINT FK_alertas_categoria FOREIGN KEY (id_categoria) 
        REFERENCES presupuesto_categorias(id_presupuesto) ON DELETE CASCADE
);

CREATE INDEX IX_alertas_categoria ON alertas_presupuesto(id_categoria);
CREATE INDEX IX_alertas_nivel ON alertas_presupuesto(nivel_alerta);
GO

-- ============================================================================
-- AGREGAR COLUMNAS A SOLICITUDES (si no existen)
-- ============================================================================

IF NOT EXISTS (SELECT 1 FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME='Solicitudes' AND COLUMN_NAME='presupuesto_confirmado')
BEGIN
    ALTER TABLE Solicitudes 
    ADD presupuesto_confirmado BIT NOT NULL DEFAULT 0,
        fecha_confirmacion_presupuesto DATETIME2 NULL,
        directivo_autorizo INT NULL;
    
    CREATE INDEX IX_presupuesto_confirmado ON Solicitudes(presupuesto_confirmado);
END
GO

-- ============================================================================
-- CARGAR DATOS INICIALES PARA 2026
-- ============================================================================

-- Crear ciclo 2026
IF NOT EXISTS (SELECT 1 FROM ciclos_presupuestarios WHERE ano_fiscal = 2026)
BEGIN
    INSERT INTO ciclos_presupuestarios 
    (ano_fiscal, estado, fecha_inicio, fecha_cierre, presupuesto_total_inicial, creada_por)
    VALUES 
    (2026, 'ABIERTO', '2026-01-01', NULL, 100000000.00, 1);
    
    PRINT '✅ Ciclo 2026 creado';
END
GO

-- Obtener ID del ciclo 2026
DECLARE @ciclo_id BIGINT = (SELECT id FROM ciclos_presupuestarios WHERE ano_fiscal = 2026);

-- Crear categorías
IF NOT EXISTS (SELECT 1 FROM presupuesto_categorias WHERE id_ciclo = @ciclo_id AND nombre = 'Becas y Educación')
BEGIN
    INSERT INTO presupuesto_categorias 
    (id_ciclo, nombre, presupuesto_anual, disponible)
    VALUES 
    (@ciclo_id, 'Becas y Educación', 25000000.00, 25000000.00),
    (@ciclo_id, 'Programas de Empleo', 35000000.00, 35000000.00),
    (@ciclo_id, 'Vivienda y Desarrollo', 20000000.00, 20000000.00),
    (@ciclo_id, 'Cultura y Deporte', 12000000.00, 12000000.00),
    (@ciclo_id, 'Salud y Bienestar', 8000000.00, 8000000.00);
    
    PRINT '✅ Categorías de presupuesto creadas';
END
GO

-- ============================================================================
-- VERIFICACIÓN FINAL
-- ============================================================================

SELECT 
    'TABLAS CREADAS' as Status,
    COUNT(*) as TablesCount
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME IN ('ciclos_presupuestarios', 'presupuesto_categorias', 
                     'presupuesto_apoyos', 'movimientos_presupuestarios', 
                     'alertas_presupuesto');

PRINT '';
PRINT '✅ SETUP COMPLETADO EXITOSAMENTE';
PRINT 'Ciclos presupuestarios 2026 creados con $100,000,000 distribuido en 5 categorías';
GO

-- ============================================================================
-- PARA DARLE PERMISOS AL USUARIO SigoWebAppUser (ejecutar SOLO si necesario)
-- ============================================================================
-- Descomenta las siguientes líneas si quieres permitir que el usuario 
-- pueda hacer migraciones en el futuro

/*
GRANT SELECT, INSERT, UPDATE, DELETE ON ciclos_presupuestarios TO [SigoWebAppUser];
GRANT SELECT, INSERT, UPDATE, DELETE ON presupuesto_categorias TO [SigoWebAppUser];
GRANT SELECT, INSERT, UPDATE, DELETE ON presupuesto_apoyos TO [SigoWebAppUser];
GRANT SELECT, INSERT, UPDATE, DELETE ON movimientos_presupuestarios TO [SigoWebAppUser];
GRANT SELECT, INSERT, UPDATE, DELETE ON alertas_presupuesto TO [SigoWebAppUser];
GO

-- Para permitir migraciones futuras:
-- GRANT CREATE TABLE TO [SigoWebAppUser];
*/
