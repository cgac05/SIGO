<?php

namespace App\Console\Commands;

use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class Test4HitosDebug extends Command
{
    protected $signature = 'test:4hitos-debug';
    protected $description = 'Test with detailed logging to debug event sync';

    public function handle()
    {
        $this->line("🧪 TEST: 4 Hitos CON DEBUG");
        $this->line("==========================\n");

        try {
            // Create apoyo
            $this->line("[1] Creando apoyo...");
            $apoyo = Apoyo::create([
                'nombre_apoyo' => 'DEBUG_4_HITOS_' . now()->timestamp,
                'tipo_apoyo' => 'Económico',
                'sincronizar_calendario' => 1,
                'recordatorio_dias' => 1,
                'activo' => 1,
                'anio_fiscal' => 2026,
                'cupo_limite' => 1,
                'fecha_inicio' => Carbon::now(),
                'fecha_fin' => Carbon::now()->addMonth(),
            ]);
            $this->info("    ✅ Apoyo ID: {$apoyo->id_apoyo}\n");

            // Create ONE hito first to debug
            $this->line("[2] Creando 1 hito para DEBUG...");
            
            $fecha = Carbon::parse('2026-04-15');
            
            $this->line("    Datos del hito:");
            $this->line("      fk_id_apoyo: {$apoyo->id_apoyo}");
            $this->line("      nombre_hito: Hito 1");
            $this->line("      clave_hito: HITO_1");
            $this->line("      fecha_inicio: {$fecha->startOfDay()}");
            $this->line("      fecha_fin: {$fecha->endOfDay()}");
            
            Log::channel('single')->info("TEST_DEBUG: Creando hito...", [
                'apoyo_id' => $apoyo->id_apoyo,
                'nombre' => 'Hito 1',
                'clave' => 'HITO_1',
            ]);
            
            $hito = HitosApoyo::create([
                'fk_id_apoyo' => $apoyo->id_apoyo,
                'nombre_hito' => "Hito 1",
                'clave_hito' => 'HITO_1',
                'fecha_inicio' => $fecha->startOfDay(),
                'fecha_fin' => $fecha->endOfDay(),
                'activo' => 1,
                'es_base' => 0,
                'orden_hito' => 1,
                'fecha_creacion' => Carbon::now(),
            ]);
            
            $this->info("    ✅ Hito creado: ID {$hito->id_hito}");
            
            // Refresh to see if event_id was set
            $hito->refresh();
            
            $this->line("\n    Estado después de refresh:");
            $this->line("      google_calendar_event_id: " . ($hito->google_calendar_event_id ?? "NULL"));
            $this->line("      google_calendar_sync: " . ($hito->google_calendar_sync ?? "NULL"));

            if ($hito->google_calendar_event_id) {
                $this->info("    ✅ Event sincronizado: {$hito->google_calendar_event_id}");
            } else {
                $this->warn("    ❌ Event NO sincronizado");
                $this->newLine();
                $this->line("📝 Posibles problemas:");
                $this->line("   1. Observer no se ejecutó");
                $this->line("   2. Evento HitoCambiado no se disparó");
                $this->line("   3. Listener no procesó el evento");
                $this->line("   4. GoogleCalendarService falló silenciosamente");
                $this->line("\n⚠️  Revisar logs en: storage/logs/laravel.log");
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("   Archivo: " . $e->getFile());
            $this->error("   Línea: " . $e->getLine());
            return Command::FAILURE;
        }
    }
}
