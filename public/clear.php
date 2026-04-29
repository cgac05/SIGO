<?php

require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);

try {
    $kernel->call('cache:clear');
    $kernel->call('config:clear');
    $kernel->call('route:clear');
    $kernel->call('view:clear');
    
    echo "<h1>Cachés de SIGO limpiadas exitosamente</h1>";
    echo "<p>Se limpiaron las cachés de: aplicación, configuración, rutas y vistas.</p>";
} catch (\Exception $e) {
    echo "<h1>Error al limpiar cachés</h1>";
    echo "<p>" . $e->getMessage() . "</p>";
}
