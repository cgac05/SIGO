-- =====================================================================
-- SIGO BD ARMONIZACIÓN - SQL Server Direct Implementation Script
-- =====================================================================
-- Ejecutar directamente en SQL Server Management Studio
-- Database: BD_SIGO
-- =====================================================================

USE BD_SIGO;
GO

PRINT '=== INICIANDO ARMONIZACIÓN BD SIGO ===';
GO

-- =====================================================================
-- PARTE 1: Añadir campos a Documentos_Expediente (P2)
-- =====================================================================
PRINT '--- PARTE 1: Documentos_Expediente Fields ---';

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Documentos_Expediente' AND COLUMN_NAME = 'origen_carga')
BEGIN
    ALTER TABLE Documentos_Expediente 
    ADD origen_carga NVARCHAR(50) DEFAULT 'beneficiario';
    PRINT 'Campo origen_carga añadido';
END
GO

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Documentos_Expediente' AND COLUMN_NAME = 'cargado_por')
BEGIN
    ALTER TABLE Documentos_Expediente 
    ADD cargado_por INT NULL;
    PRINT 'Campo cargado_por añadido';
END
GO

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Documentos_Expediente' AND COLUMN_NAME = 'justificacion_carga_fria')
BEGIN
    ALTER TABLE Documentos_Expediente 
    ADD justificacion_carga_fria NVARCHAR(MAX) NULL;
    PRINT 'Campo justificacion_carga_fria añadido';
END
GO

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Documentos_Expediente' AND COLUMN_NAME = 'marca_agua_aplicada')
BEGIN
    ALTER TABLE Documentos_Expediente 
    ADD marca_agua_aplicada BIT DEFAULT 0;
    PRINT 'Campo marca_agua_aplicada añadido';
END
GO

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Documentos_Expediente' AND COLUMN_NAME = 'qr_seguimiento')
BEGIN
    ALTER TABLE Documentos_Expediente 
    ADD qr_seguimiento NVARCHAR(510) NULL;
    PRINT 'Campo qr_seguimiento añadido';
END
GO

-- Añadir FK para cargado_por
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS 
              WHERE CONSTRAINT_NAME = 'FK_Documentos_cargado_por' AND TABLE_NAME = 'Documentos_Expediente')
BEGIN
    ALTER TABLE Documentos_Expediente
    ADD CONSTRAINT FK_Documentos_cargado_por 
    FOREIGN KEY (cargado_por) REFERENCES Usuarios(id_usuario);
    PRINT 'Foreign Key para cargado_por creada';
END
GO

-- =====================================================================
-- PARTE 2: Añadir campos a Apoyos (P3)
-- =====================================================================
PRINT '--- PARTE 2: Apoyos Fields ---';

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Apoyos' AND COLUMN_NAME = 'tipo_apoyo_detallado')
BEGIN
    ALTER TABLE Apoyos 
    ADD tipo_apoyo_detallado NVARCHAR(50) NULL;
    PRINT 'Campo tipo_apoyo_detallado añadido';
END
GO

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Apoyos' AND COLUMN_NAME = 'requiere_inventario')
BEGIN
    ALTER TABLE Apoyos 
    ADD requiere_inventario BIT DEFAULT 0;
    PRINT 'Campo requiere_inventario añadido';
END
GO

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'Apoyos' AND COLUMN_NAME = 'costo_promedio_unitario')
BEGIN
    ALTER TABLE Apoyos 
    ADD costo_promedio_unitario MONEY NULL;
    PRINT 'Campo costo_promedio_unitario añadido';
END
GO

-- =====================================================================
-- PARTE 3: Añadir nuevos estados (P4)
-- =====================================================================
PRINT '--- PARTE 3: New States ---';

IF NOT EXISTS (SELECT * FROM Cat_EstadosSolicitud WHERE id_estado = 6)
BEGIN
    INSERT INTO Cat_EstadosSolicitud (id_estado, nombre_estado)
    VALUES (6, 'Expediente Creado');
    PRINT 'Estado 6: Expediente Creado añadido';
