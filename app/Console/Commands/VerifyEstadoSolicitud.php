<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class VerifyEstadoSolicitud extends Command
{
    protected $signature = 'verify:estado {folio}';
    protected $description = 'Verificar estado actual de una solicitud y actualizar si necesario';

    public function handle()
    {
        $folio = $this->argument('folio');

        $this->line('=== VERIFICACIÓN ESTADO SOLICITUD ===');
        $this->line('');

        // Obtener solicitud
        $solicitud = DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitud) {
            $this->error("❌ Solicitud folio $folio no encontrada");
            return 1;
        }

        // Obtener estado actual
        $estadoActual = DB::table('Cat_EstadosSolicitud')
            ->where('id_estado', $solicitud->fk_id_estado)
            ->first();

        $this->info("Folio: $folio");
        $this->line("  Estado actual ID: {$solicitud->fk_id_estado}");
        $this->line("  Estado nombre: {$estadoActual->nombre_estado}");
        $this->line("");

        // Verificar documentos
        $docsTotal = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->count();

        $docsAceptados = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where('admin_status', 'aceptado')
            ->count();

        $docsRechazados = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where('admin_status', 'rechazado')
            ->count();

        $docsPendientes = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where(function ($q) {
                $q->whereNull('admin_status')
                  ->orWhere('admin_status', 'pendiente');
            })
            ->count();

        $this->line("Documentos:");
        $this->line("  Total: $docsTotal");
        $this->line("  ✅ Aceptados: $docsAceptados");
        $this->line("  ❌ Rechazados: $docsRechazados");
        $this->line("  ⏳ Pendientes: $docsPendientes");
        $this->line("");

        // Verificar si debería estar aprobada
        $deberiaEstarAprobada = ($docsTotal > 0 && $docsAceptados === $docsTotal && $docsRechazados === 0);

        if ($deberiaEstarAprobada && $solicitud->fk_id_estado === 3) {
            $this->warn("⚠️  DETECTADO: Solicitud debería estar APROBADA (ID 4) pero está en SUBSANACIÓN (ID 3)");
            $this->line("Actualizando....");
            
            DB::table('Solicitudes')
                ->where('folio', $folio)
                ->update(['fk_id_estado' => 4]);

            $this->info("✅ Actualizado a estado APROBADO (ID 4)");
        } elseif ($deberiaEstarAprobada && $solicitud->fk_id_estado === 4) {
            $this->info("✅ Estado CORRECTO: Solicitud está como APROBADA");
        } elseif (!$deberiaEstarAprobada && $solicitud->fk_id_estado === 3) {
            $this->warn("⚠️  ESTADO: Solicitud está en SUBSANACIÓN (correcto, hay documentos pendientes)");
        } else {
            $this->line("Estado: " . $estadoActual->nombre_estado);
        }

        $this->line("");
        $this->line("Presupuesto:");
        $this->line("  - presupuesto_confirmado: " . ($solicitud->presupuesto_confirmado ? 'SÍ (1)' : 'NO (0)'));
        $this->line("  - monto_entregado: " . ($solicitud->monto_entregado ?? 'NULL'));

        return 0;
    }
}
