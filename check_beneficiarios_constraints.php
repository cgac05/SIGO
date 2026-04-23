<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== CHECK: UNIQUE Constraints en Beneficiarios ===\n\n";

// Conectar con Windows Auth para tener permisos
$pdo = new PDO(
    "sqlsrv:server=localhost;database=BD_SIGO;TrustServerCertificate=yes",
    null, 
    null,
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$result = $pdo->query("
    SELECT 
        tc.CONSTRAINT_NAME,
        tc.CONSTRAINT_TYPE,
        kcu.COLUMN_NAME,
        kcu.ORDINAL_POSITION
    FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS tc
    JOIN INFORMATION_SCHEMA.KEY_COLUMN_USAGE kcu 
        ON tc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME
        AND tc.TABLE_SCHEMA = kcu.TABLE_SCHEMA
        AND tc.TABLE_NAME = kcu.TABLE_NAME
    WHERE tc.TABLE_NAME = 'Beneficiarios'
    AND tc.TABLE_SCHEMA = 'dbo'
    AND tc.CONSTRAINT_TYPE IN ('UNIQUE', 'PRIMARY KEY')
    ORDER BY tc.CONSTRAINT_NAME, kcu.ORDINAL_POSITION
");

$rows = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "Constraint: {$row['CONSTRAINT_NAME']}\n";
    echo "  Type: {$row['CONSTRAINT_TYPE']}\n";
    echo "  Column: {$row['COLUMN_NAME']}\n\n";
}

echo "\n=== CHECK: Índices en Beneficiarios ===\n\n";

$result = $pdo->query("
    SELECT 
        i.name AS IndexName,
        c.name AS ColumnName,
        i.is_unique AS IsUnique,
        i.is_unique_constraint AS IsUniqueConstraint,
        i.filter_definition AS FilterDefinition
    FROM sys.indexes i
    JOIN sys.index_columns ic ON i.object_id = ic.object_id AND i.index_id = ic.index_id
    JOIN sys.columns c ON ic.object_id = c.object_id AND ic.column_id = c.column_id
    JOIN sys.tables t ON i.object_id = t.object_id
    WHERE t.name = 'Beneficiarios'
    AND t.schema_id = SCHEMA_ID('dbo')
    ORDER BY i.name, ic.key_ordinal
");

$rows = $result->fetchAll(PDO::FETCH_ASSOC);
foreach ($rows as $row) {
    echo "Index: {$row['IndexName']}\n";
    echo "  Column: {$row['ColumnName']}\n";
    echo "  Is Unique: " . ($row['IsUnique'] ? 'YES' : 'NO') . "\n";
    echo "  Is Unique Constraint: " . ($row['IsUniqueConstraint'] ? 'YES' : 'NO') . "\n";
    echo "  Filter Definition: " . ($row['FilterDefinition'] ?? 'NONE') . "\n\n";
}
?>
