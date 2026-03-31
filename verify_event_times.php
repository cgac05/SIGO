<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

use App\Models\HitosApoyo;
use App\Models\DirectivoCalendarioPermiso;
use Google\Client as Google_Client;
use Google\Service\Calendar as Google_Service_Calendar;

echo "🔍 Verificador de Horas en Google Calendar\n";
echo "==========================================\n\n";

try {
    // Obtener últimos eventos creados
    $hitos = HitosApoyo::where('google_calendar_event_id', '!=', NULL)
        ->orderBy('fecha_creacion', 'desc')
        ->limit(5)
        ->get();

    if ($hitos->isEmpty()) {
        echo "No hay eventos creados aún\n";
        exit;
    }

    // Obtener permiso de directivo
    $permiso = DirectivoCalendarioPermiso::where('activo', 1)->first();
    
    if (!$permiso) {
        echo "❌ No hay directivos activos\n";
        exit;
    }

    // Configurar cliente de Google
    $googleClient = new Google_Client();
    $googleClient->setAuthConfig(config('services.google.credentials'));
    $googleClient->setScopes(['https://www.googleapis.com/auth/calendar']);
    
    $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
    $googleClient->setAccessToken($tokenCompleto);
    
    $calendarService = new Google_Service_Calendar($googleClient);

    echo "📅 Últimos 5 eventos creados:\n";
    echo "==============================\n\n";

    foreach ($hitos as $hito) {
        try {
            $event = $calendarService->events->get(
                $permiso->google_calendar_id,
                $hito->google_calendar_event_id
            );

            $startTime = $event->getStart()->getDateTime();
            $endTime = $event->getEnd()->getDateTime();
            
            echo "Hito ID: {$hito->id_hito}\n";
            echo "  Nombre: {$hito->nombre_hito}\n";
            echo "  Event ID: {$hito->google_calendar_event_id}\n";
            echo "  Inicio: " . substr($startTime, 0, 19) . "\n";
            echo "  Fin: " . substr($endTime, 0, 19) . "\n";
            
            // Verificar si la hora es 23:59
            preg_match('/T(\d{2}):(\d{2}):\d{2}/', $startTime, $matches);
            $hora = $matches[1] ?? 'N/A';
            $minutos = $matches[2] ?? 'N/A';
            
            if ($hora === '23' && $minutos === '59') {
                echo "  ✅ Hora correcta: 23:59\n";
            } else {
                echo "  ⚠️  Hora incorrecta: {$hora}:{$minutos}\n";
            }
            echo "\n";
            
        } catch (\Exception $e) {
            echo "  ❌ Error al obtener evento: " . $e->getMessage() . "\n\n";
        }
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
