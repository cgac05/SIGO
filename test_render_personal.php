<?php
session_start();
$_SESSION['auth_user'] = 1;

$app = require __DIR__.'/bootstrap/app.php';
App::setBasePath(__DIR__);

$response = app('Illuminate\Routing\Router')
    ->dispatch(Illuminate\Http\Request::create('/personal/crear'));
    
echo $response->getContent();
?>
