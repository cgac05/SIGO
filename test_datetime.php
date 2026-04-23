
use Illuminate\Support\Facades\DB;
use App\Models\Documento;
use App\Models\CadenaDigitalDocumento;

// Get test folio
$folioTest = DB::table('Solicitudes')
    ->where('origen_solicitud', 'admin_caso_a')
    ->first(['folio']);

if ($folioTest) {
    $folio = $folioTest->folio;
    echo "\n✓ Test folio: $folio\n";
    
    DB::beginTransaction();
    
    // Test Documento creation
    $doc = Documento::create([
        'fk_folio' => $folio,
        'fk_id_tipo_doc' => 2,
        'ruta_archivo' => 'test/test-' . time() . '.pdf',
        'origen_archivo' => 'admin_caso_a',
        'id_admin' => 6,
        'estado_validacion' => 'PENDIENTE',
        'fecha_carga' => DB::raw('GETDATE()'),
    ]);
    
    echo "✓ Documento created: ID " . $doc->id_doc . ", fecha_carga: " . $doc->fecha_carga . "\n";
    
    // Test CadenaDigitalDocumento
    $cadena = CadenaDigitalDocumento::create([
        'fk_id_documento' => $doc->id_doc,
        'folio' => $folio,
        'hash_actual' => '7a910511489f960e5e9ebb7c5df6b819dda3fafb66b116a93a1053e02e0653b0',
        'admin_creador' => 6,
        'timestamp_creacion' => DB::raw('GETDATE()'),
        'firma_hmac' => '0b2dff4e9997642b4e112920621c0e748079620455e70e621ebae1b5bcb76655',
        'razon_cambio' => 'Test',
    ]);
    
    echo "✓ CadenaDigitalDocumento created: ID " . $cadena->id_cadena . "\n";
    echo "✅ All tests PASSED!\n";
    
    DB::rollBack();
} else {
    echo "❌ No test folio found\n";
}
