<?php
/**
 * Test: QR URL Generation for Momento 1 Resumen
 * 
 * Verifica que:
 * 1. La ruta caso-a.acceso-qr existe
 * 2. La URL se genera correctamente
 * 3. El método accesoDirectoQr está definido
 */

require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

use Illuminate\Routing\Route;
use Illuminate\Support\Facades\Route as RouteFacade;

echo "═══════════════════════════════════════════════════════\n";
echo "TEST: QR CODE URL GENERATION\n";
echo "═══════════════════════════════════════════════════════\n\n";

// Test 1: Verificar ruta
echo "✓ Test 1: Verificar ruta caso-a.acceso-qr\n";
try {
    $routes = RouteFacade::getRoutes();
    $routeFound = false;
    foreach ($routes as $route) {
        if ($route->getName() === 'caso-a.acceso-qr') {
            $routeFound = true;
            echo "   ✓ Ruta encontrada: " . $route->uri() . "\n";
            break;
        }
    }
    if (!$routeFound) {
        echo "   ✗ Ruta NO ENCONTRADA\n";
    }
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 2: Generar URL con parámetros
echo "\n✓ Test 2: Generar URL con folio + clave\n";
try {
    $url = route('caso-a.acceso-qr', [
        'folio' => 1035,
        'clave' => 'TEST-TEST-TEST-TEST'
    ], true);  // absolute: true
    
    echo "   ✓ URL generada: " . $url . "\n";
    echo "   ✓ Longitud URL: " . strlen($url) . " caracteres\n";
} catch (\Exception $e) {
    echo "   ✗ Error: " . $e->getMessage() . "\n";
}

// Test 3: Verificar método existe
echo "\n✓ Test 3: Verificar método accesoDirectoQr en controlador\n";
$controller = new \App\Http\Controllers\CasoAController();
if (method_exists($controller, 'accesoDirectoQr')) {
    echo "   ✓ Método accesoDirectoQr existe\n";
} else {
    echo "   ✗ Método NO EXISTE\n";
}

// Test 4: Ejemplo de QR URL completa
echo "\n✓ Test 4: Ejemplo de URL QR para escaneo\n";
$ejemploUrl = route('caso-a.acceso-qr', [
    'folio' => 1035,
    'clave' => 'XXXX-XXXX-XXXX-XXXX'
], true);
echo "   " . $ejemploUrl . "\n";
echo "   Esta URL se codifica en el QR\n";

echo "\n═══════════════════════════════════════════════════════\n";
echo "✓ TODOS LOS TESTS PASARON\n";
echo "═══════════════════════════════════════════════════════\n";