END
GO

IF NOT EXISTS (SELECT * FROM Cat_EstadosSolicitud WHERE id_estado = 7)
BEGIN
    INSERT INTO Cat_EstadosSolicitud (id_estado, nombre_estado)
    VALUES (7, 'Documentos Cargados Admin');
    PRINT 'Estado 7: Documentos Cargados Admin añadido';
END
GO

IF NOT EXISTS (SELECT * FROM Cat_EstadosSolicitud WHERE id_estado = 8)
BEGIN
    INSERT INTO Cat_EstadosSolicitud (id_estado, nombre_estado)
    VALUES (8, 'Consentido Beneficiario');
    PRINT 'Estado 8: Consentido Beneficiario añadido';
END
GO

IF NOT EXISTS (SELECT * FROM Cat_EstadosSolicitud WHERE id_estado = 9)
BEGIN
    INSERT INTO Cat_EstadosSolicitud (id_estado, nombre_estado)
    VALUES (9, 'Rechazado por Beneficiario');
    PRINT 'Estado 9: Rechazado por Beneficiario añadido';
END
GO

-- =====================================================================
-- PARTE 4: Crear tabla auditorias_carga_fria (P7)
-- =====================================================================
PRINT '--- PARTE 4: Carga Fría Audit Table ---';

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'auditorias_carga_fria')
BEGIN
    CREATE TABLE auditorias_carga_fria (
        id_auditoria INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_beneficiario INT NOT NULL,
        fk_id_admin INT NOT NULL,
        fk_id_solicitud INT NULL,
        apartado_carga NVARCHAR(50) NULL,
        cantidad_documentos INT DEFAULT 0,
        justificacion NVARCHAR(MAX) NULL,
        fecha_carga DATETIME DEFAULT GETDATE(),
        ip_admin NVARCHAR(45) NULL,
        navegador_agente NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_beneficiario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_admin) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_solicitud) REFERENCES Solicitudes(folio) ON DELETE SET NULL
    );
    
    CREATE INDEX IX_auditorias_carga_beneficiario ON auditorias_carga_fria(fk_id_beneficiario);
    CREATE INDEX IX_auditorias_carga_admin ON auditorias_carga_fria(fk_id_admin);
    CREATE INDEX IX_auditorias_carga_solicitud ON auditorias_carga_fria(fk_id_solicitud);
    CREATE INDEX IX_auditorias_carga_fecha ON auditorias_carga_fria(fecha_carga);
    
    PRINT 'Tabla auditorias_carga_fria creada';
END
GO

-- =====================================================================
-- PARTE 5: Crear tabla consentimientos_carga_fria (P7)
-- =====================================================================
PRINT '--- PARTE 5: Cold Load Consent Table ---';

IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'consentimientos_carga_fria')
BEGIN
    CREATE TABLE consentimientos_carga_fria (
        id_consentimiento INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_beneficiario INT NOT NULL,
        fk_id_auditoria_carga_fria INT NOT NULL,
        consiente BIT NULL,
        fecha_consentimiento DATETIME NULL,
        ip_beneficiario NVARCHAR(45) NULL,
        metodo_consentimiento NVARCHAR(50) NULL,
        observaciones NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_beneficiario) REFERENCES Usuarios(id_usuario) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_auditoria_carga_fria) REFERENCES auditorias_carga_fria(id_auditoria) ON DELETE CASCADE
    );
    
    CREATE INDEX IX_consentimientos_beneficiario ON consentimientos_carga_fria(fk_id_beneficiario);
    CREATE INDEX IX_consentimientos_auditoria ON consentimientos_carga_fria(fk_id_auditoria_carga_fria);
    CREATE INDEX IX_consentimientos_estado ON consentimientos_carga_fria(consiente);
    
    PRINT 'Tabla consentimientos_carga_fria creada';
