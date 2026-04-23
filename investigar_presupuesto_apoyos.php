<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

echo "=== INVESTIGACIÓN: PRESUPUESTO_APOYOS ===\n\n";

// Ver datos de la tabla presupuesto_apoyos
$data = DB::select("
    SELECT TOP 10
        pa.*,
        s.folio,
        a.nombre_apoyo,
        a.monto_maximo
    FROM presupuesto_apoyos pa
    JOIN Solicitudes s ON pa.folio = s.folio
    JOIN Apoyos a ON s.fk_id_apoyo = a.id_apoyo
");

echo "DATOS EN presupuesto_apoyos:\n\n";
foreach ($data as $row) {
    echo "Folio: " . $row->folio . " | Apoyo: " . $row->nombre_apoyo . "\n";
    echo "  - Monto Solicitado: \$" . number_format($row->monto_solicitado, 2) . "\n";
    echo "  - Monto Aprobado: \$" . number_format($row->monto_aprobado ?? 0, 2) . "\n";
    echo "  - Disponible (solicitado - aprobado): \$" . number_format(($row->monto_solicitado - ($row->monto_aprobado ?? 0)), 2) . "\n";
    echo "  - Monto Máximo del Apoyo: \$" . number_format($row->monto_maximo ?? 0, 2) . "\n";
    echo "  - Estado: " . $row->estado . "\n";
    echo "\n";
}

// Ver si hay tabla presupuesto_categorias con totales
echo "\n=== PRESUPUESTO POR CATEGORÍA ===\n\n";
$categories = DB::select("
    SELECT TOP 5 *
    FROM presupuesto_categorias
    LIMIT 5
");

if (count($categories) > 0) {
    echo "Estructura presupuesto_categorias:\n";
    $props = (array)$categories[0];
    foreach (array_keys($props) as $col) {
        echo "  - " . $col . "\n";
    }
} else {
    echo "Sin registros\n";
}
?>
