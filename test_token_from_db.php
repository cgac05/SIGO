<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\DirectivoCalendarioPermiso;
use App\Models\Apoyo;
use Google_Service_Calendar_Event;
use Google_Service_Calendar_EventDateTime;
use Google_Service_Calendar_EventReminders;
use Google_Service_Calendar_EventReminder;
use Illuminate\Support\Facades\Crypt;

echo "🔍 Usando token EXACTO de la BD para crear evento...\n\n";

$permiso = DirectivoCalendarioPermiso::where('activo', 1)->first();
$apoyo = Apoyo::with('hitos')->findOrFail(24);
$hito = $apoyo->hitos[0];

// Decodificar token guardado
$tokenCompleto = json_decode(Crypt::decrypt($permiso->google_access_token), true);

echo "Token info:\n";
echo "  Access Token (primeros 50): " . substr($tokenCompleto['access_token'], 0, 50) . "\n";
echo "  Created: " . $tokenCompleto['created'] . "\n";
echo "  Expires In: " . $tokenCompleto['expires_in'] . "\n";

// Configurar Google Client
$client = new \Google_Client();
$client->setClientId('523344188732-pglcoe6qpgl5a6df8n0itfa4gbh028oa.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:8000/admin/calendario/callback');
$client->setAccessToken($tokenCompleto);

$calendarService = new \Google_Service_Calendar($client);

// Crear evento
$event = new \Google_Service_Calendar_Event();
$event->setSummary("TEST DB - {$apoyo->nombre_apoyo} - {$hito->nombre_hito}");
$event->setDescription("Test using saved token from DB");

$fecha = $hito->fecha_inicio;
$eventDateTime = new \Google_Service_Calendar_EventDateTime();
$eventDateTime->setDateTime($fecha->format(\DateTime::RFC3339));
$eventDateTime->setTimeZone(config('app.timezone', 'America/Mexico_City'));
$event->setStart($eventDateTime);

$endTime = (clone $fecha)->add(new \DateInterval('PT1H'));
$eventEnd = new \Google_Service_Calendar_EventDateTime();
$eventEnd->setDateTime($endTime->format(\DateTime::RFC3339));
$eventEnd->setTimeZone(config('app.timezone', 'America/Mexico_City'));
$event->setEnd($eventEnd);

// Intentar crear
try {
    echo "\n📤 Intentando crear evento...\n";
    $createdEvent = $calendarService->events->insert(
        $permiso->google_calendar_id,
        $event
    );
    
    echo "✅ Éxito! Event ID: " . $createdEvent->getId() . "\n";
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