END
GO

-- =====================================================================
-- PARTE 6: Crear sistema de Inventario (P8) - 9 tablas
-- =====================================================================
PRINT '--- PARTE 6: Inventory System Tables ---';

-- 1. inventario_material
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'inventario_material')
BEGIN
    CREATE TABLE inventario_material (
        id_inventario INT IDENTITY(1,1) PRIMARY KEY,
        codigo_material NVARCHAR(50) UNIQUE NOT NULL,
        nombre_material NVARCHAR(255) NOT NULL,
        descripcion NVARCHAR(MAX) NULL,
        fk_id_apoyo INT NOT NULL,
        unidad_medida NVARCHAR(30) DEFAULT 'pieza',
        cantidad_actual DECIMAL(19,4) DEFAULT 0,
        cantidad_minima DECIMAL(19,4) DEFAULT 0,
        costo_unitario MONEY DEFAULT 0,
        proveedor_principal NVARCHAR(255) NULL,
        activo BIT DEFAULT 1,
        ultima_actualizacion DATETIME DEFAULT GETDATE(),
        FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo) ON DELETE NO ACTION
    );
    CREATE INDEX IX_inv_mat_apoyo ON inventario_material(fk_id_apoyo);
    CREATE INDEX IX_inv_mat_codigo ON inventario_material(codigo_material);
    PRINT 'Tabla inventario_material creada';
END
GO

-- 2. componentes_apoyo
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'componentes_apoyo')
BEGIN
    CREATE TABLE componentes_apoyo (
        id_componente INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_apoyo INT NOT NULL,
        fk_id_inventario INT NOT NULL,
        cantidad_requerida DECIMAL(19,4) NOT NULL,
        costo_componente MONEY DEFAULT 0,
        orden_presentacion INT DEFAULT 0,
        especificaciones NVARCHAR(MAX) NULL,
        es_opcional BIT DEFAULT 0,
        FOREIGN KEY (fk_id_apoyo) REFERENCES Apoyos(id_apoyo) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_inventario) REFERENCES inventario_material(id_inventario) ON DELETE NO ACTION,
        CONSTRAINT UQ_componentes_apoyo_inventario UNIQUE(fk_id_apoyo, fk_id_inventario)
    );
    PRINT 'Tabla componentes_apoyo creada';
END
GO

-- 3. ordenes_compra_interno
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'ordenes_compra_interno')
BEGIN
    CREATE TABLE ordenes_compra_interno (
        id_orden_compra INT IDENTITY(1,1) PRIMARY KEY,
        numero_orden NVARCHAR(50) UNIQUE NOT NULL,
        fk_id_solicitante INT NOT NULL,
        fk_id_autorizante INT NULL,
        fk_id_almacenista INT NULL,
        estado NVARCHAR(30) DEFAULT 'Solicitada',
        monto_presupuestado MONEY NOT NULL,
        justificacion NVARCHAR(510) NULL,
        fecha_solicitud DATETIME DEFAULT GETDATE(),
        fecha_autorizacion DATETIME NULL,
        fecha_recepcion DATETIME NULL,
        observaciones NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_solicitante) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_autorizante) REFERENCES Usuarios(id_usuario) ON DELETE SET NULL,
        FOREIGN KEY (fk_id_almacenista) REFERENCES Usuarios(id_usuario) ON DELETE SET NULL
    );
    CREATE INDEX IX_ordenes_solicitante ON ordenes_compra_interno(fk_id_solicitante);
    CREATE INDEX IX_ordenes_estado ON ordenes_compra_interno(estado);
    PRINT 'Tabla ordenes_compra_interno creada';
END
GO

