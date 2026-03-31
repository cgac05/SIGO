<?php

use Illuminate\Support\Facades\DB;
use App\Models\User;

require __DIR__ . '/bootstrap/app.php';

$app = require_once __DIR__ . '/bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);

echo "========================================\n";
echo "🔍 DEBUG: Verificando Usuario Directivo\n";
echo "========================================\n\n";

// 1. Ver todos los usuarios
$usuarios = DB::table('Usuarios')->get(['id_usuario', 'email', 'tipo_usuario']);
echo "PASO 1: Todos los Usuarios en BD\n";
foreach ($usuarios as $u) {
    echo "  - ID {$u->id_usuario}: {$u->email} (Tipo: {$u->tipo_usuario})\n";
}

echo "\n";

// 2. Ver tabla Personal
$personal = DB::table('Personal')->get(['id_personal', 'fk_id_usuario', 'fk_rol']);
echo "PASO 2: Tabla Personal (con roles)\n";
foreach ($personal as $p) {
    echo "  - ID {$p->id_personal}: Usuario {$p->fk_id_usuario}, Rol: {$p->fk_rol}\n";
}

echo "\n";

// 3. Ver tabla Cat_Roles para verificar los nombres
$roles = DB::table('Cat_Roles')->get(['id_rol', 'nombre_rol']);
echo "PASO 3: Tabla Cat_Roles\n";
foreach ($roles as $r) {
    echo "  - ID {$r->id_rol}: {$r->nombre_rol}\n";
}

echo "\n";

// 4. Ver usuario directivo que intenta acceder
$directivo = User::where('tipo_usuario', 'like', '%Directivo%')
    ->orWhere('tipo_usuario', 'like', '%directivo%')
    ->first();

if ($directivo) {
    echo "PASO 4: Usuario Directivo Encontrado\n";
    echo "  - ID: {$directivo->id_usuario}\n";
    echo "  - Email: {$directivo->email}\n";
    echo "  - Tipo: {$directivo->tipo_usuario}\n";
    
    // Cargar relación personal
    $directivo->load('personal');
    
    if ($directivo->personal) {
        echo "  - Personal ID: {$directivo->personal->id_personal}\n";
        echo "  - Personal Rol (fk_rol): {$directivo->personal->fk_rol}\n";
    } else {
        echo "  ❌ NO TIENE REGISTRO EN TABLA PERSONAL\n";
    }
} else {
    echo "PASO 4: ❌ NO SE ENCONTRÓ USUARIO DIRECTIVO\n";
}

echo "\n";

// 5. Ver USUARIOS con PERSONAL
echo "PASO 5: Usuarios CON Personal asignado\n";
$usersWithPersonal = DB::select(
    'SELECT u.id_usuario, u.email, u.tipo_usuario, p.fk_rol, cr.nombre_rol
    FROM Usuarios u
    LEFT JOIN Personal p ON u.id_usuario = p.fk_id_usuario
    LEFT JOIN Cat_Roles cr ON p.fk_rol = cr.id_rol
    WHERE p.fk_id_usuario IS NOT NULL'
);

foreach ($usersWithPersonal as $row) {
    echo "  - Usuario {$row->id_usuario}: {$row->email} → Rol {$row->fk_rol} ({$row->nombre_rol})\n";
}

echo "\n========================================\n";
