<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;

// Get Directivo role
$rol = DB::selectOne("SELECT id_rol FROM Cat_Roles WHERE nombre_rol = 'Directivo'");

if (!$rol) {
    echo "❌ No se encontró rol 'Directivo'\n";
    exit;
}

echo "✅ Usando rol Directivo (ID: " . $rol->id_rol . ")\n\n";

// Check if email already exists
$user = DB::selectOne("SELECT id_usuario FROM Usuarios WHERE email = 'directivo@test.com'");

if ($user) {
    echo "✅ Usuario ya existe con ID: " . $user->id_usuario . "\n";
    $user_id = $user->id_usuario;
} else {
    // Create user
    DB::statement("
        INSERT INTO Usuarios (email, password_hash, tipo_usuario, activo, fecha_creacion, notif_email_apoyos, notif_email_status)
        VALUES ('directivo@test.com', ?, 'Personal', 1, GETDATE(), 1, 1)
    ", [bcrypt('password123')]);
    
    $user = DB::selectOne("SELECT id_usuario FROM Usuarios WHERE email = 'directivo@test.com'");
    $user_id = $user->id_usuario;
    echo "✅ Usuario en tabla Usuarios creado con ID: " . $user_id . "\n";
}

// Check if Personal already exists
$personal = DB::selectOne("SELECT numero_empleado FROM Personal WHERE fk_id_usuario = ?", [$user_id]);

if ($personal) {
    echo "✅ Registro Personal ya existe\n";
} else {
    // Create Personal record with unique numero_empleado
    $numero_empleado = 'DIR' . str_pad($user_id, 4, '0', STR_PAD_LEFT) . date('His');
    
    DB::statement("
        INSERT INTO Personal (numero_empleado, fk_id_usuario, nombre, apellido_paterno, apellido_materno, fk_rol, puesto)
        VALUES (?, ?, 'Director', 'Prueba', 'Test', ?, 'Director de Prueba')
    ", [$numero_empleado, $user_id, $rol->id_rol]);
    
    echo "✅ Registro en tabla Personal creado (Emp: " . $numero_empleado . ")\n";
}

echo "\n" . str_repeat("═", 50) . "\n";
echo "✅ USUARIO DIRECTIVO CREADO EXITOSAMENTE\n";
echo str_repeat("═", 50) . "\n";
echo "Email:        directivo@test.com\n";
echo "Contraseña:   password123\n";
echo "Rol:          Directivo\n";
echo "\n🌐 Ingresa a: http://localhost/SIGO/login\n";
echo "📋 Luego accede a: /solicitudes/proceso\n";
echo "\nYa deberías ver el NUEVO layout con CARDS ✨\n";
?>
