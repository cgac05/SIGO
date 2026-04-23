<?php
// Test script to verify the fix for datetime insertion

require_once 'bootstrap/app.php';

use Illuminate\Support\Facades\DB;
use App\Models\Documento;
use App\Models\CadenaDigitalDocumento;

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$kernel->bootstrap();

echo "=== Test: Documento and CadenaDigitalDocumento Creation ===\n\n";

try {
    // Get an existing folio for testing (Caso A)
    $folioTest = DB::table('Solicitudes')
        ->where('origen_solicitud', 'admin_caso_a')
        ->first(['folio']);
    
    if (!$folioTest) {
        echo "❌ No test folio found. Cannot proceed with test.\n";
        exit(1);
    }
    
    $folio = $folioTest->folio;
    echo "✓ Using test folio: $folio\n";
    
    // Start transaction
    DB::beginTransaction();
    
    // Test 1: Create Documento with DB::raw('GETDATE()')
    echo "\n1. Testing Documento creation with DB::raw('GETDATE()')...\n";
    
    $documento = Documento::create([
        'fk_folio' => $folio,
        'fk_id_tipo_doc' => 2,
        'ruta_archivo' => 'test/test-documento-' . time() . '.pdf',
        'origen_archivo' => 'admin_caso_a',
        'id_admin' => 6,  // Admin ID from error
        'estado_validacion' => 'PENDIENTE',
        'fecha_carga' => DB::raw('GETDATE()'),
    ]);
    
    echo "✓ Documento created successfully with ID: " . $documento->id_doc . "\n";
    echo "  - fecha_carga: " . ($documento->fecha_carga ?? 'NULL') . "\n";
    
    // Test 2: Create CadenaDigitalDocumento with DB::raw('GETDATE()')
    echo "\n2. Testing CadenaDigitalDocumento creation with DB::raw('GETDATE()')...\n";
    
    $cadenaDigital = CadenaDigitalDocumento::create([
        'fk_id_documento' => $documento->id_doc,
        'folio' => $folio,
        'hash_actual' => '7a910511489f960e5e9ebb7c5df6b819dda3fafb66b116a93a1053e02e0653b0',
        'hash_anterior' => null,
        'admin_creador' => 6,
        'timestamp_creacion' => DB::raw('GETDATE()'),
        'firma_hmac' => '0b2dff4e9997642b4e112920621c0e748079620455e70e621ebae1b5bcb76655',
        'razon_cambio' => 'Test document creation',
    ]);
    
    echo "✓ CadenaDigitalDocumento created successfully with ID: " . $cadenaDigital->id_cadena . "\n";
    echo "  - timestamp_creacion: " . ($cadenaDigital->timestamp_creacion ?? 'NULL') . "\n";
    
    // Rollback to avoid polluting database
    DB::rollBack();
    echo "\n✓ Transaction rolled back (test data not saved)\n";
    echo "\n✅ All tests passed! The fix should work.\n";
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "\n❌ Test failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(1);
}
?>