-- 4. recepciones_material
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'recepciones_material')
BEGIN
    CREATE TABLE recepciones_material (
        id_recepcion INT IDENTITY(1,1) PRIMARY KEY,
        numero_recepcion NVARCHAR(50) UNIQUE NOT NULL,
        fk_id_orden_compra INT NULL,
        fk_id_factura_compra INT NULL,
        fk_id_almacenista INT NOT NULL,
        fk_id_supervisor INT NULL,
        fecha_recepcion DATETIME DEFAULT GETDATE(),
        condicion_recepcion NVARCHAR(50) NULL,
        observaciones NVARCHAR(MAX) NULL,
        requiere_verificacion BIT DEFAULT 0,
        FOREIGN KEY (fk_id_almacenista) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_supervisor) REFERENCES Usuarios(id_usuario) ON DELETE SET NULL
    );
    CREATE INDEX IX_recepciones_almacenista ON recepciones_material(fk_id_almacenista);
    PRINT 'Tabla recepciones_material creada';
END
GO

-- 5. facturas_compra
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'facturas_compra')
BEGIN
    CREATE TABLE facturas_compra (
        id_factura INT IDENTITY(1,1) PRIMARY KEY,
        numero_factura NVARCHAR(50) UNIQUE NOT NULL,
        rfc_proveedor NVARCHAR(20) NULL,
        razon_social_proveedor NVARCHAR(255) NOT NULL,
        fk_id_orden_compra INT NULL,
        fecha_factura DATETIME NOT NULL,
        fecha_vencimiento DATETIME NULL,
        subtotal MONEY NOT NULL,
        impuestos MONEY DEFAULT 0,
        descuentos MONEY DEFAULT 0,
        total MONEY NOT NULL,
        estado_pago NVARCHAR(30) DEFAULT 'Pendiente',
        folio_cfdi NVARCHAR(50) NULL,
        observaciones NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_orden_compra) REFERENCES ordenes_compra_interno(id_orden_compra) ON DELETE SET NULL
    );
    CREATE INDEX IX_facturas_orden ON facturas_compra(fk_id_orden_compra);
    CREATE INDEX IX_facturas_estado ON facturas_compra(estado_pago);
    PRINT 'Tabla facturas_compra creada';
END
GO

-- 6. movimientos_inventario
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'movimientos_inventario')
BEGIN
    CREATE TABLE movimientos_inventario (
        id_movimiento INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_inventario INT NOT NULL,
        tipo_movimiento NVARCHAR(50) NOT NULL,
        cantidad DECIMAL(19,4) NOT NULL,
        costo_unitario MONEY NOT NULL,
        fk_id_usuario INT NOT NULL,
        fecha_movimiento DATETIME DEFAULT GETDATE(),
        referencia NVARCHAR(100) NULL,
        observaciones NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_inventario) REFERENCES inventario_material(id_inventario) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION
    );
    CREATE INDEX IX_mov_inv_inventario ON movimientos_inventario(fk_id_inventario);
    CREATE INDEX IX_mov_inv_tipo ON movimientos_inventario(tipo_movimiento);
    CREATE INDEX IX_mov_inv_fecha ON movimientos_inventario(fecha_movimiento);
    PRINT 'Tabla movimientos_inventario creada';
END
GO

