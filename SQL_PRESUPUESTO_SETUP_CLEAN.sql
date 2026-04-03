-- ============================================================================
-- SIGO - PRESUPUESTACIÓN SETUP (VERSIÓN LIMPIA)
-- Script para crear tablas presupuestación en SQL Server
-- ============================================================================

USE BD_SIGO;
GO

-- TABLA 1: Ciclos Presupuestarios
IF OBJECT_ID('ciclos_presupuestarios', 'U') IS NULL
BEGIN
    CREATE TABLE ciclos_presupuestarios (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        ano_fiscal INT NOT NULL UNIQUE,
        estado NVARCHAR(255) NOT NULL DEFAULT 'ABIERTO',
        fecha_inicio DATE NOT NULL,
        fecha_cierre DATE NULL,
        presupuesto_total_inicial DECIMAL(15, 2) NOT NULL,
        presupuesto_total_aprobado DECIMAL(15, 2) DEFAULT 0,
        cantidad_solicitudes_totales INT DEFAULT 0,
        cantidad_solicitudes_aprobadas INT DEFAULT 0,
        cantidad_beneficiarios_atendidos INT DEFAULT 0,
        creada_por BIGINT NOT NULL,
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE()
    );
    
    CREATE INDEX IX_ciclos_ano ON ciclos_presupuestarios(ano_fiscal);
    PRINT 'Tabla ciclos_presupuestarios creada';
END
ELSE
    PRINT 'Tabla ciclos_presupuestarios ya existe';
GO

-- TABLA 2: Presupuesto Categorías
IF OBJECT_ID('presupuesto_categorias', 'U') IS NULL
BEGIN
    CREATE TABLE presupuesto_categorias (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        id_ciclo BIGINT NOT NULL,
        nombre NVARCHAR(100) NOT NULL,
        descripcion NVARCHAR(500) NULL,
        presupuesto_asignado DECIMAL(15, 2) NOT NULL,
        reservado DECIMAL(15, 2) DEFAULT 0,
        aprobado DECIMAL(15, 2) DEFAULT 0,
        desembolsado DECIMAL(15, 2) DEFAULT 0,
        estado NVARCHAR(50) DEFAULT 'ACTIVO',
        nivel_alerta NVARCHAR(50) DEFAULT 'NORMAL',
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE(),
        
        CONSTRAINT FK_presupuesto_categorias_ciclos FOREIGN KEY (id_ciclo) 
            REFERENCES ciclos_presupuestarios(id) ON DELETE CASCADE
    );
    
    CREATE INDEX IX_presupuesto_categorias_ciclo ON presupuesto_categorias(id_ciclo);
    PRINT 'Tabla presupuesto_categorias creada';
END
ELSE
    PRINT 'Tabla presupuesto_categorias ya existe';
GO

-- TABLA 3: Presupuesto Apoyos
IF OBJECT_ID('presupuesto_apoyos', 'U') IS NULL
BEGIN
    CREATE TABLE presupuesto_apoyos (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        id_solicitud BIGINT NOT NULL,
        id_categoria BIGINT NOT NULL,
        monto_solicitado DECIMAL(15, 2) NOT NULL,
        monto_recomendado DECIMAL(15, 2) NULL,
        monto_aprobado DECIMAL(15, 2) NULL,
        estado NVARCHAR(50) DEFAULT 'PENDIENTE',
        fecha_solicitud DATETIME2 DEFAULT GETUTCDATE(),
        fecha_aprobacion DATETIME2 NULL,
        aprobado_por BIGINT NULL,
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE(),
        
        CONSTRAINT FK_presupuesto_apoyos_solicitudes FOREIGN KEY (id_solicitud) 
            REFERENCES solicitudes(id_solicitud) ON DELETE CASCADE,
        CONSTRAINT FK_presupuesto_apoyos_categorias FOREIGN KEY (id_categoria) 
            REFERENCES presupuesto_categorias(id) ON DELETE CASCADE
    );
    
    CREATE INDEX IX_presupuesto_apoyos_solicitud ON presupuesto_apoyos(id_solicitud);
    CREATE INDEX IX_presupuesto_apoyos_categoria ON presupuesto_apoyos(id_categoria);
    PRINT 'Tabla presupuesto_apoyos creada';
END
ELSE
    PRINT 'Tabla presupuesto_apoyos ya existe';
GO

-- TABLA 4: Movimientos Presupuestarios
IF OBJECT_ID('movimientos_presupuestarios', 'U') IS NULL
BEGIN
    CREATE TABLE movimientos_presupuestarios (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        id_categoria BIGINT NOT NULL,
        id_apoyo BIGINT NULL,
        tipo_movimiento NVARCHAR(50) NOT NULL,
        monto DECIMAL(15, 2) NOT NULL,
        descripcion NVARCHAR(500) NULL,
        creado_por BIGINT NOT NULL,
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE(),
        
        CONSTRAINT FK_movimientos_categorias FOREIGN KEY (id_categoria)
            REFERENCES presupuesto_categorias(id) ON DELETE CASCADE,
        CONSTRAINT FK_movimientos_apoyos FOREIGN KEY (id_apoyo)
            REFERENCES presupuesto_apoyos(id) ON DELETE SET NULL,
        CONSTRAINT FK_movimientos_usuarios FOREIGN KEY (creado_por)
            REFERENCES Usuarios(id_usuario) ON DELETE RESTRICT
    );
    
    CREATE INDEX IX_movimientos_categoria ON movimientos_presupuestarios(id_categoria);
    CREATE INDEX IX_movimientos_apoyo ON movimientos_presupuestarios(id_apoyo);
    PRINT 'Tabla movimientos_presupuestarios creada';
