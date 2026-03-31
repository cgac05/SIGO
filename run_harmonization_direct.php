<?php
/**
 * Direct database harmonization using individual statements
 */

require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 ARMONIZACIÓN BD SIGO - EJECUCIÓN DIRECTA\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$successCount = 0;
$status = [];

// PARTE 1: Documentos_Expediente - Agregar campos
echo "PARTE 1: Documentos_Expediente\n";
echo "───────────────────────────────\n";

$commands = [
    "ALTER TABLE Documentos_Expediente ADD origen_carga NVARCHAR(50) DEFAULT 'beneficiario';" => "origen_carga",
    "ALTER TABLE Documentos_Expediente ADD cargado_por INT NULL;" => "cargado_por",
    "ALTER TABLE Documentos_Expediente ADD justificacion_carga_fria NVARCHAR(MAX) NULL;" => "justificacion_carga_fria",
    "ALTER TABLE Documentos_Expediente ADD marca_agua_aplicada BIT DEFAULT 0;" => "marca_agua_aplicada",
    "ALTER TABLE Documentos_Expediente ADD qr_seguimiento NVARCHAR(510) NULL;" => "qr_seguimiento",
];

foreach ($commands as $sql => $fieldName) {
    try {
        DB::statement($sql);
        echo "✓ Campo $fieldName añadido\n";
        $successCount++;
        $status["Documentos_Expediente.$fieldName"] = true;
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'ya existe') !== false) {
            echo "⚠️  Campo $fieldName ya existe\n";
            $status["Documentos_Expediente.$fieldName"] = true;
        } else {
            echo "✗ Error en $fieldName: " . substr($e->getMessage(), 0, 80) . "\n";
            $status["Documentos_Expediente.$fieldName"] = false;
        }
    }
}

// FK para cargado_por
try {
    DB::statement("ALTER TABLE Documentos_Expediente ADD CONSTRAINT FK_Documentos_cargado_por FOREIGN KEY (cargado_por) REFERENCES Usuarios(id_usuario);");
    echo "✓ Foreign Key cargado_por creada\n";
    $successCount++;
} catch (\Exception $e) {
    if (strpos($e->getMessage(), 'already exists') === false) {
        echo "⚠️  FK cargado_por ya existe o error menor\n";
    }
}

// PARTE 2: Apoyos - Agregar campos
echo "\nPARTE 2: Apoyos\n";
echo "───────────────\n";

$commands = [
    "ALTER TABLE Apoyos ADD tipo_apoyo_detallado NVARCHAR(50) NULL;" => "tipo_apoyo_detallado",
    "ALTER TABLE Apoyos ADD requiere_inventario BIT DEFAULT 0;" => "requiere_inventario",
    "ALTER TABLE Apoyos ADD costo_promedio_unitario MONEY NULL;" => "costo_promedio_unitario",
];

foreach ($commands as $sql => $fieldName) {
    try {
        DB::statement($sql);
        echo "✓ Campo $fieldName añadido\n";
        $successCount++;
        $status["Apoyos.$fieldName"] = true;
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'already exists') !== false || strpos($e->getMessage(), 'ya existe') !== false) {
            echo "⚠️  Campo $fieldName ya existe\n";
            $status["Apoyos.$fieldName"] = true;
        } else {
            echo "✗ Error en $fieldName: " . substr($e->getMessage(), 0, 80) . "\n";
            $status["Apoyos.$fieldName"] = false;
        }
    }
}

// PARTE 3: Nuevos estados
echo "\nPARTE 3: Nuevos Estados\n";
echo "──────────────────────\n";

$states = [
    6 => 'Expediente Creado',
    7 => 'Documentos Cargados Admin',
    8 => 'Consentido Beneficiario',
    9 => 'Rechazado por Beneficiario',
];

foreach ($states as $id => $name) {
    try {
        DB::statement(
            "INSERT INTO Cat_EstadosSolicitud (id_estado, nombre_estado) VALUES ($id, ?)
             IF @@ROWCOUNT = 0 UPDATE Cat_EstadosSolicitud SET nombre_estado = ? WHERE id_estado = $id",
            [$name, $name]
        );
        echo "✓ Estado $id: $name agregado/actualizado\n";
        $successCount++;
        $status["Estado.$id"] = true;
    } catch (\Exception $e) {
        if (strpos($e->getMessage(), 'PRIMARY KEY') !== false || strpos($e->getMessage(), 'UNIQUE') !== false) {
            // Already exists, try update
            try {
                DB::statement("UPDATE Cat_EstadosSolicitud SET nombre_estado = ? WHERE id_estado = ?", [$name, $id]);
                echo "⚠️  Estado $id actualizado (ya existía)\n";
                $status["Estado.$id"] = true;
            } catch (\Exception $e2) {
                echo "✗ Error en estado $id: " . substr($e->getMessage(), 0, 80) . "\n";
                $status["Estado.$id"] = false;
            }
        } else {
            echo "✗ Error en estado $id: " . substr($e->getMessage(), 0, 80) . "\n";
            $status["Estado.$id"] = false;
        }
    }
}

