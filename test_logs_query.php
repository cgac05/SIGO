<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

// Simular usuario autenticado
Auth::loginUsingId(6);

try {
    // Intentar cargar los logs como lo hace el controlador
    $logs = \App\Models\CalendarioSincronizacionLog::with(['hito', 'apoyo', 'usuario'])
        ->latest('fecha_cambio')
        ->take(5)
        ->get();

    echo "[OK] Logs cargados correctamente:\n";
    foreach ($logs as $log) {
        echo "  - ID: {$log->id}, Apoyo: {$log->apoyo?->nombre_apoyo ?? 'N/A'}, Tipo: {$log->tipo_cambio}\n";
    }
} catch (\Exception $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    echo "  at " . $e->getFile() . ":" . $e->getLine() . "\n";
}
