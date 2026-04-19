<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "=== TEST: Validación de Teléfono con Formato ===\n\n";

// Setup
Auth::loginUsingId(6);

// Test casos de validación
$test_cases = [
    ['telefono' => '(311) 555-1234', 'expected' => true, 'desc' => 'Formato correcto'],
    ['telefono' => '(123) 456-7890', 'expected' => true, 'desc' => 'Formato correcto'],
    ['telefono' => '3115551234', 'expected' => false, 'desc' => 'Sin formato'],
    ['telefono' => '311-555-1234', 'expected' => false, 'desc' => 'Formato incorrecto'],
    ['telefono' => '+311 555 1234', 'expected' => false, 'desc' => 'Formato con +'],
    ['telefono' => '', 'expected' => true, 'desc' => 'Campo vacío (nullable)'],
    ['telefono' => null, 'expected' => true, 'desc' => 'null (nullable)'],
];

echo "Validando patrones de teléfono:\n\n";

foreach ($test_cases as $test) {
    $telefono = $test['telefono'];
    $patrón = '/^\(\d{3}\) \d{3}-\d{4}$/';
    
    // Validar si es nullable o si cumple el patrón
    $valido = empty($telefono) || preg_match($patrón, $telefono);
    $resultado = $valido === $test['expected'] ? '✅' : '❌';
    
    echo "$resultado {$test['desc']}\n";
    echo "   Teléfono: " . ($telefono ? "'$telefono'" : 'vacío/null') . "\n";
    echo "   Esperado: " . ($test['expected'] ? 'VÁLIDO' : 'INVÁLIDO') . " | Obtenido: " . ($valido ? 'VÁLIDO' : 'INVÁLIDO') . "\n\n";
}

echo "✅ PRUEBAS COMPLETADAS\n\n";
echo "Formato esperado: (XXX) XXX-XXXX\n";
echo "Ejemplo: (311) 555-1234\n";
?>
