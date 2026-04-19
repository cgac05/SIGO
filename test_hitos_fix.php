<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\Apoyo;

echo "=== TEST: Cargando hitos con orden_hito ===\n\n";

try {
    // Obtener un apoyo con hitos
    $apoyo = Apoyo::find(3);
    
    if (!$apoyo) {
        echo "❌ Apoyo 3 no encontrado\n";
        exit;
    }
    
    echo "✓ Apoyo encontrado: " . $apoyo->nombre_apoyo . "\n\n";
    
    // Cargar hitos ordenados por orden_hito
    $hitos = $apoyo->hitos()->orderBy('orden_hito')->get();
    
    echo "✓ Hitos cargados: " . $hitos->count() . "\n\n";
    
    foreach ($hitos as $hito) {
        echo "  - ID: " . $hito->id_hito . "\n";
        echo "    Nombre: " . $hito->nombre_hito . "\n";
        echo "    Orden: " . $hito->orden_hito . "\n";
        echo "    Fecha Inicio: " . ($hito->fecha_inicio?->format('Y-m-d') ?? 'NULL') . "\n";
        echo "    Fecha Fin: " . ($hito->fecha_fin?->format('Y-m-d') ?? 'NULL') . "\n\n";
    }
    
    echo "✅ TODO FUNCIONANDO CORRECTAMENTE\n";
    
} catch (\Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
?>
