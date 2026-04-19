<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\CasoADocumentService;

echo "=== TEST: Beneficiario Parcial (Sin Usuario) ===\n\n";

Auth::loginUsingId(6);  // Admin user

try {
    $service = new CasoADocumentService();
    
    // Simular beneficiario NO registrado (manual entry)
    $datoBeneficiario = (object) [
        'id_usuario' => null,
        'nombre_completo' => 'Juan Pérez López',
        'curp' => 'JUPL' . rand(100000, 999999) . 'HNTVML00',  // CURP aleatorio válido
        'email' => 'juan.perez@ejemplo.com',
        'telefono' => '(311) 555-1234',
    ];
    
    echo "Datos del beneficiario:\n";
    echo "  Nombre: {$datoBeneficiario->nombre_completo}\n";
    echo "  CURP: {$datoBeneficiario->curp}\n";
    echo "  Email: {$datoBeneficiario->email}\n";
    echo "  Teléfono: {$datoBeneficiario->telefono}\n\n";
    
    // Crear expediente para beneficiario NO registrado
    echo "Creando expediente presencial...\n";
    $resultado = $service->crearExpedientePresencial(
        null,  // beneficiario_id = null (no registrado)
        $datoBeneficiario,
        3,  // apoyo_id
        ['CEDULA', 'COMPROBANTE_DOMICILIO'],  // documentos
        6,  // admin_id
        'Registro presencial de beneficiario nuevo'
    );
    
    echo "\n✅ EXPEDIENTE CREADO EXITOSAMENTE:\n";
    echo "  Folio: {$resultado['folio']}\n";
    echo "  Clave Acceso: {$resultado['clave_acceso']}\n";
    echo "  Beneficiario: {$resultado['beneficiario_nombre']}\n";
    echo "  Estado: {$resultado['estado_solicitud']}\n\n";
    
    // Verificar que el beneficiario se creó en BD
    echo "Verificando registro en BD:\n";
    $beneficiario = DB::table('Beneficiarios')
        ->where('curp', $datoBeneficiario->curp)
        ->first();
    
    if ($beneficiario) {
        echo "  ✅ Beneficiario encontrado en Beneficiarios table\n";
        echo "    CURP: {$beneficiario->curp}\n";
        echo "    Nombre: {$beneficiario->nombre}\n";
        echo "    fk_id_usuario: " . ($beneficiario->fk_id_usuario ? $beneficiario->fk_id_usuario : 'NULL') . "\n\n";
    } else {
        echo "  ❌ ERROR: Beneficiario NO encontrado en BD\n\n";
    }
    
    // Verificar solicitud
    echo "Verificando Solicitud:\n";
    $solicitud = DB::table('Solicitudes')->where('folio', $resultado['folio'])->first();
    if ($solicitud) {
        echo "  ✅ Solicitud encontrada\n";
        echo "    Folio: {$solicitud->folio}\n";
        echo "    CURP: {$solicitud->fk_curp}\n";
        echo "    beneficiario_id: " . ($solicitud->beneficiario_id ? $solicitud->beneficiario_id : 'NULL') . "\n\n";
    }
    
    // Verificar clave privada
    echo "Verificando Clave Privada:\n";
    $clave = DB::table('claves_seguimiento_privadas')
        ->where('folio', $resultado['folio'])
        ->first();
    if ($clave) {
        echo "  ✅ Clave privada encontrada\n";
        echo "    Folio: {$clave->folio}\n";
        echo "    Clave: {$clave->clave_alfanumerica}\n";
        echo "    beneficiario_id: " . ($clave->beneficiario_id ? $clave->beneficiario_id : 'NULL') . "\n\n";
    }
    
    echo "✅ PRUEBA COMPLETADA EXITOSAMENTE\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n\n";
    echo "SOLUCIÓN:\n";
    echo "Ejecuta en SQL Server Management Studio:\n";
    echo "  ALTER TABLE dbo.[Beneficiarios] ALTER COLUMN [fk_id_usuario] INT NULL;\n";
    echo "  ALTER TABLE dbo.[claves_seguimiento_privadas] ALTER COLUMN [beneficiario_id] INT NULL;\n";
}
?>
