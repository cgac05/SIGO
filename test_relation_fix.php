<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

$database = $app->make('db');

try {
    // Verificar que los logs existen
    $count = \App\Models\CalendarioSincronizacionLog::count();
    echo "Total logs en BD: {$count}\n";
    
    if ($count > 0) {
        // Intentar cargar los logs con relaciones
        $logs = \App\Models\CalendarioSincronizacionLog::with(['hito', 'apoyo', 'usuario'])
            ->latest('fecha_cambio')
            ->take(3)
            ->get();

        echo "\n✅ Logs cargados correctamente:\n";
        foreach ($logs as $log) {
            $apoyoNombre = $log->apoyo?->nombre_apoyo ?? 'N/A';
            $hitoNombre = $log->hito?->nombre_hito ?? 'N/A';
            echo "  - Apoyo: {$apoyoNombre}, Hito: {$hitoNombre}, Tipo: {$log->tipo_cambio}\n";
        }
    } else {
        echo "⚠️  No hay logs registrados en la BD\n";
    }
    
} catch (\Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
    echo "Archivo: " . $e->getFile() . "\n";
    echo "Línea: " . $e->getLine() . "\n";
}
?>
