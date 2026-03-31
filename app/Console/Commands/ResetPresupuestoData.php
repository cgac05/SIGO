<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ResetPresupuestoData extends Command
{
    protected $signature = 'seed:reset-presupuesto {--ciclo=2026}';
    protected $description = 'Limpia todos los datos de presupuestación y siembra nuevos datos';

    public function handle()
    {
        $año = $this->option('ciclo');
        
        $this->info("🗑️  Limpiando datos de presupuestación para ciclo {$año}...");
        
        // Obtener el ciclo
        $ciclo = DB::table('ciclos_presupuestarios')
            ->where('año_fiscal', $año)
            ->first();
        
        if (!$ciclo) {
            $this->error("❌ Ciclo fiscal {$año} no encontrado");
            return 1;
        }
        
        // Eliminar movimientos (si existen apoyos)
        DB::table('movimientos_presupuestarios')
            ->whereIn('id_presupuesto_apoyo', function ($query) use ($ciclo) {
                $query->select('id_presupuesto_apoyo')
                    ->from('presupuesto_apoyos')
                    ->whereIn('id_categoria', function ($q) use ($ciclo) {
                        $q->select('id_categoria')
                            ->from('presupuesto_categorias')
                            ->where('id_ciclo', $ciclo->id_ciclo);
                    });
            })
            ->delete();
        $this->line("  ✅ Movimientos eliminados");

        // Eliminar apoyos presupuestarios
        DB::table('presupuesto_apoyos')
            ->whereIn('id_categoria', function ($query) use ($ciclo) {
                $query->select('id_categoria')
                    ->from('presupuesto_categorias')
                    ->where('id_ciclo', $ciclo->id_ciclo);
            })
            ->delete();
        $this->line("  ✅ Apoyos eliminados");
        
        // Eliminar categorías
        DB::table('presupuesto_categorias')
            ->where('id_ciclo', $ciclo->id_ciclo)
            ->delete();
        $this->line("  ✅ Categorías eliminadas");
        
        // Ejecutar el seeder
        $this->info("\n📊 Sembrando nuevos datos...");
        $this->call('seed:presupuesto', ['--ciclo' => $año]);
        
        return 0;
    }
}
