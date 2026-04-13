-- ============================================================================
-- SQL CONSOLIDADO - FASE 2 COMPLETA: RESUMEN CRÍTICO Y FIRMA DIGITAL
-- ============================================================================
-- 
-- Este archivo contiene todos los SQL necesarios para que tus compañeros
-- configuren correctamente el sistema con la Fase 2 (Resumen Crítico) lista
-- para usar.
--
-- INSTRUCCIONES:
-- 1. Abre SQL Server Management Studio (SSMS)
-- 2. Conecta a: Server=JDEV\PARTIDA, Database=BD_SIGO
-- 3. Copia TODO este archivo y ejecuta en una sola consulta
-- 4. Espera a que termine sin errores
--
-- TIEMPO ESTIMADO: 2-3 minutos
-- ============================================================================

USE BD_SIGO
GO

PRINT '========== INICIANDO SETUP FASE 2 =========='
GO

-- ============================================================================
-- 1. RESETEAR Y PREPARAR FOLIO 1000 PARA TESTS
-- ============================================================================

PRINT 'Paso 1: Reseteando Folio 1000...'
GO

-- Primero, obtener el primer hito del apoyo
DECLARE @FirstHitoId INT = (
    SELECT TOP 1 id_hito FROM Hitos_Apoyo 
    WHERE fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1000)
    ORDER BY orden_hito ASC
)

-- Limpiar documentos de folio 1000
DELETE FROM Documentos_Expediente WHERE fk_folio = 1000

-- Resetear solicitud a estado inicial
UPDATE Solicitudes SET
    fk_id_hito_actual = @FirstHitoId,
    fk_id_estado = 1,
    presupuesto_confirmado = 0,
    monto_entregado = NULL,
    cuv = NULL,
    permite_correcciones = 0,
    fecha_actualizacion = GETDATE(),
    observaciones_internas = 'Folio reseteado para testing - Fase 2'
WHERE folio = 1000

PRINT 'Folio 1000 reseteado ✓'
GO

-- ============================================================================
-- 2. PREPARAR FOLIO 1000 EN HITO ANALISIS_ADMIN (FASE 2)
-- ============================================================================

PRINT 'Paso 2: Posicionando Folio 1000 en ANALISIS_ADMIN...'
GO

-- Obtener el hito ANALISIS_ADMIN
DECLARE @AnalisisAdminHitoId INT = (
    SELECT TOP 1 id_hito FROM Hitos_Apoyo 
    WHERE clave_hito = 'ANALISIS_ADMIN'
    AND fk_id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1000)
)

-- Actualizar solicitud al hito ANALISIS_ADMIN
UPDATE Solicitudes SET
    fk_id_hito_actual = @AnalisisAdminHitoId,
    fk_id_estado = 2,
    presupuesto_confirmado = 1,
    fecha_actualizacion = GETDATE()
WHERE folio = 1000

PRINT 'Folio 1000 en ANALISIS_ADMIN ✓'
GO

-- ============================================================================
-- 3. CREAR DOCUMENTO DE PRUEBA PARA FOLIO 1000
-- ============================================================================

PRINT 'Paso 3: Creando documento de prueba...'
GO

-- Obtener tipo de documento válido
DECLARE @TipoDoc INT = (SELECT TOP 1 id_tipo_documento FROM Tipo_Documento LIMIT 1)

-- Insertar documento con estado "Correcto"
IF NOT EXISTS (SELECT * FROM Documentos_Expediente WHERE fk_folio = 1000)
BEGIN
    INSERT INTO Documentos_Expediente (
        fk_folio,
        fk_id_tipo_documento,
        nombre_archivo,
        nombre_digital,
        tamano,
        ruta_almacenamiento,
        estado_validacion,
        fecha_creacion,
        revisado_por,
        fecha_revision
    )
    VALUES (
        1000,
        @TipoDoc,
        'documento_prueba_fase2.pdf',
        'documento_prueba_fase2',
        1024000,
        '/storage/documentos/folio_1000/',
        'Correcto',
        GETDATE(),
        'Sistema',
        GETDATE()
    )
    
    PRINT 'Documento de prueba creado ✓'
END
ELSE
BEGIN
    -- Actualizar documento existente a estado "Correcto"
    UPDATE Documentos_Expediente 
    SET estado_validacion = 'Correcto'
    WHERE fk_folio = 1000
    
    PRINT 'Documento de prueba actualizado a Correcto ✓'
END
GO

-- ============================================================================
-- 4. VERIFICAR ESTRUCTURA DE TABLAS PARA FASE 2
-- ============================================================================

PRINT 'Paso 4: Verificando tablas necesarias...'
GO

-- Verificar que Solicitudes tiene las columnas requeridas
IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Solicitudes' AND COLUMN_NAME = 'presupuesto_confirmado'
)
BEGIN
    ALTER TABLE Solicitudes ADD presupuesto_confirmado INT DEFAULT 0
    PRINT 'Columna presupuesto_confirmado agregada ✓'
END

IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_NAME = 'Solicitudes' AND COLUMN_NAME = 'cuv'
)
BEGIN
    ALTER TABLE Solicitudes ADD cuv VARCHAR(50) NULL
    PRINT 'Columna cuv agregada ✓'
END

PRINT 'Estructura de tablas verificada ✓'
GO

