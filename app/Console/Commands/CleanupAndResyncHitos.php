<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupAndResyncHitos extends Command
{
    protected $signature = 'sync:cleanup-and-resync';
    protected $description = 'Limpiar event_ids para re-sincronizar con timezone correcto';

    public function handle()
    {
        set_time_limit(600); // 10 minutos para cleanup + resync
        
        $this->line("🧹 Limpiando sincronizaciones previas");
        $this->line("====================================\n");

        // Paso 1: Ver estado actual
        $hitosConEvent = DB::table('hitos_apoyo')
            ->whereNotNull('google_calendar_event_id')
            ->count();

        $this->info("[1] Estado actual:");
        $this->line("    Hitos con google_calendar_event_id: {$hitosConEvent}\n");

        // Paso 2: Preguntar confirmación
        if (!$this->confirm('¿Limpiar todos los event_ids para re-sincronización?')) {
            $this->warn('Cancelado');
            return Command::FAILURE;
        }

        // Paso 3: Limpiar
        $this->line("\n[2] Limpiando event_ids...");
        
        $updated = DB::table('hitos_apoyo')
            ->whereNotNull('google_calendar_event_id')
            ->update([
                'google_calendar_event_id' => null,
                'google_calendar_sync' => 0,
                'ultima_sincronizacion' => null,
            ]);

        $this->info("    ✅ Limpiados: {$updated} hitos\n");

        // Paso 4: Verificar limpieza
        $hitosLimpios = DB::table('hitos_apoyo')
            ->whereNull('google_calendar_event_id')
            ->count();

        $this->info("[3] Verificación:");
        $this->info("    ✅ Hitos listos para re-sincronizar: {$hitosLimpios}");
        $this->line("    Timezone: " . config('app.timezone'));

        $this->newLine();
        $this->warn("⚠️  IMPORTANTE: Las fechas siguen siendo incorrectas en la BD");
        $this->warn("   (2025-05-05 en lugar de 2026-03-XX)");
        $this->line("\n   Ejecuta después:");
        $this->line("   $ php artisan sync:pending-hitos");
        $this->line("\n   Para re-crear con timezone correcto (pero fechas incorrectas)");

        return Command::SUCCESS;
    }
}
