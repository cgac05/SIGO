-- FACTURAS DE COMPRA Y TRAZABILIDAD DE INVENTARIO
-- =====================================================

-- 1. facturas_compra - Registro de facturas de compra de materiales
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'facturas_compra')
BEGIN
    CREATE TABLE facturas_compra (
        id_factura INT IDENTITY(1,1) PRIMARY KEY,
        numero_factura NVARCHAR(50) NOT NULL UNIQUE,
        fk_id_proveedor INT NULL,
        nombre_proveedor NVARCHAR(255) NOT NULL,
        rfc_proveedor NVARCHAR(13) NULL,
        fecha_compra DATETIME DEFAULT GETDATE(),
        fecha_recepcion DATETIME NULL,
        monto_total MONEY NOT NULL,
        moneda NVARCHAR(10) DEFAULT 'MXN',
        estado NVARCHAR(30) DEFAULT 'Recibida', -- Recibida, Parcial, Rechazada, Devuelta
        observaciones NVARCHAR(MAX) NULL,
        archivo_factura NVARCHAR(MAX) NULL, -- Ruta archivo PDF/imagen
        registrado_por INT NOT NULL,
        actualizado_por INT NULL,
        created_at DATETIME DEFAULT GETDATE(),
        updated_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (registrado_por) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (actualizado_por) REFERENCES Usuarios(id_usuario) ON DELETE SET NULL
    );
    CREATE INDEX IX_facturas_numero ON facturas_compra(numero_factura);
    CREATE INDEX IX_facturas_fecha ON facturas_compra(fecha_compra);
    CREATE INDEX IX_facturas_proveedor ON facturas_compra(nombre_proveedor);
    PRINT 'Tabla facturas_compra creada';
END
GO

-- 2. detalle_facturas_compra - Detalles de materiales por factura
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'detalle_facturas_compra')
BEGIN
    CREATE TABLE detalle_facturas_compra (
        id_detalle INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_factura INT NOT NULL,
        fk_id_inventario INT NOT NULL,
        cantidad_comprada DECIMAL(19,4) NOT NULL,
        costo_unitario MONEY NOT NULL,
        costo_total MONEY GENERATED ALWAYS AS (cantidad_comprada * costo_unitario) STORED,
        lote_numero NVARCHAR(50) NULL,
        fecha_vencimiento DATE NULL,
        observaciones NVARCHAR(MAX) NULL,
        created_at DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (fk_id_factura) REFERENCES facturas_compra(id_factura) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_inventario) REFERENCES inventario_material(id_inventario) ON DELETE NO ACTION
    );
    CREATE INDEX IX_detalle_factura ON detalle_facturas_compra(fk_id_factura);
    CREATE INDEX IX_detalle_inventario ON detalle_facturas_compra(fk_id_inventario);
    PRINT 'Tabla detalle_facturas_compra creada';
END
GO

-- 3. Actualizar movimientos_inventario para enlazarlo con facturas (si existe)
-- Verificar si la columna ya existe antes de intentar agregar
IF EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'movimientos_inventario')
BEGIN
    IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
        WHERE TABLE_NAME = 'movimientos_inventario' AND COLUMN_NAME = 'fk_id_factura')
    BEGIN
        ALTER TABLE movimientos_inventario
        ADD fk_id_factura INT NULL,
            FOREIGN KEY (fk_id_factura) REFERENCES facturas_compra(id_factura) ON DELETE SET NULL;
        PRINT 'Columna fk_id_factura agregada a movimientos_inventario';
    END
END
GO

PRINT '=== ✅ SETUP FACTURAS DE COMPRA COMPLETADO ===';
