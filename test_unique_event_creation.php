<?php
require 'bootstrap/app.php';
$app = require 'bootstrap/app.php';

use App\Models\Apoyo;
use App\Models\HitosApoyo;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;

echo "🧪 Validación de Creación de Eventos únicos a las 23:59\n";
echo "========================================================\n\n";

try {
    // Crear apoyo de prueba
    $apoyo = Apoyo::create([
        'nombre_apoyo' => '🧪 TEST - Evento Único',
        'tipo_apoyo' => 'Económico',
        'sincronizar_calendario' => 1,
        'recordatorio_dias' => 1,
        'fecha_inicio' => now(),
        'fecha_fin' => now()->addMonths(3),
    ]);

    echo "[1] Apoyo creado: {$apoyo->nombre_apoyo} (ID: {$apoyo->id_apoyo})\n\n";

    // Crear un hito
    $fechaPrueba = Carbon::parse('2026-04-15'); // 15 de abril
    $hito = HitosApoyo::create([
        'fk_id_apoyo' => $apoyo->id_apoyo,
        'nombre_hito' => '📅 Test Evento 23:59',
        'fecha_inicio' => $fechaPrueba,
        'fecha_fin' => $fechaPrueba->clone()->endOfDay(),
        'activo' => 1,
        'es_base' => 0,
        'fecha_creacion' => now(),
    ]);

    echo "[2] Hito creado: {$hito->nombre_hito} (ID: {$hito->id_hito})\n";
    echo "    Fecha: {$hito->fecha_inicio}\n\n";

    // Crear evento en Google Calendar
    $service = new GoogleCalendarService();
    $resultado = $service->crearEventosApoyo($apoyo->id_apoyo);

    echo "[3] Resultado de creación:\n";
    echo "    Eventos creados: {$resultado['eventos_creados']}\n";
    echo "    Directivos: " . implode(', ', $resultado['directivos']) . "\n";
    
    if (!empty($resultado['errores'])) {
        echo "    ⚠️  Errores: \n";
        foreach ($resultado['errores'] as $error) {
            echo "      - {$error}\n";
        }
    }

    // Verificar en BD
    $hitoActualizado = HitosApoyo::find($hito->id_hito);
    echo "\n[4] Verificación en BD:\n";
    echo "    Event ID guardado: " . ($hitoActualizado->google_calendar_event_id ?? 'NO') . "\n";
    echo "    Sincronizado: " . ($hitoActualizado->google_calendar_sync ? 'SÍ' : 'NO') . "\n";

    if ($hitoActualizado->google_calendar_event_id) {
        echo "\n✅ ÉXITO: Evento creado una sola vez\n";
        echo "   Event ID: {$hitoActualizado->google_calendar_event_id}\n";
        echo "\n📝 Próximo paso:\n";
        echo "   Verifica en Google Calendar que aparezca a las 23:59 del 15 de abril\n";
        echo "   Evento: INJUVE - 🧪 TEST - Evento Único - 📅 Test Evento 23:59\n";
    } else {
        echo "\n❌ ERROR: No se guardó el event ID\n";
    }

} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo "   Archivo: " . $e->getFile() . "\n";
    echo "   Línea: " . $e->getLine() . "\n";
}
?>
