<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;

echo "=== TEST: Simular exactamente lo que el usuario hizo ===\n\n";

// Simular que el admin está autenticado
Auth::loginUsingId(6);  // Admin ID 6

// 1. Hacer búsqueda AJAX como el usuario
echo "1. BÚSQUEDA AJAX por CURP:\n";
$curpBuscado = 'AICC050509HNTVMHA5';
$resultadosAJAX = DB::table('Beneficiarios')
    ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
    ->where(function ($q) use ($curpBuscado) {
        $q->where('Beneficiarios.curp', 'LIKE', "%$curpBuscado%")
          ->orWhere('Beneficiarios.nombre', 'LIKE', "%$curpBuscado%")
          ->orWhere('Usuarios.email', 'LIKE', "%$curpBuscado%");
    })
    ->select(
        'Beneficiarios.curp',
        'Beneficiarios.nombre',
        'Beneficiarios.apellido_paterno',
        'Beneficiarios.apellido_materno',
        'Usuarios.id_usuario as fk_id_usuario',
        'Usuarios.email'
    )
    ->limit(10)
    ->get();

if ($resultadosAJAX->count() > 0) {
    echo "   ✓ Encontrado(s) " . $resultadosAJAX->count() . " resultado(s)\n";
    $beneficiarioSeleccionado = $resultadosAJAX[0];
    echo "   - CURP: " . $beneficiarioSeleccionado->curp . "\n";
    echo "   - Nombre: " . $beneficiarioSeleccionado->nombre . "\n";
    echo "   - Usuario ID: " . $beneficiarioSeleccionado->fk_id_usuario . "\n\n";
} else {
    echo "   ✗ No se encontró nada\n";
    exit;
}

// 2. Simular submit del formulario
echo "2. SIMULACIÓN DE REQUEST POST:\n";
$beneficiario_id = $beneficiarioSeleccionado->fk_id_usuario;

// Validar como beneficiario registrado
$validated = [
    'es_beneficiario_registrado' => '1',
    'beneficiario_id' => $beneficiario_id,
    'apoyo_id' => 3,
    'documentos_listados' => ['CEDULA', 'RFC'],
    'notas' => 'Test presencial'
];

echo "   - Beneficiario ID: " . $validated['beneficiario_id'] . "\n";
echo "   - Es registrado: " . $validated['es_beneficiario_registrado'] . "\n\n";

// 3. Ejecutar lógica del controlador
echo "3. LÓGICA DEL CONTROLADOR:\n";

try {
    // Obtener usuario
    $usuario = \App\Models\User::findOrFail($beneficiario_id);
    echo "   ✓ Usuario encontrado: " . $usuario->email . "\n";
    
    // Obtener beneficiario record
    $beneficiarioRecord = DB::table('Beneficiarios')
        ->where('fk_id_usuario', $beneficiario_id)
        ->select('curp', 'nombre', 'apellido_paterno', 'apellido_materno')
        ->first();
    
    if (!$beneficiarioRecord) {
        throw new \Exception(
            'El usuario "' . $usuario->email . '" existe en el sistema pero NO está registrado como beneficiario.'
        );
    }
    echo "   ✓ Beneficiario record encontrado\n";
    
    if (!$beneficiarioRecord->curp) {
        throw new \Exception(
            'El beneficiario "' . $usuario->email . '" no tiene CURP registrado.'
        );
    }
    echo "   ✓ CURP presente: " . $beneficiarioRecord->curp . "\n";
    
    // Crear objeto beneficiario
    $datoBeneficiario = (object)[
        'id_usuario' => $usuario->id_usuario,
        'nombre_completo' => trim(
            ($beneficiarioRecord->nombre ?? '') . ' ' .
            ($beneficiarioRecord->apellido_paterno ?? '') . ' ' .
            ($beneficiarioRecord->apellido_materno ?? '')
        ),
        'curp' => $beneficiarioRecord->curp,
        'email' => $usuario->email,
        'telefono' => null,
    ];
    
    echo "   ✓ Objeto beneficiario creado correctamente\n";
    echo "   ✓ Nombre completo: " . $datoBeneficiario->nombre_completo . "\n";
    echo "   ✓ CURP: " . $datoBeneficiario->curp . "\n\n";
    
    echo "✅ TODA LA VALIDACIÓN PASÓ EXITOSAMENTE\n";
    echo "\nAhora se procedería a crear el expediente con:\n";
    echo "  - Beneficiario: " . $datoBeneficiario->nombre_completo . "\n";
    echo "  - CURP: " . $datoBeneficiario->curp . "\n";
    echo "  - Email: " . $datoBeneficiario->email . "\n";
    
} catch (\Exception $e) {
    echo "   ❌ ERROR: " . $e->getMessage() . "\n";
}
?>
