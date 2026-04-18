<?php
/**
 * Script para probar el rechazo y envío de email
 */
require 'vendor/autoload.php';

$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

echo "=== TEST RECHAZO Y EMAIL ===\n\n";

// 1. Verificar configuración de mail
echo "1. Configuración de MAIL:\n";
echo "   MAIL_MAILER: " . config('mail.default') . "\n";
echo "   Esto significa que los emails se: " . (config('mail.default') === 'log' ? 'GUARDAN EN LOGS (no se envían)' : 'ENVÍAN REALMENTE') . "\n\n";

// 2. Verificar si existe folio 1016
echo "2. Buscando solicitud folio 1016...\n";
$solicitud = DB::table('Solicitudes')->where('folio', 1016)->first();
if ($solicitud) {
    echo "   ✓ Solicitud encontrada\n";
    echo "   Estado actual (fk_id_estado): " . $solicitud->fk_id_estado . "\n";
    echo "   CURP: " . $solicitud->fk_curp . "\n";
} else {
    echo "   ✗ Solicitud no encontrada\n";
    exit;
}

// 3. Verificar beneficiario
echo "\n3. Buscando beneficiario...\n";
$beneficiario = DB::table('Beneficiarios')
    ->where('curp', $solicitud->fk_curp)
    ->first();
if ($beneficiario) {
    echo "   ✓ Beneficiario encontrado\n";
    echo "   Nombre: " . $beneficiario->nombre . "\n";
    echo "   Email: " . ($beneficiario->email ?? 'SIN EMAIL') . "\n";
} else {
    echo "   ✗ Beneficiario no encontrado\n";
}

// 4. Verificar apoyo
echo "\n4. Buscando apoyo...\n";
$apoyo = DB::table('Apoyos')
    ->where('id_apoyo', $solicitud->fk_id_apoyo)
    ->first();
if ($apoyo) {
    echo "   ✓ Apoyo encontrado\n";
    echo "   Nombre: " . $apoyo->nombre_apoyo . "\n";
} else {
    echo "   ✗ Apoyo no encontrado\n";
}

// 5. Simular envío de email
if ($beneficiario && $beneficiario->email) {
    echo "\n5. Intentando enviar email de prueba...\n";
    try {
        Mail::send('emails.rechazo-solicitud', [
            'folio' => 1016,
            'beneficiario_nombre' => $beneficiario->nombre,
            'apoyo_nombre' => $apoyo->nombre_apoyo,
            'motivos_generales' => [
                'Documentación incompleta',
                'Presupuesto insuficiente'
            ],
            'motivo_directivo' => 'Email de prueba',
        ], function ($message) use ($beneficiario) {
            $message->subject('Solicitud 1016 - Rechazo TEST')
                    ->to($beneficiario->email);
        });
        echo "   ✓ Email enviado (o guardado en logs)\n";
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "\n5. No hay email válido para enviar\n";
}

// 6. Ver últimos logs
echo "\n6. Últimas líneas del log:\n";
$logFile = 'storage/logs/laravel.log';
if (file_exists($logFile)) {
    $lines = array_slice(file($logFile), -10);
    foreach ($lines as $line) {
        echo "   " . trim($line) . "\n";
    }
} else {
    echo "   ✗ Log no encontrado\n";
}

echo "\n=== FIN DEL TEST ===\n";
