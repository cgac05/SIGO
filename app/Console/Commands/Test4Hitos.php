<?php

namespace App\Console\Commands;

use App\Models\Apoyo;
use App\Models\HitosApoyo;
use Carbon\Carbon;
use Illuminate\Console\Command;

class Test4Hitos extends Command
{
    protected $signature = 'test:4hitos';
    protected $description = 'Test: Create apoyo with 4 hitos and verify each creates 1 Google event (no duplicates)';

    public function handle()
    {
        $this->line("🧪 TEST: Crear Apoyo con 4 Hitos");
        $this->line("================================\n");

        try {
            // Create apoyo
            $this->line("[1] Creando apoyo...");
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
            $this->info("    ✅ Apoyo creado: ID {$apoyo->id_apoyo}\n");

            // Create 4 hitos
            $this->line("[2] Creando 4 hitos (cada uno dispara el Observer)...");
            
            $fechas = [
                Carbon::parse('2026-04-15'),
                Carbon::parse('2026-04-22'),
                Carbon::parse('2026-04-29'),
                Carbon::parse('2026-05-06'),
            ];

            $hitos_con_event = 0;

            foreach ($fechas as $index => $fecha) {
                $this->line("    [" . ($index + 1) . "] Hito " . ($index + 1) . ": ", 'info');
                
                $hito = HitosApoyo::create([
                    'fk_id_apoyo' => $apoyo->id_apoyo,
                    'nombre_hito' => "Hito " . ($index + 1),
                    'clave_hito' => 'HITO_' . ($index + 1),
                    'fecha_inicio' => $fecha->startOfDay(),
                    'fecha_fin' => $fecha->endOfDay(),
                    'activo' => 1,
                    'es_base' => 0,
                    'orden_hito' => $index + 1,
                    'fecha_creacion' => Carbon::now(),
                ]);
                
                if ($hito->google_calendar_event_id) {
                    $this->comment("✅ ID {$hito->id_hito} → Event: " . substr($hito->google_calendar_event_id, 0, 20));
                    $hitos_con_event++;
                } else {
                    $this->warn("❌ ID {$hito->id_hito} → SIN EVENT");
                }
            }

            $this->newLine();
            $this->line("[3] RESULTADOS:");
            $this->info("    Esperado: 4/4 hitos con event_id (sin duplicados)");
            $this->line("    Observado: {$hitos_con_event}/4\n");

            if ($hitos_con_event === 4) {
                $this->info("    ✅ ¡ÉXITO! La solución está funcionando.");
                $this->newLine();
                $this->line("📝 Verificación en Google Calendar:");
                $this->line("   - Solo deberían aparecer 4 eventos");
                $this->line("   - Uno por cada hito en las fechas especificadas");
                $this->line("   - Hora: 23:59 - 23:59:59 (Mazatlán time)");
                return Command::SUCCESS;
            } else {
                $this->error("    ❌ Solo {$hitos_con_event} de 4 hitos tienen event_id");
                return Command::FAILURE;
            }

        } catch (\Exception $e) {
            $this->error("❌ Error: " . $e->getMessage());
            $this->error("   Archivo: " . $e->getFile() . " línea " . $e->getLine());
            return Command::FAILURE;
        }
    }
}