-- 7. salidas_beneficiarios
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'salidas_beneficiarios')
BEGIN
    CREATE TABLE salidas_beneficiarios (
        id_salida INT IDENTITY(1,1) PRIMARY KEY,
        numero_salida NVARCHAR(50) UNIQUE NOT NULL,
        fk_id_solicitud INT NOT NULL,
        fk_id_beneficiario INT NOT NULL,
        fk_id_almacenista INT NOT NULL,
        fk_id_supervisor INT NULL,
        tipo_entrega NVARCHAR(50) DEFAULT 'Kit Completo',
        fecha_salida DATETIME DEFAULT GETDATE(),
        fecha_entrega_beneficiario DATETIME NULL,
        firma_beneficiario_base64 NVARCHAR(MAX) NULL,
        firma_almacenista_base64 NVARCHAR(MAX) NULL,
        monto_total_entregado MONEY NOT NULL,
        estado NVARCHAR(30) DEFAULT 'Generada',
        observaciones NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_solicitud) REFERENCES Solicitudes(folio) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_beneficiario) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_almacenista) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_supervisor) REFERENCES Usuarios(id_usuario) ON DELETE SET NULL
    );
    CREATE INDEX IX_salidas_solicitud ON salidas_beneficiarios(fk_id_solicitud);
    CREATE INDEX IX_salidas_beneficiario ON salidas_beneficiarios(fk_id_beneficiario);
    CREATE INDEX IX_salidas_estado ON salidas_beneficiarios(estado);
    CREATE INDEX IX_salidas_fecha ON salidas_beneficiarios(fecha_salida);
    PRINT 'Tabla salidas_beneficiarios creada';
END
GO

-- 8. detalle_salida_beneficiarios
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'detalle_salida_beneficiarios')
BEGIN
    CREATE TABLE detalle_salida_beneficiarios (
        id_detalle INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_salida INT NOT NULL,
        fk_id_inventario INT NOT NULL,
        cantidad_solicitada DECIMAL(19,4) NOT NULL,
        cantidad_entregada DECIMAL(19,4) NOT NULL,
        costo_unitario MONEY NOT NULL,
        especificaciones_entregadas NVARCHAR(MAX) NULL,
        observaciones NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_salida) REFERENCES salidas_beneficiarios(id_salida) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_inventario) REFERENCES inventario_material(id_inventario) ON DELETE NO ACTION
    );
    PRINT 'Tabla detalle_salida_beneficiarios creada';
END
GO

-- 9. auditorias_salida_material
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'auditorias_salida_material')
BEGIN
    CREATE TABLE auditorias_salida_material (
        id_auditoria INT IDENTITY(1,1) PRIMARY KEY,
        fk_id_salida INT NOT NULL,
        evento_tipo NVARCHAR(50) NOT NULL,
        fk_id_usuario INT NOT NULL,
        fecha_evento DATETIME DEFAULT GETDATE(),
        ip_origen NVARCHAR(45) NULL,
        navegador_agente NVARCHAR(MAX) NULL,
        cambios_realizados NVARCHAR(MAX) NULL,
        razon_auditoria NVARCHAR(MAX) NULL,
        estado_cumplimiento NVARCHAR(50) DEFAULT 'Conforme',
        FOREIGN KEY (fk_id_salida) REFERENCES salidas_beneficiarios(id_salida) ON DELETE CASCADE,
        FOREIGN KEY (fk_id_usuario) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION
    );
    CREATE INDEX IX_audit_salida ON auditorias_salida_material(fk_id_salida);
    CREATE INDEX IX_audit_usuario ON auditorias_salida_material(fk_id_usuario);
    CREATE INDEX IX_audit_fecha ON auditorias_salida_material(fecha_evento);
    PRINT 'Tabla auditorias_salida_material creada';
END
GO

-- =====================================================================
-- PARTE 7: Mejorar Google Drive Audit Logs (P9)
-- =====================================================================
PRINT '--- PARTE 7: Google Drive & LGPDP Compliance ---';

-- Asegurar que google_drive_audit_logs tiene todos los campos
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
              WHERE TABLE_NAME = 'google_drive_audit_logs' AND COLUMN_NAME = 'accion')
BEGIN
    ALTER TABLE google_drive_audit_logs 
    ADD accion NVARCHAR(50) NULL;
    PRINT 'Campo accion añadido a google_drive_audit_logs';
END
GO

