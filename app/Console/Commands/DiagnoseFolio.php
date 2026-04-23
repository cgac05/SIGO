<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DiagnoseFolio extends Command
{
    protected $signature = 'diagnose:folio';
    protected $description = 'Diagnose folio issues';

    public function handle()
    {
        $this->info("=== Checking Folios ===");
        
        // Get first clave
        $clave = DB::table('claves_seguimiento_privadas')->first();
        if ($clave) {
            $this->info("Clave folio: " . $clave->folio);
            
            // Check if solicitud exists
            $solicitud = DB::table('Solicitudes')->where('folio', $clave->folio)->first();
            $this->info("Solicitud exists: " . ($solicitud ? 'YES' : 'NO'));
            
            if (!$solicitud) {
                $this->warn("Solicitud NOT FOUND with folio: " . $clave->folio);
                
                // Get some folios from Solicitudes
                $folios = DB::table('Solicitudes')->limit(3)->pluck('folio');
                $this->info("First folios in Solicitudes: " . json_encode($folios->toArray()));
            }
        } else {
            $this->warn("No claves_seguimiento_privadas found");
        }
    }
}
