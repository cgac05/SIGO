<?php
/**
 * Script para debuggear el error de creación de evento
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Illuminate\Support\Facades\DB;

echo "=== DEBUG: Creación de Evento ===\n\n";

// 1. Cargar el apoyo
$apoyo_id = 30;
$apoyo = Apoyo::find($apoyo_id);

echo "[1] Apoyo: " . $apoyo->nombre_apoyo . "\n";
echo "[2] Sincronizar: " . $apoyo->sincronizar_calendario . "\n";

// 2. Cargar hitos
echo "\n[3] Cargando hitos...\n";
$hitos = $apoyo->hitos;
echo "    Total de hitos: " . count($hitos) . "\n";

// 3. Iterar los hitos
foreach ($hitos as $idx => $hito) {
    echo "\n[4.$idx] Hito ID: " . $hito->id_hito . "\n";
    echo "        Nombre: " . $hito->nombre_hito . "\n";
    echo "        Fecha inicio type: " . gettype($hito->fecha_inicio) . "\n";
    echo "        Fecha inicio value: " . $hito->fecha_inicio . "\n";
    
    try {
        $fecha = $hito->fecha_inicio->toDateTime();
        echo "        toDateTime() success: " . $fecha->format('Y-m-d H:i:s') . "\n";
    } catch (Exception $e) {
        echo "        ❌ ERROR in toDateTime(): " . $e->getMessage() . "\n";
    }
}

?>
