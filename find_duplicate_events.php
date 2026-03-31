<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

use App\Models\HitosApoyo;
use App\Models\Apoyo;
use App\Services\GoogleCalendarService;

echo "🧹 Limpiador de Eventos Duplicados en Google Calendar\n";
echo "======================================================\n\n";

try {
    // Obtener todos los apoyos con duplicados
    $apoyos = Apoyo::with('hitos')->get();
    
    $googleCalendarService = new GoogleCalendarService();
    $duplicados_encontrados = 0;
    $duplicados_eliminados = 0;

    foreach ($apoyos as $apoyo) {
        foreach ($apoyo->hitos as $hito) {
            if ($hito->google_calendar_event_id) {
                // Contar cuántos hitos de esta apoyo tienen el mismo nombre
                $duplicados = HitosApoyo::where('fk_id_apoyo', $apoyo->id_apoyo)
                    ->where('nombre_hito', $hito->nombre_hito)
                    ->where('id_hito', '!=', $hito->id_hito)
                    ->where('google_calendar_event_id', '!=', NULL)
                    ->get();

                if (count($duplicados) > 0) {
                    echo "⚠️  Apoyo #{$apoyo->id_apoyo}: {$apoyo->nombre_apoyo}\n";
                    echo "   Hito: {$hito->nombre_hito} (ID: {$hito->id_hito})\n";
                    echo "   Encontrados {$duplicados->count()} duplicados\n";
                    
                    foreach ($duplicados as $dup) {
                        echo "     - Duplicado ID: {$dup->id_hito}, Event ID: {$dup->google_calendar_event_id}\n";
                        $duplicados_encontrados++;
                    }
                    echo "\n";
                }
            }
        }
    }

    echo "\n📊 RESUMEN:\n";
    echo "   Duplicados encontrados: {$duplicados_encontrados}\n";
    echo "\n✅ Para eliminar los eventos duplicados, ejecuta:\n";
    echo "   php delete_duplicate_events.php\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
