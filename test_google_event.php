<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DirectivoCalendarioPermiso;
use App\Models\Apoyo;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventReminders;
use Google_Service_Calendar_EventReminder;

echo "🔍 Creando evento de prueba para Google Calendar...\n\n";

$permiso = DirectivoCalendarioPermiso::where('activo', 1)->first();
$apoyo = Apoyo::with('hitos')->findOrFail(24);
$hito = $apoyo->hitos[0]; // Primer hito

// Configurar Google Client
$client = new \Google_Client();
$client->setClientId('523344188732-pglcoe6qpgl5a6df8n0itfa4gbh028oa.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:8000/admin/calendario/callback');

// Cargar token
$tokenCompleto = json_decode(\Illuminate\Support\Facades\Crypt::decrypt($permiso->google_access_token), true);
$client->setAccessToken($tokenCompleto);

$calendarService = new \Google_Service_Calendar($client);

// Crear evento como lo hace el código
$event = new Google_Service_Calendar_Event();
$event->setSummary("TEST - {$apoyo->nombre_apoyo} - {$hito->nombre_hito}");
$event->setDescription("Test event");

$fecha = $hito->fecha_inicio->toDateTime();
echo "Fecha: " . $fecha->format(\DateTime::RFC3339) . "\n";

$eventDateTime = new Google_Service_Calendar_EventDateTime();
$eventDateTime->setDateTime($fecha->format(\DateTime::RFC3339));
$eventDateTime->setTimeZone(config('app.timezone', 'America/Mexico_City'));
$event->setStart($eventDateTime);

$endTime = (clone $fecha)->add(new \DateInterval('PT1H'));

$eventEnd = new Google_Service_Calendar_EventDateTime();
$eventEnd->setDateTime($endTime->format(\DateTime::RFC3339));
$eventEnd->setTimeZone(config('app.timezone', 'America/Mexico_City'));
$event->setEnd($eventEnd);

// Intentar crear
try {
    echo "\n📤 Enviando evento a Google Calendar...\n";
    echo "Calendar ID: {$permiso->google_calendar_id}\n";
    echo "Summary: " . $event->getSummary() . "\n";
    echo "Start: " . $event->getStart()->getDateTime() . " (" . $event->getStart()->getTimeZone() . ")\n";
    echo "End: " . $event->getEnd()->getDateTime() . " (" . $event->getEnd()->getTimeZone() . ")\n";
    
    $createdEvent = $calendarService->events->insert(
        $permiso->google_calendar_id,
        $event
    );
    
    echo "\n✅ Evento creado:\n";
    echo "Event ID: " . $createdEvent->getId() . "\n";
    echo "Link: " . $createdEvent->getHtmlLink() . "\n";
} catch (\Exception $e) {
    echo "\n❌ Error: " . $e->getMessage() . "\n";
    if ($e->getCode() == 400) {
        echo "\nJSON Error:\n";
        $errorData = json_decode($e->getMessage(), true);
        if (is_array($errorData)) {
            print_r($errorData);
        }
    }
}