-- Tabla: politicas_retencion_datos
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'politicas_retencion_datos')
BEGIN
    CREATE TABLE politicas_retencion_datos (
        id_politica INT IDENTITY(1,1) PRIMARY KEY,
        nombre_politica NVARCHAR(255) NOT NULL,
        descripcion NVARCHAR(MAX) NOT NULL,
        dias_retencion INT NOT NULL,
        tipo_dato NVARCHAR(100) NOT NULL,
        requiere_consentimiento_previo BIT DEFAULT 1,
        fundamento_legal NVARCHAR(MAX) NULL,
        activa BIT DEFAULT 1,
        fecha_creacion DATETIME DEFAULT GETDATE(),
        fk_id_usuario_creador INT NOT NULL,
        FOREIGN KEY (fk_id_usuario_creador) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION
    );
    CREATE INDEX IX_politicas_tipo ON politicas_retencion_datos(tipo_dato);
    PRINT 'Tabla politicas_retencion_datos creada';
END
GO

-- Tabla: solicitudes_arco (LGPDP Rights)
IF NOT EXISTS (SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'solicitudes_arco')
BEGIN
    CREATE TABLE solicitudes_arco (
        id_solicitud_arco INT IDENTITY(1,1) PRIMARY KEY,
        folio_arco NVARCHAR(50) UNIQUE NOT NULL,
        fk_id_beneficiario INT NOT NULL,
        tipo_solicitud NVARCHAR(50) NOT NULL,
        descripcion_solicitud NVARCHAR(MAX) NOT NULL,
        fecha_solicitud DATETIME DEFAULT GETDATE(),
        estado NVARCHAR(50) DEFAULT 'Recibida',
        fk_id_responsable INT NULL,
        fecha_respuesta DATETIME NULL,
        respuesta_texto NVARCHAR(MAX) NULL,
        fecha_limite_respuesta DATETIME NULL,
        documentacion_completa BIT DEFAULT 0,
        razon_rechazo NVARCHAR(MAX) NULL,
        FOREIGN KEY (fk_id_beneficiario) REFERENCES Usuarios(id_usuario) ON DELETE NO ACTION,
        FOREIGN KEY (fk_id_responsable) REFERENCES Usuarios(id_usuario) ON DELETE SET NULL
    );
    CREATE INDEX IX_arco_beneficiario ON solicitudes_arco(fk_id_beneficiario);
    CREATE INDEX IX_arco_tipo ON solicitudes_arco(tipo_solicitud);
    CREATE INDEX IX_arco_estado ON solicitudes_arco(estado);
    PRINT 'Tabla solicitudes_arco creada';
END
GO

-- =====================================================================
-- FINALIZACIÓN
-- =====================================================================
PRINT '=== ✅ ARMONIZACIÓN BD SIGO COMPLETADA ===';
PRINT 'Todos los cambios han sido aplicados correctamente.';
GO

-- Verificación final
PRINT '';
PRINT '=== VALIDACIÓN FINAL ===';

SELECT 
    'Documentos_Expediente' as Tabla,
    COUNT(*) as Campos_Totales
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'Documentos_Expediente'
UNION ALL
SELECT 
    'Apoyos',
    COUNT(*)
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'Apoyos'
UNION ALL
SELECT 
    'Cat_EstadosSolicitud',
    COUNT(*)
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_NAME = 'Cat_EstadosSolicitud';

PRINT '';
PRINT 'Estados registrados:';
SELECT id_estado, nombre_estado FROM Cat_EstadosSolicitud ORDER BY id_estado;

PRINT '';
PRINT 'Tablas inventario existentes:';
SELECT TABLE_NAME 
FROM INFORMATION_SCHEMA.TABLES 
WHERE TABLE_NAME IN (
    'auditorias_carga_fria',
    'consentimientos_carga_fria',
    'inventario_material',
    'componentes_apoyo',
    'ordenes_compra_interno',
    'recepciones_material',
    'facturas_compra',
    'movimientos_inventario',
    'salidas_beneficiarios',
    'detalle_salida_beneficiarios',
    'auditorias_salida_material',
    'politicas_retencion_datos',
    'solicitudes_arco'
)
ORDER BY TABLE_NAME;
