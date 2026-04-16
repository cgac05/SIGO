<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixDocumentoMetadataSQL extends Command
{
    protected $signature = 'fix:documento-metadata-sql {--folio=1013} {--id=11}';
    protected $description = 'Corregir metadata de documento marcado como google_drive pero sin google_file_id';

    public function handle()
    {
        $folio = $this->option('folio');
        $docId = $this->option('id');

        $this->line('=== CORRECCIÓN BD: Documento Folio ' . $folio . ' ID ' . $docId . ' ===');
        $this->line('');

        // Antes: Mostrar estado actual
        $documento = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where('id_doc', $docId)
            ->first();

        if (!$documento) {
            $this->error("❌ Documento no encontrado");
            return 1;
        }

        $this->info("ANTES:");
        $this->line("  - origen_archivo: " . ($documento->origen_archivo ?? 'NULL'));
        $this->line("  - google_file_id: " . ($documento->google_file_id ?? 'NULL'));
        $this->line("  - ruta_archivo: " . ($documento->ruta_archivo ?? 'NULL'));
        $this->line('');

        // ACCIÓN: Corregir metadata
        // Si el archivo no existe en storage pero está marcado como google_drive
        // Y no tiene google_file_id, entonces está definitivamente dañado
        
        DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where('id_doc', $docId)
            ->update([
                'origen_archivo' => 'local',  // Era incorrecto
                'google_file_id' => null,     // Limpiar
            ]);

        // Después
        $documento = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where('id_doc', $docId)
            ->first();

        $this->info("DESPUÉS:");
        $this->line("  - origen_archivo: " . ($documento->origen_archivo ?? 'NULL'));
        $this->line("  - google_file_id: " . ($documento->google_file_id ?? 'NULL'));
        $this->line("  - ruta_archivo: " . ($documento->ruta_archivo ?? 'NULL'));
        $this->line('');

        $this->info("✅ Metadata corregida");
        $this->warn("\n⚠️  NOTA: El archivo aún no existe en disk. Recomendaciones:");
        $this->line("  1. El archivo fue eliminado o no se guardó correctamente");
        $this->line("  2. Puede restaurarse desde backup si está disponible");
        $this->line("  3. O el usuario debe re-cargar el documento");

        return 0;
    }
}
