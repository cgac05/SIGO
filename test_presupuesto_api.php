<?php
/**
 * Test simple para verificar si el API de presupuesto funciona
 * Accede a: http://localhost/SIGO/public/test_presupuesto_api.php
 */

// Cargar Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(\Illuminate\Contracts\Http\Kernel::class);

// Crear request simulado
$request = \Illuminate\Http\Request::create('/admin/presupuesto/api/datos-dashboard', 'GET');
$request = $kernel->handle($request);

// Si es JSON, mostrar bonito
if ($request->headers->get('content-type') === 'application/json') {
    echo "<pre>";
    echo json_encode(json_decode($request->getContent()), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    echo "</pre>";
} else {
    echo "<pre>";
    echo $request->getContent();
    echo "</pre>";
}
?>
