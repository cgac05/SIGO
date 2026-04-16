<?php

namespace Database\Seeders;

use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;
use Illuminate\Database\Seeder;

class PresupuestoSeeder extends Seeder
{
    /**
     * Ejecutar el seeder
     */
    public function run(): void
    {
        // Verificar si ya existe ciclo 2026
        $ciclo = CicloPresupuestario::firstOrCreate(
            ['ano_fiscal' => 2026],
            [
                'estado' => 'ABIERTO',
                'presupuesto_total_inicial' => 10000000.00,
                'presupuesto_total' => 10000000.00,
                'fecha_apertura' => now(),
                'fecha_inicio' => '2026-01-01',
                'notas' => 'Ciclo presupuestario 2026 - INJUVE Nayarit',
            ]
        );

        // Crear categorías para el ciclo
        $categorias = [
            ['nombre' => 'Becas Educativas', 'presupuesto' => 5000000.00, 'descripcion' => 'Programa de becas para estudiantes de educación media superior y superior'],
            ['nombre' => 'Equipamiento Tecnológico', 'presupuesto' => 3000000.00, 'descripcion' => 'Computadoras, tablets y dispositivos de conectividad'],
            ['nombre' => 'Capacitación Laboral', 'presupuesto' => 2000000.00, 'descripcion' => 'Talleres de capacitación en habilidades laborales'],
        ];

        foreach ($categorias as $cat) {
            PresupuestoCategoria::firstOrCreate(
                ['nombre' => $cat['nombre'], 'id_ciclo' => $ciclo->id_ciclo],
                [
                    'descripcion' => $cat['descripcion'],
                    'presupuesto_anual' => $cat['presupuesto'],
                    'disponible' => $cat['presupuesto'],
                    'activo' => true,
                ]
            );
        }

        $this->command->info('✅ Ciclo presupuestario 2026 y categorías creados exitosamente');
    }
}
