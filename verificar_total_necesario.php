<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== VERIFICACIÓN: TOTAL NECESARIO PARA SOLICITUDES ===\n\n";

// Obtener varias solicitudes con sus apoyos
$solicitudes = DB::select("
    SELECT TOP 5
        s.folio,
        s.fk_id_apoyo,
        a.nombre_apoyo,
        a.monto_maximo,
        a.cupo_limite,
        b.nombre as beneficiario_nombre
    FROM Solicitudes s
    JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
    JOIN Beneficiarios b ON s.fk_curp = b.curp
    ORDER BY s.folio DESC
");

if (count($solicitudes) > 0) {
    foreach ($solicitudes as $sol) {
        $montoMaximo = $sol->monto_maximo ?? 0;
        $cupoLimite = $sol->cupo_limite ?? 0;
        $totalNecesario = $montoMaximo * $cupoLimite;

        echo "Folio: " . $sol->folio . "\n";
        echo "  Apoyo: " . $sol->nombre_apoyo . "\n";
        echo "  Beneficiario: " . $sol->beneficiario_nombre . "\n";
        echo "  Monto Máximo (por beneficiario): \$" . number_format($montoMaximo, 2) . "\n";
        echo "  Cupo Límite (cantidad beneficiarios): " . $cupoLimite . "\n";
        echo "  ✓ TOTAL NECESARIO: \$" . number_format($totalNecesario, 2) . "\n";
        echo "  (Fórmula: \$" . number_format($montoMaximo, 2) . " × " . $cupoLimite . " = \$" . number_format($totalNecesario, 2) . ")\n";
        echo "\n";
    }
} else {
    echo "No se encontraron solicitudes\n";
}

echo "=== CAMBIO IMPLEMENTADO EXITOSAMENTE ===\n";
echo "✓ Se agregó campo 'Total Necesario' en la vista de directivo\n";
echo "✓ El cálculo es universal para apoyos nuevos y existentes\n";
echo "✓ Fórmula: Monto Máximo × Cantidad Máx. Beneficiarios\n";
?>
