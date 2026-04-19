<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== Direct SQL Execution Test ===\n\n";

try {
    // Test 1: List all tables
    echo "1. Testing table enumeration:\n";
    $tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA='dbo'");
    foreach ($tables as $t) {
        if (strpos($t->TABLE_NAME, 'Beneficiarios') !== false) {
            echo "   ✅ Found: {$t->TABLE_NAME}\n";
        }
    }
    
    // Test 2: Try to describe Beneficiarios
    echo "\n2. Describe Beneficiarios:\n";
    $desc = DB::select("EXEC sp_help 'dbo.Beneficiarios'");
    echo "   ✅ Got " . count($desc) . " rows\n";
    
    // Test 3: Actual ALTER
    echo "\n3. Attempting ALTER TABLE:\n";
    DB::statement('ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN fk_id_usuario INT NULL');
    echo "   ✅ ALTER TABLE succeeded!\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
