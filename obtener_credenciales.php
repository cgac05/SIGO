<?php
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$db = $app->make('db');

// Query para obtener usuarios de prueba
echo "\n=== USUARIOS EN SIGO ===\n";

// Beneficiarios
$usuarios = $db->select("
    SELECT id_usuario, email, nombre, apellido_paterno, apellido_materno, fk_id_rol 
    FROM Usuarios 
    WHERE email LIKE '%@gmail.com' OR email LIKE '%@injuve%' OR email LIKE '%@test.local'
    ORDER BY fk_id_rol
");

foreach($usuarios as $user) {
    $rol = match($user->fk_id_rol) {
        1 => 'ADMINISTRATIVO',
        2 => 'DIRECTIVO',
        3 => 'FINANZAS',
        default => 'BENEFICIARIO'
    };
    echo "\nID: {$user->id_usuario} | Email: {$user->email} | Nombre: {$user->nombre} {$user->apellido_paterno} | Rol: {$rol}\n";
}

echo "\n=== NOTA: Las contraseñas se establecen en seeding o durante registro ===\n";
