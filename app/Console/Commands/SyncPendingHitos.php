<?php

namespace App\Console\Commands;

use App\Models\HitosApoyo;
use App\Services\GoogleCalendarService;
use Illuminate\Console\Command;

class SyncPendingHitos extends Command
{
    protected $signature = 'sync:pending-hitos {--force : Force sync even if event_id exists}';
    protected $description = 'Sincronizar hitos que no tienen google_calendar_event_id';

    public function handle()
    {
        set_time_limit(600); // 10 minutos para sincronizar 81+ hitos × Google API calls
        
        $this->info("🔄 Sincronizando hitos pendientes a Google Calendar");
        $this->line("================================================\n");

        // Get hitos sin google_calendar_event_id
        $hitoPendientes = HitosApoyo::whereNull('google_calendar_event_id')
            ->where('activo', 1)
            ->with(['apoyo' => fn($q) => $q->where('sincronizar_calendario', 1)])
            ->get();

        if ($hitoPendientes->isEmpty()) {
            $this->warn("✅ No hay hitos pendientes de sincronización");
            return Command::SUCCESS;
        }

        $this->line("📋 Encontrados: " . $hitoPendientes->count() . " hitos pendientes\n");

        $googleCalendarService = app(GoogleCalendarService::class);
        $sincronizados = 0;
        $errores = 0;

        foreach ($hitoPendientes as $index => $hito) {
            $num = $index + 1;
            $this->line("[{$num}/" . $hitoPendientes->count() . "] Hito {$hito->id_hito}: {$hito->nombre_hito}");

            // Validations
            if (!$hito->apoyo) {
                $this->warn("    ❌ Apoyo no encontrado");
                $errores++;
                continue;
            }

            if (!$hito->apoyo->sincronizar_calendario) {
                $this->warn("    ⏭️  Apoyo no tiene sincronización habilitada");
                continue;
            }

            if (!$hito->fecha_inicio) {
                $this->warn("    ❌ Sin fecha_inicio");
                $errores++;
                continue;
            }

            // Intentar sincronizar
            try {
                $resultado = $googleCalendarService->crearEventoHito($hito->id_hito);

                if ($resultado['exito']) {
                    $this->info("    ✅ Sincronizado: {$resultado['event_id']}");
                    $sincronizados++;
                } else {
                    $this->error("    ❌ Error: {$resultado['error']}");
                    $errores++;
                }
            } catch (\Exception $e) {
                $this->error("    ❌ Excepción: " . $e->getMessage());
                $errores++;
            }
        }

        $this->newLine();
        $this->line("📊 RESULTADOS:");
        $this->info("   ✅ Sincronizados: {$sincronizados}");
        $this->error("   ❌ Errores: {$errores}");
        $this->line("   📍 Pendientes: " . ($hitoPendientes->count() - $sincronizados - $errores) . "\n");

        if ($sincronizados > 0) {
            $this->info("✅ Hitos sincronizados correctamente a Google Calendar");
            return Command::SUCCESS;
        } else {
            $this->warn("⚠️  No se sincronizaron hitos. Verificar OAuth tokens.");
            return Command::FAILURE;
        }
    }
}
