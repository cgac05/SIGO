<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Carbon\Carbon;

echo "🧪 TEST: Crear Apoyo con 4 Hitos - Verificar eventos únicos\n";
echo "===========================================================\n\n";

try {
    // 1. Crear Apoyo
    echo "[1] Creando apoyo...\n";
    $apoyo = Apoyo::create([
        'nombre_apoyo' => '🧪 TEST 4 HITOS - Verificación',
        'tipo_apoyo' => 'Económico',
        'sincronizar_calendario' => 1,
        'recordatorio_dias' => 1,
        'activo' => 1,
        'anio_fiscal' => 2026,
        'cupo_limite' => 1,
        'fecha_inicio' => Carbon::now(),
        'fecha_fin' => Carbon::now()->addMonth(),
    ]);

    echo "    ✅ Apoyo creado: ID {$apoyo->id_apoyo}\n\n";

    // 2. Crear 4 hitos EN ORDEN SECUENCIAL
    echo "[2] Creando 4 hitos (esto dispara el Observer 4 veces)...\n";
    
    $hito_ids = [];
    $fechas = [
        Carbon::parse('2026-04-15'),
        Carbon::parse('2026-04-22'),
        Carbon::parse('2026-04-29'),
        Carbon::parse('2026-05-06'),
    ];

    foreach ($fechas as $index => $fecha) {
        echo "    Hito " . ($index + 1) . ": ";
        
        $hito = HitosApoyo::create([
            'fk_id_apoyo' => $apoyo->id_apoyo,
            'nombre_hito' => "📋 Hito " . ($index + 1),
            'fecha_inicio' => $fecha->startOfDay(),
            'fecha_fin' => $fecha->endOfDay(),
            'activo' => 1,
            'es_base' => 0,
            'orden_hito' => $index + 1,
            'fecha_creacion' => Carbon::now(),
        ]);

        $hito_ids[] = $hito->id_hito;
        echo "✅ ID {$hito->id_hito}";
        
        if ($hito->google_calendar_event_id) {
            echo " → Event: {$hito->google_calendar_event_id}";
        } else {
            echo " → ⏳ Esperando sincronización...";
        }
        echo "\n";
    }

    echo "\n[3] Verificando resultados...\n\n";

    $apoyo_refresh = Apoyo::with('hitos')->find($apoyo->id_apoyo);
    
    echo "    Apoyo: {$apoyo_refresh->nombre_apoyo}\n";
    echo "    Total hitos: " . count($apoyo_refresh->hitos) . "\n";
    echo "    Hitos con event_id: ";
    
    $con_event = $apoyo_refresh->hitos->filter(fn($h) => $h->google_calendar_event_id)->count();
    echo "{$con_event}\n";

    if ($con_event === 4) {
        echo "\n    ✅ ÉXITO: Cada hito tiene su propio event_id\n";
    } else {
        echo "\n    ⚠️  PARCIAL: Solo {$con_event}/4 hitos tienen event_id\n";
        echo "\n    Detalle:\n";
        foreach ($apoyo_refresh->hitos as $h) {
            $status = $h->google_calendar_event_id ? "✅" : "❌";
            echo "      {$status} Hito {$h->id_hito}: {$h->nombre_hito}\n";
            if ($h->google_calendar_event_id) {
                echo "         Event: {$h->google_calendar_event_id}\n";
            }
        }
    }

    echo "\n📝 ESPERADO: 4 eventos en Google Calendar\n";
    echo "   (uno por cada hito, sin duplicados)\n";
    echo "\n✅ Para verificar: https://calendar.google.com\n";

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
    
    if ($e->getPrevious()) {
        echo "\n   Causa: " . $e->getPrevious()->getMessage() . "\n";
    }
}
?>
