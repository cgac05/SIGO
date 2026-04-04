<?php

/**
 * Script de limpieza: Detecta y repara montos erráticos en la tabla Apoyos
 * Ejecutar desde: php artisan tinker < cleanup_corrupted_montos.php
 * O simplemente: php cleanup_corrupted_montos.php desde raíz del proyecto
 */

require_once __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\Apoyo;

echo "🔍 Buscando apoyos con montos erráticos...\n";

// Buscar apoyos con monto_maximo > 100 millones (límite razonable)
$corruptedApoyos = Apoyo::where('monto_maximo', '>', 100000000) // > 100M
    ->orWhere('cupo_limite', '>', 1000000)
    ->get();

if ($corruptedApoyos->isEmpty()) {
    echo "✅ No se encontraron apoyos con montos erráticos.\n";
    exit(0);
}

echo "❌ Se encontraron " . $corruptedApoyos->count() . " apoyo(s) con datos sospechosos:\n\n";

foreach ($corruptedApoyos as $apoyo) {
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "ID: {$apoyo->id_apoyo}\n";
    echo "Nombre: {$apoyo->nombre_apoyo}\n";
    echo "Monto Máximo: {$apoyo->monto_maximo}\n";
    echo "Cupo Límite: {$apoyo->cupo_limite}\n";
    echo "Tipo: {$apoyo->tipo_apoyo}\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
}

echo "\n⚠️  Para borrar estos apoyos, ejecute:\n";
echo "   php artisan tinker\n";
echo "   > App\\Models\\Apoyo::whereIn('id_apoyo', [" . 
    $corruptedApoyos->pluck('id_apoyo')->implode(', ') . 
    "])->delete();\n";
