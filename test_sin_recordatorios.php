<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;
use App\Models\DirectivoCalendarioPermiso;

echo "🔍 Test SIN RECORDATORIOS para identificar causa del error 400\n\n";

$apoyo = Apoyo::with('hitos')->findOrFail(24);
$directivos = DirectivoCalendarioPermiso::where('activo', 1)->with('directivo')->get();

foreach ($directivos as $permiso) {
    echo "📨 Directivo: {$permiso->email_directivo}\n";
    
    try {
        $client = new \Google_Client();
        $client->setApplicationName('SIGO - INJUVE');
        $client->setScopes([
            \Google_Service_Calendar::CALENDAR,
            \Google_Service_Oauth2::USERINFO_EMAIL,
            \Google_Service_Oauth2::OPENID,
        ]);
        
        $client->setClientId('523344188732-pglcoe6qpgl5a6df8n0itfa4gbh028oa.apps.googleusercontent.com');
        $client->setClientSecret('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');
        $client->setRedirectUri('http://localhost:8000/admin/calendario/callback');
        
        $tokenCompleto = json_decode(\Illuminate\Support\Facades\Crypt::decrypt($permiso->google_access_token), true);
        $client->setAccessToken($tokenCompleto);
        
        $calendarService = new \Google_Service_Calendar($client);
        
        $hito = $apoyo->hitos[0];
        $event = new \Google_Service_Calendar_Event();
        $event->setSummary("TEST - {$apoyo->nombre_apoyo} - {$hito->nombre_hito}");
        $event->setDescription("Test event");
        
        // Sin setColorId
        // Sin recordatorios
        
        $fecha = $hito->fecha_inicio->toDateTime();
        $eventDateTime = new \Google_Service_Calendar_EventDateTime();
        $eventDateTime->setDateTime($fecha->format(\DateTime::RFC3339));
        $eventDateTime->setTimeZone('America/Mexico_City');
        $event->setStart($eventDateTime);
        
        $endTime = (clone $fecha)->add(new \DateInterval('PT1H'));
        $eventEnd = new \Google_Service_Calendar_EventDateTime();
        $eventEnd->setDateTime($endTime->format(\DateTime::RFC3339));
        $eventEnd->setTimeZone('America/Mexico_City');
        $event->setEnd($eventEnd);
        
        echo "  📤 Intentando insert (sin recordatorios)...\n";
        $createdEvent = $calendarService->events->insert(
            $permiso->google_calendar_id,
            $event
        );
        
        echo "  ✅ Éxito! Event ID: " . $createdEvent->getId() . "\n";
    } catch (\Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }
}
