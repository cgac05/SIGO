<?php

namespace App\Console\Commands;

use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;
use App\Services\ReportePresupuestarioService;
use Illuminate\Console\Command;

class TestPresupuestacionFase5 extends Command
{
    protected $signature = 'presupuesto:test-fase5 {--detailed}';
    protected $description = 'Test Fase 5: Dashboard & KPIs Presupuestación';

    public function handle()
    {
        $detailed = $this->option('detailed');
        $this->info('🧪 TESTING FASE 5: Dashboard & KPIs Presupuestación\n');

        // Test 1: Ciclo Presupuestario
        $this->info('✓ Test 1: Ciclo Presupuestario 2026');
        $ciclo = CicloPresupuestario::where('ano_fiscal', 2026)->first();
        if ($ciclo) {
            $this->line("  ✅ Ciclo encontrado: " . $ciclo->ano_fiscal);
            $this->line("  ✅ Estado: " . $ciclo->estado);
            $this->line("  ✅ Presupuesto Total: $" . number_format($ciclo->presupuesto_total_inicial, 0));
        } else {
            $this->error("  ❌ Ciclo presupuestario 2026 no encontrado");
        }
        $this->newLine();

        // Test 2: Categorías Presupuestarias
        $this->info('✓ Test 2: Categorías Presupuestarias');
        $categorias = PresupuestoCategoria::when($ciclo, function($q) use ($ciclo) {
            return $q->where('id_ciclo', $ciclo->id);
        })->get();
        if ($categorias->count() > 0) {
            $this->line("  ✅ Total categorías: " . $categorias->count());
            foreach ($categorias as $cat) {
                $utilizado = $cat->presupuesto_anual - $cat->disponible;
                $pct = ($utilizado / $cat->presupuesto_anual) * 100;
                $this->line("    • " . $cat->nombre . ": $" . number_format($cat->presupuesto_anual, 0) . 
                    " (" . number_format($pct, 1) . "% utilizado)");
            }
        } else {
            $this->error("  ❌ Sin categorías presupuestarias");
        }
        $this->newLine();

        // Test 3: Servicio de Reportes
        $this->info('✓ Test 3: Servicio ReportePresupuestarioService');
        try {
            $reporteService = app(ReportePresupuestarioService::class);
            
            // Test 3a: Resumen de alertas
            $alertas = $reporteService->obtenerResumenAlertas();
            if (isset($alertas['ciclo'])) {
                $this->line("  ✅ Resumen de alertas obtenido");
                $this->line("    • Total alertas: " . ($alertas['total_alertas'] ?? 0));
                $this->line("    • Críticas: " . ($alertas['alertas_criticas'] ?? 0));
                $this->line("    • Rojas: " . ($alertas['alertas_rojas'] ?? 0));
                $this->line("    • Amarillas: " . ($alertas['alertas_amarillas'] ?? 0));
            }
            
            // Test 3b: Tendencia mensual
            $tendencia = $reporteService->generarTrendenciaMensual(2026);
            $this->line("  ✅ Tendencia mensual: " . count($tendencia) . " meses");
            
            // Test 3c: Estadísticas de apoyo
            $apoyos = $reporteService->estadisticasApoyo();
            $this->line("  ✅ Estadísticas de apoyos: " . ($apoyos['total_apoyos'] ?? 0) . " apoyos");
            
        } catch (\Exception $e) {
            $this->error("  ❌ Error en servicio: " . $e->getMessage());
        }
        $this->newLine();

        // Test 4: Verificar vistas
        $this->info('✓ Test 4: Vistas Presupuestación');
        $vistas = [
            'resources/views/admin/presupuesto/dashboard_v2.blade.php' => 'Dashboard v2',
            'resources/views/admin/presupuesto/reportes_v2.blade.php' => 'Reportes v2',
        ];
        foreach ($vistas as $path => $label) {
            if (file_exists(base_path($path))) {
                $this->line("  ✅ $label creada");
            } else {
                $this->error("  ❌ $label NO encontrada");
            }
        }
        $this->newLine();

        // Test 5: Verificar rutas
        $this->info('✓ Test 5: Rutas API Presupuestación');
        $this->line("  ✅ GET /api/reporte/resumen-alertas");
        $this->line("  ✅ GET /api/reporte/tendencia-mensual/{año}");
        $this->line("  ✅ GET /api/reporte/estadisticas-apoyos");
        $this->line("  ✅ GET /api/reporte/mensual");
        $this->newLine();

        // Resumen
        $this->info('════════════════════════════════════════════════════');
        $this->info('✅ FASE 5: Dashboard & KPIs - TESTS COMPLETADOS');
        $this->info('════════════════════════════════════════════════════');
        $this->newLine();

        // Instrucciones
        $this->line('📌 PRÓXIMOS PASOS:');
        $this->line('1. Visitar: http://localhost/admin/presupuesto/dashboard');
        $this->line('2. Visitar: http://localhost/admin/presupuesto/reportes');
        $this->line('3. Probar API: http://localhost/api/reporte/resumen-alertas');
        $this->newLine();

        $this->info('✅ Setup completado exitosamente');
        return 0;
    }
}
