<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;
use App\Models\DirectivoCalendarioPermiso;

echo "🔍 Probando diferentes formas de configurar recordatorios\n\n";

$apoyo = Apoyo::with('hitos')->findOrFail(24);
$permiso = DirectivoCalendarioPermiso::where('activo', 1)->first();
$hito = $apoyo->hitos[0];

$client = new \Google_Client();
$client->setApplicationName('SIGO - INJUVE');
$client->setScopes([\Google_Service_Calendar::CALENDAR, \Google_Service_Oauth2::USERINFO_EMAIL, \Google_Service_Oauth2::OPENID]);
$client->setClientId('523344188732-pglcoe6qpgl5a6df8n0itfa4gbh028oa.apps.googleusercontent.com');
$client->setClientSecret('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');
$client->setRedirectUri('http://localhost:8000/admin/calendario/callback');

$tokenCompleto = json_decode(\Illuminate\Support\Facades\Crypt::decrypt($permiso->google_access_token), true);
$client->setAccessToken($tokenCompleto);

$calendarService = new \Google_Service_Calendar($client);

$fecha = $hito->fecha_inicio->toDateTime();

// TEST 1: Sin recordatorios (ya sabemos que funciona)
echo "TEST 1: Sin recordatorios\n";
try {
    $event1 = new \Google_Service_Calendar_Event();
    $event1->setSummary("TEST1 - Sin recordatorios");
    
    $startDT = new \Google_Service_Calendar_EventDateTime();
    $startDT->setDateTime($fecha->format(\DateTime::RFC3339));
    $startDT->setTimeZone('America/Mexico_City');
    $event1->setStart($startDT);
    
    $endDT = new \Google_Service_Calendar_EventDateTime();
    $endDT->setDateTime((clone $fecha)->add(new \DateInterval('PT1H'))->format(\DateTime::RFC3339));
    $endDT->setTimeZone('America/Mexico_City');
    $event1->setEnd($endDT);
    
    $createdEvent = $calendarService->events->insert($permiso->google_calendar_id, $event1);
    echo "  ✅ Éxito: " . $createdEvent->getId() . "\n";
} catch (\Exception $e) {
    echo "  ❌ Error: " . substr($e->getMessage(), 0, 100) . "\n";
}

// TEST 2: Con reminders array simple
echo "\nTEST 2: Con reminders como array simple\n";
try {
    $event2 = new \Google_Service_Calendar_Event();
    $event2->setSummary("TEST2 - Con recordatorios array");
    
    $startDT = new \Google_Service_Calendar_EventDateTime();
    $startDT->setDateTime($fecha->format(\DateTime::RFC3339));
    $startDT->setTimeZone('America/Mexico_City');
    $event2->setStart($startDT);
    
    $endDT = new \Google_Service_Calendar_EventDateTime();
    $endDT->setDateTime((clone $fecha)->add(new \DateInterval('PT1H'))->format(\DateTime::RFC3339));
    $endDT->setTimeZone('America/Mexico_City');
    $event2->setEnd($endDT);
    
    // Array simple
    $reminders = new \Google_Service_Calendar_EventReminders();
    $reminders->setUseDefault(false);
    $reminders->setOverrides([
        ['method' => 'notification', 'minutes' => 1440]
    ]);
    $event2->setReminders($reminders);
    
    $createdEvent = $calendarService->events->insert($permiso->google_calendar_id, $event2);
    echo "  ✅ Éxito: " . $createdEvent->getId() . "\n";
} catch (\Exception $e) {
    echo "  ❌ Error: " . substr($e->getMessage(), 0, 100) . "\n";
}

// TEST 3: Con reminders useDefault true
echo "\nTEST 3: Con reminders useDefault = true\n";
try {
    $event3 = new \Google_Service_Calendar_Event();
    $event3->setSummary("TEST3 - Con useDefault true");
    
    $startDT = new \Google_Service_Calendar_EventDateTime();
    $startDT->setDateTime($fecha->format(\DateTime::RFC3339));
    $startDT->setTimeZone('America/Mexico_City');
    $event3->setStart($startDT);
    
    $endDT = new \Google_Service_Calendar_EventDateTime();
    $endDT->setDateTime((clone $fecha)->add(new \DateInterval('PT1H'))->format(\DateTime::RFC3339));
    $endDT->setTimeZone('America/Mexico_City');
    $event3->setEnd($endDT);
    
    $reminders = new \Google_Service_Calendar_EventReminders();
    $reminders->setUseDefault(true);
    $event3->setReminders($reminders);
    
    $createdEvent = $calendarService->events->insert($permiso->google_calendar_id, $event3);
    echo "  ✅ Éxito: " . $createdEvent->getId() . "\n";
} catch (\Exception $e) {
    echo "  ❌ Error: " . substr($e->getMessage(), 0, 100) . "\n";
}

echo "\n✅ Tests completados\n";
