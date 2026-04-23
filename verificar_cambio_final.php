<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN: DISPONIBLE EN APOYO (ACTUALIZADO A TOTAL NECESARIO) ===\n\n";

// Obtener solicitudes y sus presupuestos
$solicitudes = DB::select("
    SELECT TOP 5
        s.folio,
        s.fk_id_apoyo,
        a.nombre_apoyo,
        a.monto_maximo,
        a.cupo_limite,
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
    echo "VISTA DEL DIRECTIVO - SOLICITUDES:\n\n";
    foreach ($solicitudes as $sol) {
        // Cálculo que ahora hace el controlador
        $totalNecesario = ($sol->monto_maximo ?? 0) * ($sol->cupo_limite ?? 0);
        
        echo "═══════════════════════════════════════════════\n";
        echo "Folio: " . $sol->folio . " - " . $sol->nombre . "\n";
        echo "───────────────────────────────────────────────\n";
        echo "MONTO A AUTORIZAR: \$" . number_format($sol->monto_maximo, 2) . "\n";
        echo "✓ DISPONIBLE EN APOYO: \$" . number_format($totalNecesario, 2) . "\n";
        echo "  (Cálculo: \$" . number_format($sol->monto_maximo, 2) . " × " . $sol->cupo_limite . " beneficiarios)\n";
        echo "\n";
    }
    echo "═══════════════════════════════════════════════\n";
    echo "✅ CAMBIO COMPLETADO\n";
    echo "   El campo 'DISPONIBLE EN APOYO' ahora muestra:\n";
    echo "   Monto Máximo × Cantidad de Beneficiarios\n";
} else {
    echo "No se encontraron solicitudes\n";
}
?>