END
ELSE
    PRINT 'Tabla movimientos_presupuestarios ya existe';
GO

-- TABLA 5: Alertas Presupuestarias
IF OBJECT_ID('alertas_presupuesto', 'U') IS NULL
BEGIN
    CREATE TABLE alertas_presupuesto (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        id_categoria BIGINT NOT NULL,
        tipo_alerta NVARCHAR(50) NOT NULL,
        nivel NVARCHAR(50) NOT NULL,
        porcentaje_disponible DECIMAL(5, 2) NULL,
        descripcion NVARCHAR(500) NULL,
        vista BIT DEFAULT 0,
        fecha_resolucion DATETIME2 NULL,
        resuelto_por BIGINT NULL,
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE(),
        
        CONSTRAINT FK_alertas_categorias FOREIGN KEY (id_categoria)
            REFERENCES presupuesto_categorias(id) ON DELETE CASCADE,
        CONSTRAINT FK_alertas_usuarios FOREIGN KEY (resuelto_por)
            REFERENCES Usuarios(id_usuario) ON DELETE SET NULL
    );
    
    CREATE INDEX IX_alertas_categoria ON alertas_presupuesto(id_categoria);
    CREATE INDEX IX_alertas_nivel ON alertas_presupuesto(nivel);
    PRINT 'Tabla alertas_presupuesto creada';
END
ELSE
    PRINT 'Tabla alertas_presupuesto ya existe';
GO

-- ============================================================================
-- INSERTAR DATOS INICIALES - CICLO 2026
-- ============================================================================

-- Verificar si ya existe ciclo 2026
IF NOT EXISTS (SELECT 1 FROM ciclos_presupuestarios WHERE ano_fiscal = 2026)
BEGIN
    INSERT INTO ciclos_presupuestarios (
        ano_fiscal,
        estado,
        fecha_inicio,
        fecha_cierre,
        presupuesto_total_inicial,
        presupuesto_total_aprobado,
        creada_por
    ) VALUES (
        2026,
        'ABIERTO',
        CAST('2026-01-01' AS DATE),
        NULL,
        100000000.00,
        100000000.00,
        1
    );
    
    PRINT 'Ciclo presupuestario 2026 creado con presupuesto inicial de $100,000,000';
END
ELSE
    PRINT 'Ciclo presupuestario 2026 ya existe';
GO

-- Insertar categorías presupuestarias para 2026
IF NOT EXISTS (SELECT 1 FROM presupuesto_categorias WHERE id_ciclo = 1 AND nombre = 'Becas')
BEGIN
    INSERT INTO presupuesto_categorias (
        id_ciclo,
        nombre,
        descripcion,
        presupuesto_asignado,
        estado
    ) VALUES 
    (1, 'Becas', 'Becas educativas para beneficiarios', 35000000.00, 'ACTIVO'),
    (1, 'Empleo', 'Apoyo para generación de empleo', 25000000.00, 'ACTIVO'),
    (1, 'Vivienda', 'Apoyo para mejora de vivienda', 20000000.00, 'ACTIVO'),
    (1, 'Cultura', 'Actividades culturales y deportivas', 12000000.00, 'ACTIVO'),
    (1, 'Salud', 'Cobertura de servicios de salud', 8000000.00, 'ACTIVO');
    
    PRINT 'Categorías presupuestarias 2026 creadas (5 categorías con $100,000,000 distribuido)';
END
ELSE
    PRINT 'Categorías presupuestarias ya existen para 2026';
GO

-- ============================================================================
-- VERIFICACIÓN FINAL
-- ============================================================================

PRINT '';
PRINT '════════════════════════════════════════════════════════';
PRINT '✅ SETUP PRESUPUESTACIÓN COMPLETADO EXITOSAMENTE';
PRINT '════════════════════════════════════════════════════════';

SELECT 
    'Ciclos Presupuestarios' AS Recurso,
    COUNT(*) AS Total
FROM ciclos_presupuestarios
UNION ALL
SELECT 
    'Categorías Presupuestarias',
    COUNT(*)
FROM presupuesto_categorias
UNION ALL
SELECT
    'Apoyos Presupuestarios',
    COUNT(*)
FROM presupuesto_apoyos
UNION ALL
SELECT
    'Movimientos Presupuestarios',
    COUNT(*)
FROM movimientos_presupuestarios
UNION ALL
SELECT
    'Alertas Presupuestarias',
    COUNT(*)
FROM alertas_presupuesto;

PRINT '';
PRINT '📋 Ciclo 2026: Presupuesto Total = $100,000,000';
PRINT '📋 Categorías Presupuestarias: 5 activas';
PRINT '';
PRINT 'Próximo paso: php artisan presupuesto:cargar --año=2026';
PRINT '════════════════════════════════════════════════════════';
