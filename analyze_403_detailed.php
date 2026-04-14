<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== PROBLEMAS ENCONTRADOS EN EL CONTROLLER ===\n\n";

echo "PROBLEMA #1: Estado ID incorrecto\n";
echo "  - Controller verifica: \$solicitud->fk_id_estado != 12\n";
echo "  - Pero DOCUMENTOS_VERIFICADOS tiene ID: 10\n";
echo "  - Esto causa: return with error (NO es 403 técnicamente, pero sí un error)\n\n";

echo "PROBLEMA #2: Tabla de usuarios incorrecta\n";
echo "  - Controller usa: Auth::user() que retorna modelo User\n";
echo "  - BD usa tabla: 'usuarios' (con columnas id_usuario, password_hash)\n";
echo "  - Pero Laravel Auth::user() devuelve objeto basado en modelo Eloquent\n";
echo "  - Usuario: directivo@test.com con id_usuario=21\n\n";

echo "PROBLEMA #3: Falta tabla de firmas\n";
echo "  - Controller intenta insertar en: firmas_electronicas\n";
echo "  - Tabla NO EXISTE\n\n";

echo "=== SOLUCIONES NECESARIAS ===\n";
echo "1. Cambiar línea 765 de != 12 a != 10\n";
echo "2. Crear tabla firmas_electronicas\n";
echo "3. Verificar Auth/modelo User\n";
echo "4. Revisar que el usuario tenga role_id = 2 (Directivo)\n\n";

// Ver si el usuario está asignado a rol
echo "=== BUSCANDO ROL DEL DIRECTIVO ===\n";
$userRoles = DB::table('usuarios')
    ->where('id_usuario', 21)
    ->leftJoin('usuarios_roles', 'usuarios.id_usuario', '=', 'usuarios_roles.usuario_id')
    ->select('usuarios.id_usuario', 'usuarios.email', 'usuarios_roles.rol_id')
    ->get();

if ($userRoles->count() > 0) {
    foreach ($userRoles as $ur) {
        echo json_encode((array)$ur, JSON_PRETTY_PRINT) . "\n";
    }
} else {
    echo "❌ No hay relación entre usuario y roles\n";
}

// Ver todas las tablas de roles
echo "\n=== TABLAS DE ROLES ===\n";
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'BD_SIGO' AND TABLE_NAME LIKE '%rol%'");
foreach ($tables as $table) {
    echo "  {$table->TABLE_NAME}\n";
}