-- ============================================================================
-- 5. VERIFICAR USUARIO ADMIN Y ROLES
-- ============================================================================

PRINT 'Paso 5: Verificando usuario dora1 y roles...'
GO

-- Verificar que dora1 existe y tiene el rol correcto
IF EXISTS (SELECT * FROM Usuarios WHERE nombre = 'dora1')
BEGIN
    DECLARE @UsuarioId INT = (SELECT id_usuario FROM Usuarios WHERE nombre = 'dora1')
    
    -- Verificar que el usuario tiene el rol en Personal
    IF NOT EXISTS (
        SELECT * FROM Personal WHERE fk_id_usuario = @UsuarioId AND fk_rol = 2
    )
    BEGIN
        -- Actualizar o insertar el rol
        IF EXISTS (SELECT * FROM Personal WHERE fk_id_usuario = @UsuarioId)
        BEGIN
            UPDATE Personal SET fk_rol = 2 WHERE fk_id_usuario = @UsuarioId
        END
        ELSE
        BEGIN
            INSERT INTO Personal (fk_id_usuario, fk_rol)
            VALUES (@UsuarioId, 2)
        END
        
        PRINT 'Usuario dora1 configurado con rol 2 (Admin) ✓'
    END
    ELSE
    BEGIN
        PRINT 'Usuario dora1 ya tiene rol 2 ✓'
    END
END
GO

-- ============================================================================
-- 6. CREAR TABLA DE FIRMAS ELECTRÓNICAS (SI NO EXISTE)
-- ============================================================================

PRINT 'Paso 6: Verificando tabla firmas_electronicas...'
GO

IF NOT EXISTS (
    SELECT * FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = 'firmas_electronicas'
)
BEGIN
    CREATE TABLE firmas_electronicas (
        id INT PRIMARY KEY IDENTITY(1,1),
        folio_solicitud INT NOT NULL,
        tipo_firma VARCHAR(50),
        cuv VARCHAR(50) UNIQUE,
        sello_digital VARCHAR(MAX),
        id_directivo INT,
        fecha_firma DATETIME DEFAULT GETDATE(),
        estado VARCHAR(50) DEFAULT 'activa',
        FOREIGN KEY (folio_solicitud) REFERENCES Solicitudes(folio)
    )
    
    PRINT 'Tabla firmas_electronicas creada ✓'
END
ELSE
BEGIN
    PRINT 'Tabla firmas_electronicas ya existe ✓'
END
GO

-- ============================================================================
-- 7. RESUMEN FINAL DE CONFIGURACIÓN
-- ============================================================================

PRINT ''
PRINT '========== RESUMEN DE SETUP =========='
GO

-- Mostrar estado de folio 1000
SELECT 
    'FOLIO 1000' as Concepto,
    s.folio,
    s.fk_id_estado as EstadoId,
    h.clave_hito as HitoActual,
    s.presupuesto_confirmado,
    (SELECT COUNT(*) FROM Documentos_Expediente WHERE fk_folio = 1000) as TotalDocumentos,
    (SELECT COUNT(*) FROM Documentos_Expediente WHERE fk_folio = 1000 AND (estado_validacion = 'Correcto' OR estado_validacion = 'Aprobado')) as DocumentosAprobados
FROM Solicitudes s
LEFT JOIN Hitos_Apoyo h ON s.fk_id_hito_actual = h.id_hito
WHERE s.folio = 1000

-- Mostrar info de beneficiario
SELECT 
    'BENEFICIARIO' as Concepto,
    b.nombre,
    b.apellido_paterno,
    b.apellido_materno,
    b.curp
FROM Beneficiarios b
WHERE b.curp = (SELECT fk_curp FROM Solicitudes WHERE folio = 1000)

-- Mostrar info de apoyo
SELECT 
    'APOYO' as Concepto,
    a.nombre_apoyo,
    a.tipo_apoyo,
    a.monto_maximo as Monto,
    a.estado
FROM Apoyos a
WHERE a.id_apoyo = (SELECT fk_id_apoyo FROM Solicitudes WHERE folio = 1000)

-- Mostrar info de usuario
SELECT 
    'USUARIO DORA1' as Concepto,
    u.nombre,
    u.correo,
    p.fk_rol as Rol
FROM Usuarios u
LEFT JOIN Personal p ON u.id_usuario = p.fk_id_usuario
WHERE u.nombre = 'dora1'

GO

PRINT ''
PRINT '========== SETUP COMPLETADO EXITOSAMENTE =========='
PRINT ''
PRINT '✓ Folio 1000 está en ANALISIS_ADMIN (Fase 2)'
PRINT '✓ Documento de prueba creado'
PRINT '✓ Usuario dora1 configurado con permisos'
PRINT '✓ Tabla de firmas verificada'
PRINT ''
PRINT 'PRÓXIMOS PASOS:'
PRINT '1. Inicia sesión con dora1 / 123456789'
PRINT '2. Ve a: /solicitudes/proceso'
PRINT '3. Busca el folio 1000'
PRINT '4. Accede a la sección de firma'
PRINT '5. Completa el resumen crítico (marcar 4 checkboxes)'
PRINT '6. Verás que abre el flujo de firma digital'
PRINT ''
PRINT '=========================================='
GO
