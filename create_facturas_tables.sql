-- Crear tabla facturas_compra
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'facturas_compra')
BEGIN
    CREATE TABLE facturas_compra (
        id_factura BIGINT IDENTITY(1,1) PRIMARY KEY,
        numero_factura NVARCHAR(50) NOT NULL UNIQUE,
        nombre_proveedor NVARCHAR(150) NOT NULL,
        rfc_proveedor NVARCHAR(13) NULL,
        fecha_compra DATETIME NOT NULL,
        fecha_recepcion DATETIME NULL,
        monto_total DECIMAL(12, 2) NOT NULL,
        estado NVARCHAR(50) DEFAULT 'Pendiente Recepción',
        observaciones NVARCHAR(MAX) NULL,
        archivo_factura NVARCHAR(255) NULL,
        registrado_por INT NULL,
        actualizado_por INT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (actualizado_por) REFERENCES usuarios(id_usuario) ON DELETE NO ACTION
    );
    
    PRINT 'Tabla facturas_compra creada exitosamente';
END
GO

-- Crear tabla detalles_facturas_compra
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'detalles_facturas_compra')
BEGIN
    CREATE TABLE detalles_facturas_compra (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        fk_id_factura BIGINT NOT NULL,
        fk_id_inventario INT NOT NULL,
        cantidad_comprada DECIMAL(10, 3) NOT NULL,
        costo_unitario DECIMAL(12, 2) NOT NULL,
        lote_numero NVARCHAR(50) NULL,
        fecha_vencimiento DATE NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (fk_id_factura) REFERENCES facturas_compra(id_factura) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_inventario) REFERENCES BD_Inventario(id_inventario) ON DELETE NO ACTION
    );
    
    PRINT 'Tabla detalles_facturas_compra creada exitosamente';
END
GO

-- Crear tabla movimientos_inventario
IF NOT EXISTS (SELECT * FROM sys.tables WHERE name = 'movimientos_inventario')
BEGIN
    CREATE TABLE movimientos_inventario (
        id BIGINT IDENTITY(1,1) PRIMARY KEY,
        fk_id_inventario INT NOT NULL,
        fk_id_factura BIGINT NULL,
        tipo_movimiento NVARCHAR(50) CHECK (tipo_movimiento IN ('ENTRADA', 'SALIDA', 'AJUSTE')) DEFAULT 'ENTRADA',
        cantidad DECIMAL(10, 3) NOT NULL,
        observaciones NVARCHAR(MAX) NULL,
        registrado_por INT NULL,
        fecha_movimiento DATETIME NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (fk_id_inventario) REFERENCES BD_Inventario(id_inventario) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_factura) REFERENCES facturas_compra(id_factura) ON DELETE SET NULL,
        FOREIGN KEY (registrado_por) REFERENCES usuarios(id_usuario) ON DELETE SET NULL
    );
    
    PRINT 'Tabla movimientos_inventario creada exitosamente';
END
GO

-- Registrar las migraciones en base de datos
IF NOT EXISTS (SELECT * FROM migrations WHERE migration = '2026_04_04_120643_create_facturas_compra_table')
BEGIN
    INSERT INTO migrations (migration, batch) VALUES ('2026_04_04_120643_create_facturas_compra_table', 7);
    INSERT INTO migrations (migration, batch) VALUES ('2026_04_04_120803_create_detalles_facturas_compra_table', 7);
    INSERT INTO migrations (migration, batch) VALUES ('2026_04_04_120829_create_movimientos_inventario_table', 7);
    PRINT 'Migraciones registradas en base de datos';
END
GO

PRINT 'Todas las tablas de facturas han sido creadas exitosamente';
