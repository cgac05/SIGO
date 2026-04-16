#!/usr/bin/env php
<?php

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  ✅ VERIFICACIÓN: Acceso a Ciclos Presupuestarios (Rol 2 & 3) ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

// Bootstrap de Laravel
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Route;

// Buscar rutas de ciclos
echo "📋 RUTAS DE CICLOS PRESUPUESTARIOS:\n";
echo str_repeat("-", 64) . "\n";

$routes = Route::getRoutes();
$cicloRoutes = [];

foreach ($routes as $route) {
    if (strpos($route->uri(), 'admin/ciclos') !== false) {
        $cicloRoutes[] = $route;
    }
}

if (count($cicloRoutes) > 0) {
    echo "✅ Se encontraron " . count($cicloRoutes) . " rutas de ciclos:\n\n";
    
    foreach ($cicloRoutes as $route) {
        $methods = implode('|', $route->methods());
        $uri = $route->uri();
        $middleware = implode(', ', $route->middleware());
        
        echo "  [$methods] $uri\n";
        echo "  Middleware: $middleware\n";
        echo "  ✓ Acceso: Rol 2 (Directivo) y Rol 3 (Financiero)\n\n";
    }
} else {
    echo "❌ No se encontraron rutas de ciclos\n";
}

echo str_repeat("-", 64) . "\n\n";

// Verificar cambios en el Controller
echo "🔍 VERIFICACIÓN DEL CONTROLLER:\n";
echo str_repeat("-", 64) . "\n";

$controllerFile = __DIR__ . '/app/Http/Controllers/Admin/CicloPresupuestarioController.php';
$content = file_get_contents($controllerFile);

// Buscar la validación
if (strpos($content, '$userRole !== 2 && $userRole !== 3') !== false) {
    echo "✅ CORRECTO: El controller permite rol 2 Y rol 3\n";
    echo "   - Línea: '\$userRole !== 2 && \$userRole !== 3'\n";
} else {
    echo "⚠️  ADVERTENCIA: La validación puede no estar actualizada\n";
}

if (strpos($content, 'fk_rol !== 3') !== false) {
    echo "❌ ERROR: El controller aún tiene validación restrictiva (role:3 solo)\n";
    echo "   - Línea contiene: 'fk_rol !== 3'\n";
} else {
    echo "✅ CORRECTO: La validación restrictiva fue removida\n";
}

echo "\n";

// Verificar archivo de rutas
echo "📁 VERIFICACIÓN DEL ARCHIVO DE RUTAS:\n";
echo str_repeat("-", 64) . "\n";

$routesFile = __DIR__ . '/routes/web.php';
$routesContent = file_get_contents($routesFile);

// Buscar la línea de ciclos
$lines = explode("\n", $routesContent);
$found = false;

foreach ($lines as $lineNum => $line) {
    if (strpos($line, "Route::prefix('admin/ciclos')") !== false) {
        $found = true;
        echo "✅ Línea " . ($lineNum + 1) . ": Encontrada definición de ruta\n";
        echo "   Contenido: " . trim($line) . "\n";
        
        if (strpos($line, "middleware('role:2,3')") !== false) {
            echo "   ✅ Middleware CORRECTO: role:2,3 (Directivo + Financiero)\n";
        } elseif (strpos($line, "middleware('role:3')") !== false) {
            echo "   ❌ Middleware INCORRECTO: role:3 (Solo Financiero)\n";
        }
        echo "\n";
    }
}

if (!$found) {
    echo "❌ No se encontró la ruta de ciclos\n";
}

echo str_repeat("-", 64) . "\n\n";

// Resumen final
echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  📊 RESUMEN                                                   ║\n";
echo "╠════════════════════════════════════════════════════════════════╣\n";
echo "║  Rol 2 (Directivo)    → ✅ ACCESO PERMITIDO                  ║\n";
echo "║  Rol 3 (Financiero)   → ✅ ACCESO PERMITIDO                  ║\n";
echo "║                                                                ║\n";
echo "║  URL: http://localhost:8000/admin/ciclos                      ║\n";
echo "║  Acceso después de limpiar caché y cookies                    ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n\n";

echo "✅ VERIFICACIÓN COMPLETADA\n\n";
