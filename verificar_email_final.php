<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

echo "=== VERIFICACIÓN FINAL: EMAIL PARA RECHAZO ===\n\n";

// Obtener solicitud
$solicitud = DB::table('Solicitudes')->where('folio', 1016)->first();
echo "1. Solicitud folio 1016:\n";
echo "   CURP: " . $solicitud->fk_curp . "\n\n";

// Obtener beneficiario CON EMAIL (mediante JOIN)
$beneficiario = DB::table('Beneficiarios')
    ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
    ->select('Beneficiarios.*', 'Usuarios.email')
    ->where('Beneficiarios.curp', $solicitud->fk_curp)
    ->first();

echo "2. Beneficiario encontrado:\n";
echo "   Nombre: " . $beneficiario->nombre . "\n";
echo "   Email: " . ($beneficiario->email ?? 'SIN EMAIL') . "\n\n";

// Obtener apoyo
$apoyo = DB::table('Apoyos')
    ->where('id_apoyo', $solicitud->fk_id_apoyo)
    ->first();

echo "3. Apoyo encontrado:\n";
echo "   Nombre: " . $apoyo->nombre_apoyo . "\n\n";

// Simular envío de email
if ($beneficiario && $beneficiario->email) {
    echo "4. Intentando enviar email de rechazo...\n";
    try {
        Mail::send('emails.rechazo-solicitud', [
            'folio' => 1016,
            'beneficiario_nombre' => $beneficiario->nombre,
            'apoyo_nombre' => $apoyo->nombre_apoyo,
            'motivos_generales' => [
                'Documentación incompleta',
                'Presupuesto insuficiente'
            ],
            'motivo_directivo' => 'Test de rechazo',
        ], function ($message) use ($beneficiario) {
            $message->subject('Solicitud 1016 - Rechazo TEST')
                    ->to($beneficiario->email);
        });
        echo "   ✓ Email enviado\n";
        echo "   Destino: " . $beneficiario->email . "\n";
        echo "   Asunto: Solicitud 1016 - Rechazo TEST\n\n";
        
        echo "5. Verifica tu bandeja de entrada: " . $beneficiario->email . "\n";
        echo "   (Si MAIL_MAILER=log, revisa: storage/logs/laravel.log)\n";
    } catch (\Exception $e) {
        echo "   ✗ Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "4. ✗ No hay email disponible para enviar\n";
}

echo "\n=== FIN ===\n";
?>
