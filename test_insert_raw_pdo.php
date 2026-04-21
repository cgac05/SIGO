<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== TEST INSERT DIRECTO CON QUERY RAW ===\n\n";

try {
    // Test: insertar con query totalmente raw
    DB::connection()->getPdo()->exec("
        INSERT INTO [Solicitudes] 
        ([fk_id_apoyo], [fk_id_estado], [estado_solicitud], [origen_solicitud], 
         [creada_por_admin], [admin_creador], [beneficiario_id], [apoyo_id], 
         [observaciones_internas], [fk_curp], [fecha_creacion], [fecha_cambio_estado])
        VALUES 
        (3, 1, 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN', 'admin_caso_a', 
         1, 6, 1, 3, 
         'Notas | Nombre: Christian | Email: test@mail.com | Tel: 3111111111', 
         'AICC050509HNTVMHA5', GETDATE(), GETDATE())
    ");
    
    echo "✓ Insert exitoso con query raw\n\n";
    
    // Recuperar
    $solicitud = DB::table('Solicitudes')
        ->where('origen_solicitud', 'admin_caso_a')
        ->where('admin_creador', 6)
        ->orderByDesc('folio')
        ->first();
    
    echo "✓ Solicitud recuperada:\n";
    echo "  Folio: " . $solicitud->folio . "\n";
    echo "  Estado: " . $solicitud->estado_solicitud . "\n";
    echo "  Observaciones: " . substr($solicitud->observaciones_internas, 0, 50) . "...\n";
    
} catch (\Exception $e) {
    echo "✗ Error: " . $e->getMessage() . "\n";
    if (isset($e)) {
        echo "\nPrevious: " . $e->getPrevious() . "\n";
    }
}
?>
