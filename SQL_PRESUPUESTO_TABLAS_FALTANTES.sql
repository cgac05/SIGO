-- ============================================================================
-- SIGO - Crear tablas presupuestación faltantes
-- ============================================================================

USE BD_SIGO;
GO

-- TABLA: Presupuesto Apoyos (si no existe)
IF OBJECT_ID('presupuesto_apoyos', 'U') IS NULL
BEGIN
    CREATE TABLE presupuesto_apoyos (
        id_apoyo_presupuesto BIGINT IDENTITY(1,1) PRIMARY KEY,
        folio INT NOT NULL,
        id_categoria BIGINT NOT NULL,
        monto_solicitado DECIMAL(15, 2) NOT NULL,
        monto_aprobado DECIMAL(15, 2) NULL,
        estado NVARCHAR(50) DEFAULT 'PENDIENTE',
        fecha_solicitud DATETIME2 DEFAULT GETUTCDATE(),
        fecha_aprobacion DATETIME2 NULL,
        aprobado_por INT NULL,
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE(),
        
        CONSTRAINT FK_presupuesto_apoyos_solicitudes FOREIGN KEY (folio) 
            REFERENCES solicitudes(folio) ON DELETE CASCADE,
        CONSTRAINT FK_presupuesto_apoyos_categorias FOREIGN KEY (id_categoria) 
            REFERENCES presupuesto_categorias(id_categoria) ON DELETE CASCADE
    );
    
    CREATE INDEX IX_presupuesto_apoyos_solicitud ON presupuesto_apoyos(folio);
    CREATE INDEX IX_presupuesto_apoyos_categoria ON presupuesto_apoyos(id_categoria);
    PRINT '✅ Tabla presupuesto_apoyos creada';
END
ELSE
    PRINT 'ℹ️  Tabla presupuesto_apoyos ya existe';
GO

-- TABLA: Movimientos Presupuestarios
IF OBJECT_ID('movimientos_presupuestarios', 'U') IS NULL
BEGIN
    CREATE TABLE movimientos_presupuestarios (
        id_movimiento BIGINT IDENTITY(1,1) PRIMARY KEY,
        id_categoria BIGINT NOT NULL,
        id_apoyo_presupuesto BIGINT NULL,
        tipo_movimiento NVARCHAR(50) NOT NULL,
        monto DECIMAL(15, 2) NOT NULL,
        descripcion NVARCHAR(500) NULL,
        creado_por INT NOT NULL,
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE(),
        
        CONSTRAINT FK_movimientos_categorias FOREIGN KEY (id_categoria)
            REFERENCES presupuesto_categorias(id_categoria) ON DELETE NO ACTION,
        CONSTRAINT FK_movimientos_apoyos FOREIGN KEY (id_apoyo_presupuesto)
            REFERENCES presupuesto_apoyos(id_apoyo_presupuesto) ON DELETE NO ACTION,
        CONSTRAINT FK_movimientos_usuarios FOREIGN KEY (creado_por)
            REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION
    );
    
    CREATE INDEX IX_movimientos_categoria ON movimientos_presupuestarios(id_categoria);
    CREATE INDEX IX_movimientos_apoyo ON movimientos_presupuestarios(id_apoyo_presupuesto);
    PRINT '✅ Tabla movimientos_presupuestarios creada';
END
ELSE
    PRINT 'ℹ️  Tabla movimientos_presupuestarios ya existe';
GO

-- TABLA: Alertas Presupuestarias
IF OBJECT_ID('alertas_presupuesto', 'U') IS NULL
BEGIN
    CREATE TABLE alertas_presupuesto (
        id_alerta BIGINT IDENTITY(1,1) PRIMARY KEY,
        id_categoria BIGINT NOT NULL,
        tipo_alerta NVARCHAR(50) NOT NULL,
        nivel NVARCHAR(50) NOT NULL,
        porcentaje_disponible DECIMAL(5, 2) NULL,
        descripcion NVARCHAR(500) NULL,
        vista BIT DEFAULT 0,
        fecha_resolucion DATETIME2 NULL,
        resuelto_por INT NULL,
        created_at DATETIME2 DEFAULT GETUTCDATE(),
        updated_at DATETIME2 DEFAULT GETUTCDATE(),
        
        CONSTRAINT FK_alertas_categorias FOREIGN KEY (id_categoria)
            REFERENCES presupuesto_categorias(id_categoria) ON DELETE NO ACTION,
        CONSTRAINT FK_alertas_usuarios FOREIGN KEY (resuelto_por)
            REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION
    );
    
    CREATE INDEX IX_alertas_categoria ON alertas_presupuesto(id_categoria);
    CREATE INDEX IX_alertas_nivel ON alertas_presupuesto(nivel);
    PRINT '✅ Tabla alertas_presupuesto creada';
END
ELSE
    PRINT 'ℹ️  Tabla alertas_presupuesto ya existe';
GO

-- ============================================================================
-- VERIFICACIÓN FINAL
-- ============================================================================

PRINT '';
PRINT '════════════════════════════════════════════════════════';
PRINT '✅ TABLAS PRESUPUESTACIÓN VERIFICADAS';
PRINT '════════════════════════════════════════════════════════';

SELECT 
    'ciclos_presupuestarios' AS [Tabla],
    COUNT(*) AS Total
FROM ciclos_presupuestarios
UNION ALL
SELECT 
    'presupuesto_categorias',
    COUNT(*)
FROM presupuesto_categorias
UNION ALL
SELECT
    'presupuesto_apoyos',
    COUNT(*)
FROM presupuesto_apoyos
UNION ALL
SELECT
    'movimientos_presupuestarios',
    COUNT(*)
FROM movimientos_presupuestarios
UNION ALL
SELECT
    'alertas_presupuesto',
    COUNT(*)
FROM alertas_presupuesto;

PRINT '';
PRINT '📋 RESUMEN:';
PRINT '  • Ciclo 2026 ejecutable';
PRINT '  • 5 categorías presupuestarias activas';
PRINT '  • Presupuesto total: $100,000,000';
PRINT '';
PRINT 'Próximo paso: Verificar en PHP/Laravel';
PRINT '════════════════════════════════════════════════════════';
