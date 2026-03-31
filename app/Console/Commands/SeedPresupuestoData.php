<?php

namespace App\Console\Commands;

use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;
use App\Models\MovimientoPresupuestario;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedPresupuestoData extends Command
{
    protected $signature = 'seed:presupuesto {--ciclo=2026}';
    protected $description = 'Siembra datos de prueba para presupuestación';

    public function handle()
    {
        $año = $this->option('ciclo');
        
        // Obtener ciclo
        $ciclo = CicloPresupuestario::where('año_fiscal', $año)->first();
        
        if (!$ciclo) {
            $this->error("Ciclo fiscal {$año} no encontrado");
            return 1;
        }

        // Categorías INJUVE típicas
        $categorias = [
            [
                'nombre' => 'Becas y Asistencia Educativa',
                'descripcion' => 'Becas para educación superior y capacitación',
                'presupuesto_anual' => 25000000.00,
                'disponible' => 7500000.00,
            ],
            [
                'nombre' => 'Programas de Empleo Joven',
                'descripcion' => 'Capacitación y colocación laboral',
                'presupuesto_anual' => 35000000.00,
                'disponible' => 19250000.00,
            ],
            [
                'nombre' => 'Vivienda y Desarrollo Comunitario',
                'descripcion' => 'Apoyo para vivienda digna',
                'presupuesto_anual' => 20000000.00,
                'disponible' => 3000000.00,
            ],
            [
                'nombre' => 'Actividades Culturales y Deportivas',
                'descripcion' => 'Eventos y programas de cultura y deporte',
                'presupuesto_anual' => 12000000.00,
                'disponible' => 8400000.00,
            ],
            [
                'nombre' => 'Salud y Bienestar',
                'descripcion' => 'Programas de salud preventiva',
                'presupuesto_anual' => 8000000.00,
                'disponible' => 3200000.00,
            ],
        ];

        $existentes = PresupuestoCategoria::where('id_ciclo', $ciclo->id_ciclo)->count();
        
        if ($existentes > 0) {
            $this->info("⚠️  Ya existen " . $existentes . " categorías para este ciclo");
            return 0;
        }

        $this->info("Creando categorías para ciclo {$año}...");
        
        $totalPresupuesto = 0;
        $totalGastado = 0;
        
        foreach ($categorias as $cat) {
            // Insertar usando SQL directo
            DB::insert(
                "INSERT INTO presupuesto_categorias (id_ciclo, nombre, descripcion, presupuesto_anual, disponible, created_at, updated_at) 
                 VALUES (?, ?, ?, ?, ?, GETDATE(), GETDATE())",
                [
                    $ciclo->id_ciclo,
                    $cat['nombre'],
                    $cat['descripcion'],
                    $cat['presupuesto_anual'],
                    $cat['disponible'],
                ]
            );
            
            $gastado = $cat['presupuesto_anual'] - $cat['disponible'];
            $totalPresupuesto += $cat['presupuesto_anual'];
            $totalGastado += $gastado;
            
            $this->line("  ✅ {$cat['nombre']}: \${$cat['presupuesto_anual']}");
            $this->line("     Gastado: \${$gastado} (" . round(($gastado / $cat['presupuesto_anual']) * 100) . "%)");
        }

        $this->info("\n✅ Datos de prueba insertados correctamente");
        $this->info("\nResumen:");
        $this->line("  Categorías: " . count($categorias));
        $this->line("  Total Presupuesto: \$" . number_format($totalPresupuesto, 0));
        $this->line("  Total Gastado: \$" . number_format($totalGastado, 0));
        $this->line("  % Ejecución: " . round(($totalGastado / $totalPresupuesto) * 100) . "%");

        return 0;
    }
}
