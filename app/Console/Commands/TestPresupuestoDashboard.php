<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CicloPresupuestario;
use App\Models\PresupuestoCategoria;

class TestPresupuestoDashboard extends Command
{
    protected $signature = 'test:presupuesto-dashboard {--ciclo=2026}';
    protected $description = 'Prueba que el dashboard presupuestación tiene todos los datos';

    public function handle()
    {
        $año = $this->option('ciclo');
        
        $this->info("🔍 Verificando datos del dashboard presupuestación para ciclo {$año}...\n");
        
        // Obtener ciclo
        $ciclo = CicloPresupuestario::where('ano_fiscal', $año)->first();
        
        if (!$ciclo) {
            $this->error("❌ Ciclo {$año} no encontrado");
            return 1;
        }
        
        $this->info("✅ Ciclo encontrado:");
        $this->line("   - ID: {$ciclo->id_ciclo}");
        $this->line("   - Presupuesto Total: \${$ciclo->presupuesto_total}");
        $this->line("   - Estado: {$ciclo->estado}\n");
        
        // Obtener categorías
        $categorias = PresupuestoCategoria::where('id_ciclo', $ciclo->id_ciclo)->get();
        
        if ($categorias->count() === 0) {
            $this->error("❌ Sin categorías encontradas");
            return 1;
        }
        
        $this->info("✅ Categorías encontradas: {$categorias->count()}");
        foreach ($categorias as $cat) {
            $gastado = $cat->presupuesto_anual - $cat->disponible;
            $porcentaje = ($gastado / $cat->presupuesto_anual) * 100;
            $this->line("   - {$cat->nombre}: \${$cat->presupuesto_anual} (Gastado: \${$gastado}, {$porcentaje}%)");
        }
        
        $this->info("\n✅ Datos validados correctamente");
        $this->info("✅ Dashboard debería renderizar sin errores");
        
        return 0;
    }
}
