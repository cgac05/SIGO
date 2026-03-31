<?php

/**
 * Script de prueba para crear eventos en Google Calendar
 * Crea un apoyo con hitos que se sincronizarán automáticamente
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Apoyo;
use App\Models\HitosApoyo;
use App\Services\GoogleCalendarService;
use Carbon\Carbon;

try {
    echo "📋 === CREANDO APOYO DE PRUEBA ===\n\n";
    
    // Crear un apoyo de prueba
    $apoyo = Apoyo::create([
        'nombre_apoyo' => '🧪 PRUEBA - Capacitación JavaScript',
        'descripcion' => 'Evento de prueba para sincronización de calendario',
        'fecha_inicio' => Carbon::now()->addDays(5),
        'fecha_fin' => Carbon::now()->addDays(30),
        'poblacion_objetivo' => 'Jóvenes',
        'beneficiarios_aprox' => 50,
        'status' => 'publicado',
        'recordatorio_dias' => 1,
    ]);
    
    echo "✅ Apoyo creado: #{$apoyo->id_apoyo}\n";
    echo "   Nombre: {$apoyo->nombre_apoyo}\n\n";
    
    // Crear hitos de prueba
    $hitos = [
        [
            'nombre_hito' => 'Inicio del curso',
            'fecha_inicio' => Carbon::now()->addDays(5)->startOfDay(),
            'fecha_fin' => Carbon::now()->addDays(5)->endOfDay(),
        ],
        [
            'nombre_hito' => 'Sesión 1: Fundamentos',
            'fecha_inicio' => Carbon::now()->addDays(10)->startOfDay(),
            'fecha_fin' => Carbon::now()->addDays(10)->endOfDay(),
        ],
        [
            'nombre_hito' => 'Sesión 2: DOM y Eventos',
            'fecha_inicio' => Carbon::now()->addDays(15)->startOfDay(),
            'fecha_fin' => Carbon::now()->addDays(15)->endOfDay(),
        ],
        [
            'nombre_hito' => 'Proyecto Final',
            'fecha_inicio' => Carbon::now()->addDays(25)->startOfDay(),
            'fecha_fin' => Carbon::now()->addDays(25)->endOfDay(),
        ],
        [
            'nombre_hito' => 'Cierre del curso',
            'fecha_inicio' => Carbon::now()->addDays(30)->startOfDay(),
            'fecha_fin' => Carbon::now()->addDays(30)->endOfDay(),
        ],
    ];
    
    foreach ($hitos as $index => $hito) {
        // Generar clave única a partir del nombre
        $clave = strtoupper(str_replace(' ', '_', preg_replace('/[^a-zA-Z0-9\s]/', '', $hito['nombre_hito'])));
        $clave = 'PRUEBA_' . $index . '_' . $clave;
        
        $hitoDB = HitosApoyo::create([
            'fk_id_apoyo' => $apoyo->id_apoyo,
            'nombre_hito' => $hito['nombre_hito'],
            'clave_hito' => $clave,
            'fecha_inicio' => $hito['fecha_inicio'],
            'fecha_fin' => $hito['fecha_fin'],
            'orden_hito' => $index + 1,
            'activo' => 1,
            'es_base' => 0,
        ]);
        
        echo "✅ Hito creado: {$hitoDB->nombre_hito}\n";
        echo "   Clave: {$clave}\n";
        echo "   Inicio: {$hitoDB->fecha_inicio->format('Y-m-d H:i:s')}\n";
        echo "   Fin: {$hitoDB->fecha_fin->format('Y-m-d H:i:s')}\n";
    }
    
    echo "\n🔄 === SINCRONIZANDO A GOOGLE CALENDAR ===\n\n";
    
    // Crear instancia del servicio y sincronizar
    $googleCalendarService = app(GoogleCalendarService::class);
    
    // Sincronizar los eventos creados a Google Calendar para el directivo autenticado (ID 6)
    $resultado = $googleCalendarService->crearEventosApoyo($apoyo->id_apoyo);
    
    if ($resultado) {
        echo "✅ ¡Eventos sincronizados a Google Calendar!\n";
        echo "\n📌 Próximos pasos:\n";
        echo "   1. Abre Google Calendar en tu navegador\n";
        echo "   2. Deberías ver los 5 eventos del curso de prueba\n";
        echo "   3. Los eventos aparecerán en tu calendario predeterminado\n";
        echo "   4. Colores: verde para inicio, azul para sesiones, rojo para final\n";
    } else {
        echo "❌ Error durante la sincronización\n";
    }
    
    echo "\n✨ Script completado\n";
    
} catch (\Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
