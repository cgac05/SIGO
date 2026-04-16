<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixAllEstadosSolicitudes extends Command
{
    protected $signature = 'fix:all-estados';
    protected $description = 'Buscar y corregir todas las solicitudes con estado incorrecto (ID 3 cuando deberían ser ID 4)';

    public function handle()
    {
        $this->line('=== BÚSQUEDA Y CORRECCIÓN DE ESTADOS ===');
        $this->line('');

        // Buscar solicitudes en estado 3 (En Subsanación) que tengan todos documentos aceptados
        $solicitudesIncorrectas = DB::table('Solicitudes')
            ->where('fk_id_estado', 3)
            ->get();

        if ($solicitudesIncorrectas->isEmpty()) {
            $this->info("✅ No hay solicitudes en estado 3");
            return 0;
        }

        $this->line("Encontradas " . $solicitudesIncorrectas->count() . " solicitudes en estado 3 (En Subsanación)");
        $this->line("");

        $posiblesCorrectas = [];

        foreach ($solicitudesIncorrectas as $solicitud) {
            $docsTotal = DB::table('Documentos_Expediente')
                ->where('fk_folio', $solicitud->folio)
                ->count();

            $docsAceptados = DB::table('Documentos_Expediente')
                ->where('fk_folio', $solicitud->folio)
                ->where('admin_status', 'aceptado')
                ->count();

            $docsRechazados = DB::table('Documentos_Expediente')
                ->where('fk_folio', $solicitud->folio)
                ->where('admin_status', 'rechazado')
                ->count();

            // Si todos están aceptados y ninguno rechazado
            if ($docsTotal > 0 && $docsAceptados === $docsTotal && $docsRechazados === 0) {
                $posiblesCorrectas[] = [
                    'folio' => $solicitud->folio,
                    'total_docs' => $docsTotal,
                    'aceptados' => $docsAceptados,
                ];
            }
        }

        if (empty($posiblesCorrectas)) {
            $this->info("✅ Ninguna solicitud necesita corrección (estado 3 es correcto para todas)");
            return 0;
        }

        $this->warn("⚠️  ENCONTRADAS " . count($posiblesCorrectas) . " solicitudes que deberían estar en estado 4:");
        $this->line("");

        foreach ($posiblesCorrectas as $sol) {
            $this->line("  • Folio {$sol['folio']}: {$sol['aceptados']}/{$sol['total_docs']} docs aceptados");
        }

        $this->line("");

        if ($this->confirm('¿Actualizar todas a estado 4 (Aprobado)?')) {
            $folios = array_column($posiblesCorrectas, 'folio');

            DB::table('Solicitudes')
                ->whereIn('folio', $folios)
                ->update(['fk_id_estado' => 4]);

            $this->info("✅ Actualizadas " . count($posiblesCorrectas) . " solicitudes a estado 4 (Aprobado)");
        } else {
            $this->line("Actualización cancelada");
        }

        return 0;
    }
}
