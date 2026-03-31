use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Carbon\Carbon;

echo "🧪 TEST: Crear Apoyo con 4 Hitos\n";
echo "================================\n\n";

$apoyo = Apoyo::create([
    'nombre_apoyo' => 'TEST_4_HITOS_' . now()->timestamp,
    'tipo_apoyo' => 'Económico',
    'sincronizar_calendario' => 1,
    'recordatorio_dias' => 1,
    'activo' => 1,
    'anio_fiscal' => 2026,
    'cupo_limite' => 1,
    'fecha_inicio' => Carbon::now(),
    'fecha_fin' => Carbon::now()->addMonth(),
]);

echo "✅ Apoyo creado: ID {$apoyo->id_apoyo}\n\n";
echo "Creando 4 hitos...\n";

$fechas = [
    Carbon::parse('2026-04-15'),
    Carbon::parse('2026-04-22'),
    Carbon::parse('2026-04-29'),
    Carbon::parse('2026-05-06'),
];

$hitos_con_event = 0;

foreach ($fechas as $index => $fecha) {
    echo "  [" . ($index + 1) . "] Hito " . ($index + 1) . ": ";
    
    $hito = HitosApoyo::create([
        'fk_id_apoyo' => $apoyo->id_apoyo,
        'nombre_hito' => "Hito " . ($index + 1),
        'fecha_inicio' => $fecha->startOfDay(),
        'fecha_fin' => $fecha->endOfDay(),
        'activo' => 1,
        'es_base' => 0,
        'orden_hito' => $index + 1,
        'fecha_creacion' => Carbon::now(),
    ]);
    
    if ($hito->google_calendar_event_id) {
        echo "✅ ID {$hito->id_hito} → Event: " . substr($hito->google_calendar_event_id, 0, 20);
        $hitos_con_event++;
    } else {
        echo "❌ ID {$hito->id_hito} → SIN EVENT";
    }
    echo "\n";
}

echo "\n📊 RESULTADOS:\n";
echo "   Esperado: 4/4 hitos con Google event (sin duplicados)\n";
echo "   Observado: {$hitos_con_event}/4\n";

if ($hitos_con_event === 4) {
    echo "\n✅ ¡ÉXITO! La solución está funcionando correctamente.\n";
} else {
    echo "\n⚠️  Solo {$hitos_con_event} hitos sincronizaron. Revisar logs.\n";
}
