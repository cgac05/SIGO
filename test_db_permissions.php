<?php
require_once __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "Testing database connection and permissions...\n\n";

// Test 1: Simple SELECT
echo "1. Testing table existence:\n";
try {
    $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_NAME IN ('Documentos_Expediente', 'Apoyos', 'Usuarios') ORDER BY TABLE_NAME");
    foreach ($tables as $t) {
        echo "   ✓ {$t->TABLE_NAME}\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Check if we can modify
echo "\n2. Testing ALTER permissions:\n";
try {
    // Try to add a test column
    DB::statement("ALTER TABLE Apoyos ADD _test_column_please_ignore NVARCHAR(50) NULL");
    
    // If successful, remove it
    DB::statement("ALTER TABLE Apoyos DROP COLUMN _test_column_please_ignore");
    echo "   ✓ ALTER TABLE permissions OK\n";
} catch (\Exception $e) {
    $msg = $e->getMessage();
    if (strpos($msg, 'already exists') !== false || strpos($msg, 'ya existe') !== false) {
        echo "   ⚠️  Column already exists (trying to remove)...\n";
        try {
            DB::statement("ALTER TABLE Apoyos DROP COLUMN _test_column_please_ignore");
            echo "   ✓ Column removed successfully\n";
        } catch (\Exception $e2) {
            echo "   ✗ Could not remove: " . substr($e2->getMessage(), 0, 100) . "\n";
        }
    } else {
        echo "   ✗ Error: " . substr($msg, 0, 150) . "\n";
    }
}

// Test 3: Try the actual alterations again with better error reporting
echo "\n3. Attempting actual field additions:\n";

// Choose a table to test
$sqlStatements = [
    "ALTER TABLE Documentos_Expediente ADD origen_carga_v2 NVARCHAR(50) DEFAULT 'beneficiario'" => "origen_carga_v2",
    "ALTER TABLE Apoyos ADD tipo_apoyo_detallado_v2 NVARCHAR(50) NULL" => "tipo_apoyo_detallado_v2",
];

foreach ($sqlStatements as $sql => $fieldName) {
    try {
        DB::statement($sql);
        echo "   ✓ Field added: $fieldName\n";
        
        // Clean up immediately
        try {
            $tableName = strpos($fieldName, 'Documentos') !== false ? 'Documentos_Expediente' : 'Apoyos';
            DB::statement("ALTER TABLE $tableName DROP COLUMN $fieldName");
            echo "     (cleaned up)\n";
        } catch (\Exception $e) {
            echo "     ⚠️  Could not clean up\n";
        }
    } catch (\Exception $e) {
        echo "   ✗ Error: " . substr($e->getMessage(), 0, 150) . "\n";
    }
}

// Test 4: Check CREATE TABLE permissions
echo "\n4. Testing CREATE TABLE permissions:\n";
$testTableSql = "
    CREATE TABLE test_table_please_delete_2026 (
        id INT IDENTITY(1,1) PRIMARY KEY,
        test_field NVARCHAR(50)
    )
";

try {
    DB::statement($testTableSql);
    echo "   ✓ CREATE TABLE permissions OK\n";
    
    // Clean up
    try {
        DB:: statement("DROP TABLE test_table_please_delete_2026");
        echo "     (cleaned up)\n";
    } catch (\Exception $e) {
        echo "     (cleanup failed - leaving table)\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . substr($e->getMessage(), 0, 150) . "\n";
}

// Test 5: Current user info
echo "\n5. Database connection info:\n";
try {
    $info = DB::select("SELECT CURRENT_USER as current_user, @@SERVERNAME as server_name, DB_NAME() as database_name");
    foreach ($info as $row) {
        echo "   User: " . $row->current_user . "\n";
        echo "   Server: " . $row->server_name . "\n";
        echo "   Database: " . $row->database_name . "\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . substr($e->getMessage(), 0, 100) . "\n";
}

echo "\nDone.\n";
