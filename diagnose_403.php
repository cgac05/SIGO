<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== DIAGNOSTICO ERROR 403 ===\n\n";

// 1. Verificar estado actual de folio 1008
echo "1. ESTADO ACTUAL DE FOLIO 1008:\n";
$solicitud = DB::table('Solicitudes')
    ->where('folio', 1008)
    ->first(['folio', 'fk_id_estado', 'monto_entregado']);

if ($solicitud) {
    echo "   Folio: {$solicitud->folio}\n";
    echo "   Estado ID actual: {$solicitud->fk_id_estado}\n";
    echo "   Monto entregado: {$solicitud->monto_entregado}\n";
    
    // Ver nombre del estado
    $estado = DB::table('Cat_EstadosSolicitud')
        ->where('id_estado', $solicitud->fk_id_estado)
        ->first(['id_estado', 'nombre_estado']);
    
    if ($estado) {
        echo "   Nombre estado: {$estado->nombre_estado}\n";
    }
}

// 2. Ver qué ID tiene DOCUMENTOS_VERIFICADOS
echo "\n2. ID DEL ESTADO DOCUMENTOS_VERIFICADOS:\n";
$estado_verificados = DB::table('Cat_EstadosSolicitud')
    ->where('nombre_estado', 'DOCUMENTOS_VERIFICADOS')
    ->first(['id_estado', 'nombre_estado']);

if ($estado_verificados) {
    echo "   Encontrado: ID {$estado_verificados->id_estado} = {$estado_verificados->nombre_estado}\n";
} else {
    echo "   ❌ NO EXISTE\n";
}

// 3. Ver el ID que el controller espera (línea 765: != 12)
echo "\n3. ESTADO ID 12 (ESPERADO POR CONTROLLER):\n";
$estado_12 = DB::table('Cat_EstadosSolicitud')
    ->where('id_estado', 12)
    ->first(['id_estado', 'nombre_estado']);

if ($estado_12) {
    echo "   ID 12 = {$estado_12->nombre_estado}\n";
} else {
    echo "   ❌ ID 12 NO EXISTE\n";
}

// 4. Verificar tabla de firmass
echo "\n4. ¿EXISTE TABLA firmas_electronicas?\n";
$tables = DB::select("SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'BD_SIGO' AND TABLE_NAME LIKE '%fir%'");
if (count($tables) > 0) {
    foreach ($tables as $table) {
        echo "   ✓ Encontrada: {$table->TABLE_NAME}\n";
    }
} else {
    echo "   ❌ No hay tablas relacionadas a firmas\n";
}

// 5. Verificar rol del usuario directivo@test.com
echo "\n5. VERIFICAR USUARIO DIRECTIVO@TEST.COM:\n";
$user = DB::table('Users')
    ->where('email', 'directivo@test.com')
    ->first(['id', 'email', 'role_id']);

if ($user) {
    echo "   ID: {$user->id}\n";
    echo "   Email: {$user->email}\n";
    echo "   Role ID: {$user->role_id}\n";
    
    if ($user->role_id == 2) {
        echo "   ✓ Es directivo (role_id=2)\n";
    } else {
        echo "   ❌ NO es directivo (role_id={$user->role_id} esperado 2)\n";
    }
} else {
    echo "   ❌ Usuario no encontrado\n";
}
