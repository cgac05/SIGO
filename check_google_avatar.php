<?php

require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';

$kernel = $app->make('Illuminate\Contracts\Http\Kernel');
$response = $kernel->handle(
    $request = \Illuminate\Http\Request::capture()
);

$user = \App\Models\User::where('email', 'guillermoavilamora2@gmail.com')->first();

if ($user) {
    echo "=== USUARIO: " . $user->email . " ===" . PHP_EOL;
    echo "ID: " . $user->id_usuario . PHP_EOL;
    echo "Google ID: " . ($user->google_id ?? 'NULL') . PHP_EOL;
    echo "Google Avatar Length: " . (strlen($user->google_avatar) ?? 0) . PHP_EOL;
    echo "Google Avatar (first 150 chars): " . substr($user->google_avatar ?? '', 0, 150) . PHP_EOL;
    echo "Foto Ruta: " . ($user->foto_ruta ?? 'NULL') . PHP_EOL;
    echo "getFotoUrl() result: " . $user->getFotoUrl() . PHP_EOL;
    echo PHP_EOL;
} else {
    echo "Usuario NOT FOUND" . PHP_EOL;
}
