<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Fixing UNIQUE constraint on fk_id_usuario ===\n\n";

// Conectar con Windows Auth para tener permisos
$pdo = new PDO(
    "sqlsrv:server=localhost;database=BD_SIGO;TrustServerCertificate=yes",
    null, 
    null,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

try {
    // 1. Eliminar el constraint UNIQUE
    echo "1. Eliminando constraint UNIQUE actual...\n";
    $pdo->exec("ALTER TABLE dbo.[Beneficiarios] DROP CONSTRAINT [UQ__Benefici__1698AC3A0A5A23D3]");
    echo "   ✅ Constraint eliminado\n\n";
    
    // 2. Crear índice UNIQUE FILTRADO (permite múltiples NULLs)
    echo "2. Creando índice UNIQUE FILTRADO (WHERE fk_id_usuario IS NOT NULL)...\n";
    $pdo->exec("
        CREATE UNIQUE INDEX UQ_fk_id_usuario_not_null 
        ON dbo.[Beneficiarios] (fk_id_usuario)
        WHERE fk_id_usuario IS NOT NULL
    ");
    echo "   ✅ Índice creado\n\n";
    
    // 3. Verificar
    echo "3. Verificando cambios...\n";
    $result = $pdo->query("
        SELECT 
            i.name AS IndexName,
            c.name AS ColumnName,
            i.is_unique AS IsUnique,
            i.filter_definition AS FilterDefinition
        FROM sys.indexes i
        JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
        JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
        JOIN sys.tables t ON i.object_id = t.object_id
        WHERE t.name = 'Beneficiarios'
        AND t.schema_id = SCHEMA_ID('dbo')
        AND i.name LIKE '%fk_id_usuario%'
    ");
    
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        echo "   Index: {$row['IndexName']}\n";
        echo "   Column: {$row['ColumnName']}\n";
        echo "   Is Unique: " . ($row['IsUnique'] ? 'YES' : 'NO') . "\n";
        echo "   Filter: " . ($row['FilterDefinition'] ?? 'NONE') . "\n";
    }
    
    echo "\n✅✅✅ CONSTRAINT FIXED SUCCESSFULLY ✅✅✅\n\n";
    echo "Ahora puedes insertar múltiples registros con fk_id_usuario = NULL\n";
    echo "PERO solo un único valor no-NULL para cada fk_id_usuario\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n\n";
    echo "SOLUCIÓN MANUAL (SQL Server Management Studio):\n\n";
    echo "-- 1. Eliminar constraint\n";
    echo "ALTER TABLE dbo.[Beneficiarios] DROP CONSTRAINT [UQ__Benefici__1698AC3A0A5A23D3]\n\n";
    echo "-- 2. Crear índice filtrado\n";
    echo "CREATE UNIQUE INDEX UQ_fk_id_usuario_not_null \n";
    echo "ON dbo.[Beneficiarios] (fk_id_usuario)\n";
    echo "WHERE fk_id_usuario IS NOT NULL\n";
}
?>
