<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use App\Services\CasoADocumentService;

echo "=== TEST: Crear expediente + Verificar resumen ===\n\n";

// Setup
Auth::loginUsingId(6);

DB::beginTransaction();

try {
    // 1. Crear expediente presencial
    echo "1. Creando expediente presencial...\n";
    
    $service = new CasoADocumentService();
    
    // Obtener beneficiario con CURP
    $beneficiario_data = DB::select("
        SELECT TOP 1 u.id_usuario, u.email, b.curp, b.nombre, b.apellido_paterno, b.apellido_materno
        FROM Usuarios u
        LEFT JOIN Beneficiarios b ON u.id_usuario = b.fk_id_usuario
        WHERE b.curp IS NOT NULL
    ");
    
    if (empty($beneficiario_data)) {
        throw new \Exception('No hay beneficiarios registrados con CURP');
    }
    
    $ben_data = $beneficiario_data[0];
    $beneficiario_registrado = (object)[
        'id_usuario' => $ben_data->id_usuario,
        'email' => $ben_data->email,
        'curp' => $ben_data->curp,
        'nombre_completo' => trim($ben_data->nombre . ' ' . ($ben_data->apellido_paterno ?? '') . ' ' . ($ben_data->apellido_materno ?? ''))
    ];
    
    $resultado = $service->crearExpedientePresencial(
        $ben_data->id_usuario,
        $beneficiario_registrado,
        3,  // apoyo_id
        ['CEDULA', 'RFC'],
        6,  // admin_id
        'Test expediente para resumen'
    );
    
    echo "   ✓ Expediente creado\n";
    echo "   Folio: " . $resultado['folio'] . "\n";
    echo "   Clave: " . $resultado['clave_acceso'] . "\n\n";
    
    // 2. Simular obtener datos del resumen
    echo "2. Obteniendo datos para mostrar en resumen...\n";
    
    $folio = $resultado['folio'];
    
    // Buscar solicitud
    $solicitud = DB::table('Solicitudes')->where('folio', $folio)->first();
    if (!$solicitud) {
        throw new \Exception('Solicitud no encontrada');
    }
    echo "   ✓ Solicitud encontrada\n";
    echo "   Beneficiario ID: " . $solicitud->beneficiario_id . "\n";
    echo "   CURP: " . $solicitud->fk_curp . "\n\n";
    
    // Buscar clave
    $clave = DB::table('claves_seguimiento_privadas')->where('folio', $folio)->first();
    if (!$clave) {
        throw new \Exception('Clave privada no encontrada');
    }
    echo "   ✓ Clave privada encontrada\n";
    echo "   Beneficiario ID Clave: " . $clave->beneficiario_id . "\n\n";
    
    // Buscar beneficiario
    $beneficiario = DB::table('Beneficiarios')
        ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
        ->where('Beneficiarios.fk_id_usuario', $solicitud->beneficiario_id)
        ->select(
            'Beneficiarios.nombre',
            'Beneficiarios.apellido_paterno',
            'Beneficiarios.apellido_materno',
            'Beneficiarios.telefono',
            'Beneficiarios.curp',
            'Usuarios.email'
        )
        ->first();
    
    if ($beneficiario) {
        echo "   ✓ Beneficiario encontrado\n";
        echo "   Nombre: " . $beneficiario->nombre . " " . $beneficiario->apellido_paterno . "\n";
        echo "   Email: " . $beneficiario->email . "\n\n";
    } else {
        echo "   ⚠ Beneficiario no encontrado\n\n";
    }
    
    // Obtener apoyo
    $apoyo = DB::table('Apoyos')->where('id_apoyo', $solicitud->fk_id_apoyo)->first();
    echo "   ✓ Apoyo encontrado: " . $apoyo->nombre_apoyo . "\n\n";
    
    echo "✅ TODO LISTO PARA MOSTRAR EN RESUMEN\n";
    echo "\nDatos que se mostrarán en la página de resumen:\n";
    echo "  - Folio: $folio\n";
    echo "  - Clave: " . $clave->clave_alfanumerica . "\n";
    echo "  - Beneficiario: " . $beneficiario->nombre . "\n";
    echo "  - Apoyo: " . $apoyo->nombre_apoyo . "\n";
    
    DB::rollBack();
    
} catch (\Exception $e) {
    DB::rollBack();
    echo "❌ ERROR: " . $e->getMessage() . "\n";
}
?>
