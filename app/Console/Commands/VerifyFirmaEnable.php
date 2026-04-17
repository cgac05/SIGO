<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyFirmaEnable extends Command
{
    protected $signature = 'verify:firma-enable {folio}';
    protected $description = 'Verificar si una solicitud está lista para firmar';

    public function handle()
    {
        $folio = $this->argument('folio');

        $this->line('=== VERIFICACIÓN: ¿ESTÁ LISTA PARA FIRMAR? ===');
        $this->line('');

        $solicitud = DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitud) {
            $this->error("❌ Solicitud folio $folio no encontrada");
            return 1;
        }

        $estado = DB::table('Cat_EstadosSolicitud')
            ->where('id_estado', $solicitud->fk_id_estado)
            ->first();

        $this->info("Folio: $folio");
        $this->line("  Estado: ID {$solicitud->fk_id_estado} ({$estado->nombre_estado})");
        $this->line("  CUV: " . ($solicitud->cuv ?? 'NO GENERADO'));
        $this->line('');

        // Verificar requisitos para firma
        $puedeFirmar = in_array($solicitud->fk_id_estado, [4, 10]);
        $yaFirmado = !is_null($solicitud->cuv);

        $this->line("Requisitos:");
        
        if ($puedeFirmar) {
            $this->info("  ✅ Estado permite firma (ID {$solicitud->fk_id_estado})");
        } else {
            $this->error("  ❌ Estado NO permite firma (ID {$solicitud->fk_id_estado})");
        }

        if ($yaFirmado) {
            $this->info("  ✅ Ya tiene CUV (ya fue firmado)");
        } else {
            $this->line("  ⏳ Sin CUV (no ha sido firmado)");
        }

        $this->line('');

        if ($puedeFirmar && !$yaFirmado) {
            $this->info("✅ LISTA PARA FIRMAR");
            $this->line("   El directivo puede hacer clic en 'Firmar y Generar CUV'");
        } elseif ($puedeFirmar && $yaFirmado) {
            $this->info("✅ YA FIRMADA");
            $this->line("   CUV: {$solicitud->cuv}");
        } else {
            $this->error("❌ NO LISTA PARA FIRMAR");
            $this->line("   Estado requerido: 4 (Aprobado) O 10 (Documentos Verificados)");
            $this->line("   Estado actual: {$estado->nombre_estado}");
        }

        return $puedeFirmar ? 0 : 1;
    }
}
