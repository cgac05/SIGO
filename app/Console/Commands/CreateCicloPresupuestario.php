<?php

namespace App\Console\Commands;

use App\Models\CicloPresupuestario;
use Illuminate\Console\Command;

class CreateCicloPresupuestario extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'create:ciclo-presupuestario {año_fiscal?} {presupuesto_total?}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Crea un ciclo presupuestario';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $año = $this->argument('año_fiscal') ?? now()->year;
        $presupuesto = $this->argument('presupuesto_total') ?? 100000000.00;

        // Usar query directo para evitar problemas de datetime
        \DB::insert(
            "INSERT INTO ciclos_presupuestarios (año_fiscal, estado, presupuesto_total, fecha_apertura, created_at, updated_at) 
             VALUES (?, ?, ?, GETDATE(), GETDATE(), GETDATE())",
            [$año, 'ABIERTO', floatval($presupuesto)]
        );

        $ciclo = CicloPresupuestario::where('año_fiscal', $año)->first();

        $this->info("✅ Ciclo presupuestario {$año} creado con éxito");
        $this->info("   ID: {$ciclo->id_ciclo}");
        $this->info("   Presupuesto Total: $" . number_format($presupuesto, 2));
        $this->info("   Estado: ABIERTO");

        return 0;
    }
}
