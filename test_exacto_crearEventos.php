<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;
use App\Models\DirectivoCalendarioPermiso;
use Illuminate\Support\Facades\Log;

echo "🔍 Replicando EXACTAMENTE crearEventosApoyo() con debugging...\n\n";

// Obtener datos EXACTO como lo hace el método
$apoyo = Apoyo::with('hitos')->findOrFail(24);
echo "✅ Apoyo obtenido: {$apoyo->nombre_apoyo}\n";

// Obtener directivos EXACTO como lo hace el método
$directivos = DirectivoCalendarioPermiso::where('activo', 1)->with('directivo')->get();
echo "✅ Directivos obtenidos: " . count($directivos) . "\n";

foreach ($directivos as $permiso) {
    echo "\n🔄 Procesando permiso para: {$permiso->email_directivo}\n";
    
    try {
        // Setup cliente Google EXACTO como lo hace el método en __construct
        $client = new \Google_Client();
        $client->setApplicationName('SIGO - INJUVE');
        $client->setScopes([
            \Google_Service_Calendar::CALENDAR,
            \Google_Service_Oauth2::USERINFO_EMAIL,
            \Google_Service_Oauth2::OPENID,
        ]);
        
        // Configurar credenciales
        $client->setClientId('523344188732-pglcoe6qpgl5a6df8n0itfa4gbh028oa.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri('http://localhost:8000/admin/calendario/callback');
        
        // Configurar token EXACTO como lo hace el método
        $tokenCompleto = json_decode(\Illuminate\Support\Facades\Crypt::decrypt($permiso->google_access_token), true);
        $client->setAccessToken($tokenCompleto);
        
        // Crear calendar service EXACTO como lo hace el método
        $calendarService = new \Google_Service_Calendar($client);
        echo "  ✅ Calendar service creado\n";
        
        // Procesar cada hito EXACTO como lo hace el método
        foreach ($apoyo->hitos as $hito) {
            echo "    📝 Procesando hito: {$hito->nombre_hito}\n";
            
            if (!$hito->fecha_inicio) {
                echo "      ⚠️  Sin fecha_inicio, saltando\n";
                continue;
            }
            
            // CREAR EVENTO EXACTO COMO LO HACE EL MÉTODO
            $event = new \Google_Service_Calendar_Event();
            $event->setSummary("INJUVE - {$apoyo->nombre_apoyo} - {$hito->nombre_hito}");
            echo "      Summary: {$event->getSummary()}\n";
            
            // Descripción
            $descripcion = "**APOYO:** {$apoyo->nombre_apoyo}\n";
            $descripcion .= "**TIPO:** " . ($apoyo->tipo_apoyo ?? 'N/A') . "\n";
            $event->setDescription($descripcion);
            echo "      Description set: OK\n";
            
            // Color
            $event->setColorId('1'); // Hardcoded for test
            echo "      Color set: OK\n";
            
            // Fechas EXACTO
            $fecha = $hito->fecha_inicio->toDateTime();
            echo "      Fecha toDateTime: " . $fecha->format(\DateTime::RFC3339) . "\n";
            
            $eventDateTime = new \Google_Service_Calendar_EventDateTime();
            $eventDateTime->setDateTime($fecha->format(\DateTime::RFC3339));
            $eventDateTime->setTimeZone(config('app.timezone', 'America/Mexico_City'));
            $event->setStart($eventDateTime);
            echo "      Start set: OK\n";
            
            $endTime = (clone $fecha)->add(new \DateInterval('PT1H'));
            $eventEnd = new \Google_Service_Calendar_EventDateTime();
            $eventEnd->setDateTime($endTime->format(\DateTime::RFC3339));
            $eventEnd->setTimeZone(config('app.timezone', 'America/Mexico_City'));
            $event->setEnd($eventEnd);
            echo "      End set: OK\n";
            
            // Recordatorios EXACTO como el método corregido
            $reminders = new \Google_Service_Calendar_EventReminders();
            $overrides = [];
            if ($apoyo->recordatorio_dias) {
                $override = new \Google_Service_Calendar_EventReminder();
                $override->setMethod('notification');
                $override->setMinutes($apoyo->recordatorio_dias * 24 * 60);
                $overrides[] = $override;
            }
            $reminders->setUseDefault(false);
            $reminders->setOverrides($overrides);
            $event->setReminders($reminders);
            echo "      Reminders set: OK\n";
            
            // INTENTAR CREAR - EXACTO
            echo "      📤 Intentando insert()...\n";
            echo "        Calendar ID: {$permiso->google_calendar_id}\n";
            
            $createdEvent = $calendarService->events->insert(
                $permiso->google_calendar_id,
                $event
            );
            
            echo "      ✅ Evento creado! ID: " . $createdEvent->getId() . "\n";
        }
        
        echo "  ✅ Permiso procesado exitosamente\n";
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
        if ($e->getCode() == 400) {
            echo "     Event data: Summary={$event->getSummary()}\n";
        }
    }
}

echo "\n✅ Test completado\n";
