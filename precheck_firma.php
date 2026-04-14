<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

echo "=== PRE-CHECK ANTES DE FIRMAR ===\n\n";

// 1. Verificar usuario directivo
echo "1. USUARIO DIRECTIVO:\n";
$user = DB::table('Usuarios')
    ->where('email', 'directivo@test.com')
    ->first();

if ($user) {
    echo "   ✓ Encontrado: {$user->email}\n";
    echo "   - ID: {$user->id_usuario}\n";
    echo "   - Tipo: {$user->tipo_usuario}\n";
    
    // Verificar Personal
    $personal = DB::table('Personal')
        ->where('fk_id_usuario', $user->id_usuario)
        ->first();
    
    if ($personal) {
        echo "   ✓ Registro Personal encontrado\n";
        echo "   - Número empleado: {$personal->numero_empleado}\n";
        echo "   - fk_rol: {$personal->fk_rol}\n";
        
        if ($personal->fk_rol == 2) {
            echo "   ✓ Es Directivo (rol=2)\n";
        } else {
            echo "   ❌ NO es Directivo. Rol actual: {$personal->fk_rol}\n";
        }
    } else {
        echo "   ❌ NO tiene registro en Personal\n";
    }
} else {
    echo "   ❌ Usuario no encontrado\n";
}

// 2. Verificar solicitud 1008
echo "\n2. SOLICITUD (FOLIO 1008):\n";
$solicitud = DB::table('Solicitudes')
    ->where('folio', 1008)
    ->first();

if ($solicitud) {
    echo "   ✓ Encontrada\n";
    echo "   - Folio: {$solicitud->folio}\n";
    echo "   - Estado ID: {$solicitud->fk_id_estado}\n";
    
    $estado = DB::table('Cat_EstadosSolicitud')
        ->where('id_estado', $solicitud->fk_id_estado)
        ->first();
    
    if ($estado) {
        echo "   - Nombre estado: {$estado->nombre_estado}\n";
        
        if ($solicitud->fk_id_estado == 10) {
            echo "   ✓ Estado correcto (DOCUMENTOS_VERIFICADOS)\n";
        } else {
            echo "   ❌ Estado incorrecto. Esperado: 10 (DOCUMENTOS_VERIFICADOS)\n";
        }
    }
    
    echo "   - Monto entregado: \${$solicitud->monto_entregado}\n";
} else {
    echo "   ❌ Solicitud no encontrada\n";
}

// 3. Verificar presupuesto disponible
echo "\n3. PRESUPUESTO:\n";
$presupuesto = DB::table('presupuesto_apoyos')
    ->where('folio', 1008)
    ->first();

if ($presupuesto) {
    $disponible = ($presupuesto->monto_solicitado ?? 0) - ($presupuesto->monto_aprobado ?? 0);
    echo "   Solicitado: \${$presupuesto->monto_solicitado}\n";
    echo "   Aprobado: \${$presupuesto->monto_aprobado}\n";
    echo "   Disponible: \${$disponible}\n";
    
    if ($disponible >= ($solicitud->monto_entregado ?? 0)) {
        echo "   ✓ Presupuesto suficiente\n";
    } else {
        echo "   ❌ Presupuesto insuficiente\n";
    }
} else {
    echo "   ❌ No hay presupuesto registrado\n";
}

// 4. Verificar documentos verificados
echo "\n4. DOCUMENTOS:\n";
$docs = DB::table('Documentos_Expediente')
    ->where('fk_folio', 1008)
    ->get();

if ($docs->count() > 0) {
    echo "   Total: {$docs->count()}\n";
    $verificados = $docs->filter(function($d) { return $d->estado_validacion === 'Correcto'; })->count();
    echo "   Verificados: {$verificados}\n";
    
    if ($verificados > 0) {
        echo "   ✓ Hay documentos verificados\n";
    } else {
        echo "   ❌ No hay documentos verificados\n";
    }
} else {
    echo "   ❌ No hay documentos\n";
}

// 5. Verificar tabla de firmas
echo "\n5. TABLA DE FIRMAS:\n";
$firmasExist = DB::select(
    "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES 
     WHERE TABLE_SCHEMA = 'BD_SIGO' AND TABLE_NAME = 'firmas_electronicas'"
);

if (count($firmasExist) > 0) {
    echo "   ✓ Tabla 'firmas_electronicas' existe\n";
} else {
    echo "   ❌ Tabla 'firmas_electronicas' NO existe\n";
}

echo "\n" . str_repeat("=", 50) . "\n";
echo "✓ PRE-CHECK COMPLETADO\n";
echo str_repeat("=", 50) . "\n";
