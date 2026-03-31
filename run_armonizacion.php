<?php
/**
 * Ejecutar Script de Armonización BD SIGO
 * Ejecuta el archivo SQL directamente contra la base de datos
 */

// Bootstrap Laravel
require_once __DIR__ . '/bootstrap/app.php';

use Illuminate\Support\Facades\DB;

$sqlFile = __DIR__ . '/ARMONIZACION_BD_SIGO.sql';

if (!file_exists($sqlFile)) {
    echo "❌ Error: Archivo $sqlFile no encontrado\n";
    exit(1);
}

echo "═══════════════════════════════════════════════════════════════\n";
echo "📊 EJECUTANDO ARMONIZACIÓN BD SIGO\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

$sqlContent = file_get_contents($sqlFile);

// Dividir por GO (SQL Server)
$statements = preg_split('/^\s*GO\s*$/m', $sqlContent);

$executed = 0;
$errors = [];

foreach ($statements as $idx => $statement) {
    $statement = trim($statement);
    
    // Ignorar líneas vacías y comentarios iniciales
    if (empty($statement) || strpos($statement, '--') === 0) {
        continue;
    }
    
    // Remover comentarios de bloque
    $statement = preg_replace('/^--.*$/m', '', $statement);
    $statement = trim($statement);
    
    if (empty($statement)) {
        continue;
    }
    
    try {
        // Ejecutar usando PDO raw para soportar multiple statements
        DB::connection('sqlsrv')->statement($statement);
        $executed++;
        echo "✅ Ejecutado bloque $idx\n";
    } catch (\Exception $e) {
        $errorMsg = $e->getMessage();
        
        // Ignorar errores comunes que no son críticos
        if (strpos($errorMsg, 'ya existe') !== false || 
            strpos($errorMsg, 'already') !== false ||
            strpos($errorMsg, 'UNIQUE') !== false) {
            echo "⚠️  Bloque $idx: Elemento ya existe (ignorado)\n";
        } else {
            $errors[] = "Bloque $idx: " . substr($errorMsg, 0, 150);
            echo "❌ Error en bloque $idx: " . substr($errorMsg, 0, 100) . "\n";
        }
    }
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "📈 RESUMEN DE EJECUCIÓN\n";
echo "═══════════════════════════════════════════════════════════════\n";
echo "Bloques ejecutados: $executed\n";
echo "Errores críticos: " . count($errors) . "\n";

if (!empty($errors)) {
    echo "\n📋 Errores encontrados:\n";
    foreach ($errors as $error) {
        echo "  • $error\n";
    }
}

// Validación final
echo "\n═══════════════════════════════════════════════════════════════\n";
echo "✅ VALIDACIÓN FINAL\n";
echo "═══════════════════════════════════════════════════════════════\n\n";

// Verificar campos en Documentos_Expediente
try {
    $columns = DB::connection('sqlsrv')
        ->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Documentos_Expediente' ORDER BY ORDINAL_POSITION");
    
    $columnNames = array_map(fn($col) => $col->COLUMN_NAME, $columns);
    $requiredFields = ['origen_carga', 'cargado_por', 'justificacion_carga_fria', 'marca_agua_aplicada', 'qr_seguimiento'];
    
    echo "✅ DOCUMENTOS_EXPEDIENTE:\n";
    foreach ($requiredFields as $field) {
        $exists = in_array($field, $columnNames) ? '✓' : '✗';
        echo "   $exists $field\n";
    }
} catch (\Exception $e) {
    echo "❌ Error verificando Documentos_Expediente: " . substr($e->getMessage(), 0, 100) . "\n";
}

// Verificar campos en Apoyos
try {
    $columns = DB::connection('sqlsrv')
        ->select("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = 'Apoyos' ORDER BY ORDINAL_POSITION");
    
    $columnNames = array_map(fn($col) => $col->COLUMN_NAME, $columns);
    $requiredFields = ['tipo_apoyo_detallado', 'requiere_inventario', 'costo_promedio_unitario'];
    
    echo "\n✅ APOYOS:\n";
    foreach ($requiredFields as $field) {
        $exists = in_array($field, $columnNames) ? '✓' : '✗';
        echo "   $exists $field\n";
    }
} catch (\Exception $e) {
    echo "❌ Error verificando Apoyos: " . substr($e->getMessage(), 0, 100) . "\n";
}

// Verificar nuevos estados
try {
    $states = DB::connection('sqlsrv')
        ->select("SELECT id_estado, nombre_estado FROM Cat_EstadosSolicitud ORDER BY id_estado");
    
    echo "\n✅ ESTADOS EN CAT_ESTADOSSOLICITUD:\n";
    foreach ($states as $state) {
        echo "   ID {$state->id_estado}: {$state->nombre_estado}\n";
    }
} catch (\Exception $e) {
    echo "❌ Error verificando estados: " . substr($e->getMessage(), 0, 100) . "\n";
}

// Verificar tablas de inventario
try {
    $tables = DB::connection('sqlsrv')
        ->select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'dbo' AND TABLE_NAME IN (
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
        ) ORDER BY TABLE_NAME");
    
    $tableNames = array_map(fn($t) => $t->TABLE_NAME, $tables);
    
    echo "\n✅ TABLAS DE INVENTARIO Y CARGA FRÍA:\n";
    $expectedTables = [
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
    ];
    
    foreach ($expectedTables as $table) {
        $exists = in_array($table, $tableNames) ? '✓' : '✗';
        echo "   $exists $table\n";
    }
} catch (\Exception $e) {
    echo "❌ Error verificando tablas: " . substr($e->getMessage(), 0, 100) . "\n";
}

echo "\n═══════════════════════════════════════════════════════════════\n";
echo "🎉 ¡ARMONIZACIÓN COMPLETADA!\n";
echo "═══════════════════════════════════════════════════════════════\n";
