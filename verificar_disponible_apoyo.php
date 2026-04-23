<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN: DISPONIBLE EN APOYO (CORREGIDO) ===\n\n";

// Obtener solicitudes y sus presupuestos
$solicitudes = DB::select("
    SELECT TOP 5
        s.folio,
        s.fk_id_apoyo,
        a.nombre_apoyo,
        a.monto_maximo,
        pa.monto_solicitado,
        pa.monto_aprobado,
        b.nombre
    FROM Solicitudes s
    JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
    JOIN presupuesto_apoyos pa ON pa.folio = s.folio
    JOIN Beneficiarios b ON s.fk_curp = b.curp
    ORDER BY s.folio DESC
");

if (count($solicitudes) > 0) {
    echo "SOLICITUDES Y SUS PRESUPUESTOS:\n\n";
    foreach ($solicitudes as $sol) {
        echo "Folio: " . $sol->folio . "\n";
        echo "  Beneficiario: " . $sol->nombre . "\n";
        echo "  Apoyo: " . $sol->nombre_apoyo . "\n";
        echo "  Monto Máximo (por beneficiario): \$" . number_format($sol->monto_maximo, 2) . "\n";
        echo "  ✓ DISPONIBLE EN APOYO (monto_solicitado): \$" . number_format($sol->monto_solicitado, 2) . "\n";
        echo "  Monto Aprobado: \$" . number_format($sol->monto_aprobado ?? 0, 2) . "\n";
        echo "\n";
    }
    echo "✅ El 'DISPONIBLE EN APOYO' ahora muestra el monto_solicitado directamente\n";
} else {
    echo "No se encontraron solicitudes\n";
}
?>
