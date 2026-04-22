<?php

// Test script to check if API /api/caso-a/pendientes-escaneo is working

require 'bootstrap/app.php';

$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

// Create a fake request
$request = Illuminate\Http\Request::create('/api/caso-a/pendientes-escaneo', 'GET');
$request->setUserResolver(function () {
    $user = new stdClass();
    $user->id = 1;
    $user->role_id = 1;
    return $user;
});

// Get the response
try {
    $response = $kernel->handle($request);
    echo "Status Code: " . $response->getStatusCode() . "\n";
    echo "Content:\n";
    echo $response->getContent();
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
}
