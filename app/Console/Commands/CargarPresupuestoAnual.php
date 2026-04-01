<?php

namespace App\Console\Commands;

use App\Models\PresupuestoCategoria;
use App\Models\CicloPresupuestario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CargarPresupuestoAnual extends Command
{
    protected $signature = 'presupuesto:cargar {--año= : Año fiscal a cargar (defecto: año actual)}';
    protected $description = 'Cargar presupuesto inicial por categoría para un año fiscal';

    public function handle()
    {
        $ano = $this->option('año') ?? now()->year;

        // Verificar si ya existe ciclo
        $cicloExistente = CicloPresupuestario::where('ano_fiscal', $ano)->first();
        
        if ($cicloExistente) {
            $this->error("❌ Ya existe ciclo presupuestario para {$ano}");
            return;
        }

        $this->info("📊 Cargando presupuesto para año fiscal {$ano}...");

        // Datos por defecto (pueden ser inyectados o editados por usuario)
        $categorias = [
            [
                'nombre' => 'Becas y Asistencia Educativa',
                'presupuesto' => 25000000,
            ],
            [
                'nombre' => 'Programas de Empleo Joven',
                'presupuesto' => 35000000,
            ],
            [
                'nombre' => 'Vivienda y Desarrollo Comunitario',
                'presupuesto' => 20000000,
            ],
            [
                'nombre' => 'Actividades Culturales y Deportivas',
                'presupuesto' => 12000000,
            ],
            [
                'nombre' => 'Salud y Bienestar',
                'presupuesto' => 8000000,
            ],
        ];

        DB::beginTransaction();
        try {
            $totalPresupuesto = 0;

            // Crear cada categoría
            foreach ($categorias as $cat) {
                PresupuestoCategoria::create([
                    'ano_fiscal' => $ano,
                    'nombre_categoria' => $cat['nombre'],
                    'presupuesto_inicial' => $cat['presupuesto'],
                    'reservado' => 0,
                    'aprobado' => 0,
                    'estado' => 'ABIERTO',
                    'creada_por' => auth()->id() ?? 1,
                ]);

                $totalPresupuesto += $cat['presupuesto'];

                $this->line("✅ {$cat['nombre']}: \$" . number_format($cat['presupuesto'], 2));
            }

            // Crear ciclo presupuestario
            CicloPresupuestario::create([
                'ano_fiscal' => $ano,
                'estado' => 'ABIERTO',
                'fecha_inicio' => "{$ano}-01-01",
                'fecha_cierre' => null,
                'presupuesto_total_inicial' => $totalPresupuesto,
                'presupuesto_total_aprobado' => 0,
                'creada_por' => auth()->id() ?? 1,
            ]);

            DB::commit();

            $this->info("\n✨ Presupuesto cargado exitosamente");
            $this->info("📈 Total presupuestado: \$" . number_format($totalPresupuesto, 2));
            $this->info("📅 Ciclo fiscal abierto: {$ano}");

        } catch (\Exception $e) {
            DB::rollback();
            $this->error("❌ Error: " . $e->getMessage());
        }
    }
}
