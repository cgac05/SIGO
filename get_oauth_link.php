<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Services\GoogleCalendarService;
use Illuminate\Support\Facades\DB;

$service = app(GoogleCalendarService::class);

$directivo_id = 6;
$state = base64_encode(random_bytes(32));

DB::table('oauth_states')->insert([
    'state' => $state,
    'directivo_id' => $directivo_id,
    'created_at' => DB::raw('GETDATE()'),
    'expires_at' => DB::raw('DATEADD(MINUTE, 15, GETDATE())'),
]);

$authUrl = $service->generarUrlAutenticacion($state);

echo $authUrl;
