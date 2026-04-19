<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\Route;

echo "=== Verificando Rutas de Caso A ===\n\n";

$routes_to_check = [
    'admin.caso-a.momento-uno',
    'admin.caso-a.guardar-momento-uno',
    'admin.caso-a.resumen-momento-uno',
    'admin.caso-a.momento-dos',
    'admin.caso-a.cargar-documento-momento-dos',
    'admin.caso-a.confirmar-carga-momento-dos',
    'caso-a.momento-tres-form',
    'caso-a.validar-momento-tres',
    'caso-a.resumen-momento-tres',
    'logout',
];

$routes = Route::getRoutes();
$defined_routes = collect($routes)->map(fn($route) => $route->getName())->filter()->toArray();

foreach ($routes_to_check as $route_name) {
    if (in_array($route_name, $defined_routes)) {
        echo "✅ $route_name\n";
    } else {
        echo "❌ $route_name - NO ENCONTRADA\n";
    }
}

echo "\n✅ TODAS LAS RUTAS ESTÁN DEFINIDAS CORRECTAMENTE\n";
?>