// PARTE 4 & 5: Tablas Carga Fría
echo "\nPARTE 4 & 5: Tablas Carga Fría\n";
echo "──────────────────────────────\n";

$tables = [
    'auditorias_carga_fria' => "
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
        CREATE INDEX IX_auditorias_beneficiario ON auditorias_carga_fria(fk_id_beneficiario);
        CREATE INDEX IX_auditorias_admin ON auditorias_carga_fria(fk_id_admin);
    ",
    'consentimientos_carga_fria' => "
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
    "
];

foreach ($tables as $tableName => $sql) {
    try {
        // Check if table exists first
        $exists = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?", [$tableName]);
        if (!empty($exists)) {
            echo "⚠️  Tabla $tableName ya existe\n";
            $status["Tabla.$tableName"] = true;
        } else {
            DB::statement($sql);
            echo "✓ Tabla $tableName creada\n";
            $successCount++;
            $status["Tabla.$tableName"] = true;
        }
    } catch (\Exception $e) {
        echo "✗ Error en tabla $tableName: " . substr($e->getMessage(), 0, 80) . "\n";
        $status["Tabla.$tableName"] = false;
    }
}

// PARTE 6: Sistema de Inventario (9 tablas)
echo "\nPARTE 6: Sistema de Inventario\n";
echo "──────────────────────────────\n";

$inventoryTables = [
    'inventario_material' => "
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
    ",
    'componentes_apoyo' => "
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
            FOREIGN KEY (fk_id_inventario) REFERENCES inventario_material(id_inventario) ON DELETE NO ACTION
        );
    ",
    'ordenes_compra_interno' => "
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
    ",
    'recepciones_material' => "
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
    ",
    'facturas_compra' => "
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
    ",
    'movimientos_inventario' => "
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
    ",
    'salidas_beneficiarios' => "
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
    ",
    'detalle_salida_beneficiarios' => "
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
    ",
    'auditorias_salida_material' => "
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
    "
];

foreach ($inventoryTables as $tableName => $sql) {
    try {
        $exists = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?", [$tableName]);
        if (!empty($exists)) {
            echo "⚠️  Tabla $tableName ya existe\n";
            $status["Tabla.$tableName"] = true;
        } else {
            DB::statement($sql);
            echo "✓ Tabla $tableName creada\n";
            $successCount++;
            $status["Tabla.$tableName"] = true;
        }
    } catch (\Exception $e) {
        echo "✗ Error en tabla $tableName: " . substr($e->getMessage(), 0, 80) . "\n";
        $status["Tabla.$tableName"] = false;
    }
}

// PARTE 7: Google Drive & LGPDP
echo "\nPARTE 7: Google Drive & LGPDP\n";
echo "──────────────────────────────\n";

$lgpdpTables = [
    'politicas_retencion_datos' => "
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
    ",
    'solicitudes_arco' => "
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
    "
];

foreach ($lgpdpTables as $tableName => $sql) {
    try {
        $exists = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME = ?", [$tableName]);
        if (!empty($exists)) {
            echo "⚠️  Tabla $tableName ya existe\n";
            $status["Tabla.$tableName"] = true;
        } else {
            DB::statement($sql);
            echo "✓ Tabla $tableName creada\n";
            $successCount++;
            $status["Tabla.$tableName"] = true;
        }
    } catch (\Exception $e) {
        echo "✗ Error en tabla $tableName: " . substr($e->getMessage(), 0, 80) . "\n";
        $status["Tabla.$tableName"] = false;
    }
}

// VALIDACIÓN FINAL
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ VALIDACIÓN FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

echo "DOCUMENTOS_EXPEDIENTE:\n";
$docFields = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Documentos_Expediente'");
$docFieldNames = array_map(fn($f) => $f->COLUMN_NAME, $docFields);
foreach (['origen_carga', 'cargado_por', 'justificacion_carga_fria', 'marca_agua_aplicada', 'qr_seguimiento'] as $field) {
    $check = in_array($field, $docFieldNames) ? '✓' : '✗';
    echo "  $check $field\n";
}

echo "\nAPOYOS:\n";
$apoFields = DB::select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Apoyos'");
$apoFieldNames = array_map(fn($f) => $f->COLUMN_NAME, $apoFields);
foreach (['tipo_apoyo_detallado', 'requiere_inventario', 'costo_promedio_unitario'] as $field) {
    $check = in_array($field, $apoFieldNames) ? '✓' : '✗';
    echo "  $check $field\n";
}

echo "\nESTADOS:\n";
$states = DB::select("SELECT * FROM Cat_EstadosSolicitud ORDER BY id_estado");
foreach ($states as $state) {
    echo "  ✓ ID {$state->id_estado}: {$state->nombre_estado}\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🎉 ¡ARMONIZACIÓN COMPLETADA!\n";
echo "═══════════════════════════════════════════════════════════════\n";
