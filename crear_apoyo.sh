#!/bin/bash
php artisan tinker <<EOF
use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Illuminate\Support\Carbon;

echo "=== CREANDO APOYO DE VALIDACIÓN ===\n";

// Crear Apoyo
\$apoyo = new Apoyo();
\$apoyo->nombre_apoyo = '✅ VALIDACIÓN - Test Evento Simple';
\$apoyo->descripcion = 'Apoyo para validar creación en Google Calendar';
\$apoyo->sincronizar_calendario = 1;
\$apoyo->recordatorio_dias = 1;
\$apoyo->activo = 1;
\$apoyo->fecha_creacion = Carbon::now();
\$apoyo->save();

echo "✅ Apoyo ID: " . \$apoyo->id_apoyo . "\n";
echo "   Nombre: " . \$apoyo->nombre_apoyo . "\n\n";

// Crear Hito para mañana
\$hito = new HitosApoyo();
\$hito->fk_id_apoyo = \$apoyo->id_apoyo;
\$hito->nombre_hito = '🎯 Validación';
\$hito->descripcion = 'Evento de prueba';
\$hito->fecha_inicio = Carbon::tomorrow()->startOfDay();
\$hito->fecha_fin = Carbon::tomorrow()->endOfDay();
\$hito->estado = 'programado';
\$hito->activo = 1;
\$hito->fecha_creacion = Carbon::now();
\$hito->save();

echo "✅ Hito ID: " . \$hito->id_hito . "\n";
echo "   Nombre: " . \$hito->nombre_hito . "\n";
echo "   Fecha: " . \$hito->fecha_inicio->format('Y-m-d') . " (mañana)\n\n";

// Crear evento en Google Calendar
echo "=== CREANDO EN GOOGLE CALENDAR ===\n\n";

\$service = app('GoogleCalendarService');
\$resultado = \$service->crearEventosApoyo(\$apoyo->id_apoyo);

echo "Resultado:\n";
echo "  Eventos creados: " . (\$resultado['eventos_creados'] ?? 0) . "\n";

if (!empty(\$resultado['eventos_ids'])) {
    echo "  ✅ Event IDs:\n";
    foreach (\$resultado['eventos_ids'] as \$id) {
        echo "    - \$id\n";
    }
}

if (!empty(\$resultado['errores'])) {
    echo "  ❌ Errores:\n";
    foreach (\$resultado['errores'] as \$error) {
        echo "    - \$error\n";
    }
}

// Verificar en BD
\$hito = HitosApoyo::find(\$hito->id_hito);
echo "\nGoogle Calendar Event ID en BD: " . \$hito->google_calendar_event_id . "\n";

if (\$hito->google_calendar_event_id) {
    echo "\n✅ VALIDACIÓN EXITOSA - Evento creado!\n";
} else {
    echo "\n❌ ERROR - Event ID no se registró en BD\n";
}

exit();
EOF
