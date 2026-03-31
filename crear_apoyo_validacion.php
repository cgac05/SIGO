<?php
/**
 * Crear Apoyo de Validación con 1 Hito
 * Propósito: Validar que el evento se carga en Google Calendar en la fecha correcta
 */

require_once 'bootstrap/app.php';

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

// Bootear la aplicación
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Illuminate\Support\Carbon;

echo "=== CREANDO APOYO DE VALIDACIÓN ===\n\n";

// 1. Crear el Apoyo
$apoyo = new Apoyo();
$apoyo->nombre_apoyo = '✅ VALIDACIÓN - Test Evento';
$apoyo->descripcion = 'Apoyo de prueba para validar que los eventos se crean correctamente en Google Calendar';
$apoyo->sincronizar_calendario = 1; // Sí sincronizar
$apoyo->recordatorio_dias = 1; // 1 día antes
$apoyo->activo = 1;
$apoyo->fecha_creacion = Carbon::now();
$apoyo->save();

echo "✅ Apoyo creado:\n";
echo "   ID: {$apoyo->id_apoyo}\n";
echo "   Nombre: {$apoyo->nombre_apoyo}\n";
echo "   Sincronizar: Sí\n";
echo "   Recordatorio: 1 día antes\n\n";

// 2. Crear 1 hito para mañana (para que sea visible rápido)
$hito = new HitosApoyo();
$hito->fk_id_apoyo = $apoyo->id_apoyo;
$hito->nombre_hito = '🎯 Evento de Validación';
$hito->descripcion = 'Hito único para validar creación en Google Calendar';
$hito->fecha_inicio = Carbon::tomorrow()->startOfDay(); // Mañana
$hito->fecha_fin = Carbon::tomorrow()->endOfDay();
$hito->estado = 'programado';
$hito->activo = 1;
$hito->fecha_creacion = Carbon::now();
$hito->save();

echo "✅ Hito creado:\n";
echo "   ID: {$hito->id_hito}\n";
echo "   Nombre: {$hito->nombre_hito}\n";
echo "   Fecha: {$hito->fecha_inicio}\n";
echo "   (Mañana: " . Carbon::tomorrow()->format('Y-m-d') . ")\n\n";

// 3. Ahora probar crear el evento en Google Calendar
echo "=== PROBANDO CREACIÓN EN GOOGLE CALENDAR ===\n\n";

$googleCalendarService = app('GoogleCalendarService');
$resultado = $googleCalendarService->crearEventosApoyo($apoyo->id_apoyo);

echo "Resultado:\n";
echo "   Eventos creados: " . ($resultado['eventos_creados'] ?? 0) . "\n";
if (!empty($resultado['errores'])) {
    echo "   Errores: " . implode(", ", $resultado['errores']) . "\n";
} else {
    echo "   ✅ SIN ERRORES\n";
}

if (!empty($resultado['eventos_ids'])) {
    echo "   IDs de eventos:\n";
    foreach ($resultado['eventos_ids'] as $id) {
        echo "     - {$id}\n";
    }
}

// 4. Verificar en BD que se guardó el event ID
echo "\n=== VERIFICANDO EN BASE DE DATOS ===\n\n";

$hito_updatedado = HitosApoyo::find($hito->id_hito);
echo "Hito ID: {$hito_actualizado->id_hito}\n";
echo "Google Event ID: {$hito_actualizado->google_calendar_event_id}\n";

if ($hito_actualizado->google_calendar_event_id) {
    echo "\n✅ VALIDACIÓN EXITOSA\n";
    echo "El evento se creó en Google Calendar y se registró en BD.\n";
    echo "\nPróximo paso: Verificar en Google Calendar\n";
    echo "https://calendar.google.com\n";
    echo "Buscar: " . Carbon::tomorrow()->format('Y-m-d') . "\n";
} else {
    echo "\n❌ ERROR: El evento no se creó en Google Calendar.\n";
}

?>
