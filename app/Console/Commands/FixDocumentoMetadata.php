<?php

namespace App\Console\Commands;

use App\Models\Documento;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class FixDocumentoMetadata extends Command
{
    protected $signature = 'fix:documento-metadata {--folio=1013} {--id=11}';
    protected $description = 'Verificar y reparar metadata de documentos con conflicto origen/google_file_id';

    public function handle()
    {
        $folio = $this->option('folio');
        $docId = $this->option('id');

        $this->line('=== SCRIPT: Verificar metadata de documentos ===');
        $this->line('');

        // Buscar documento: fk_folio es el folio directamente en la tabla
        $documento = Documento::where('fk_folio', $folio)
            ->where('id_doc', $docId)
            ->first();

        if (!$documento) {
            $this->error("❌ Documento ID $docId no encontrado en folio $folio");
            return 1;
        }

        $this->info("✅ Documento encontrado: ID $docId en Folio $folio\n");

        $this->line("METADATA ACTUAL:");
        $this->line("  - Origen: " . ($documento->origen_archivo ?? 'NULL'));
        $this->line("  - Ruta: " . ($documento->ruta_archivo ?? 'NULL'));
        $this->line("  - Google File ID: " . ($documento->google_file_id ?? 'NULL'));
        $this->line("  - Google File Name: " . ($documento->google_file_name ?? 'NULL'));
        $this->line('');

        // Verificar si el archivo existe
        $ruta = $documento->ruta_archivo;
        if (!$ruta) {
            $this->error("❌ Sin ruta_archivo");
            return 1;
        }

        $hash = pathinfo($ruta, PATHINFO_FILENAME);
        $this->line("BÚSQUEDA DE ARCHIVO:");
        $this->line("  - Buscando por ruta: $ruta");
        $this->line('');

        // Rutas a verificar
        $paths = [
            storage_path('app/public/' . $ruta),
            storage_path('app/public/' . $ruta . '.pdf'),
            public_path('storage/' . $ruta),
            public_path('storage/' . $ruta . '.pdf'),
        ];

        $fileFound = false;
        $foundPath = null;

        foreach ($paths as $path) {
            $this->line("  ✓ Verificando: $path");
            if (file_exists($path)) {
                $this->info("    -> ENCONTRADO ✅");
                $fileFound = true;
                $foundPath = $path;
                break;
            }
        }

        if (!$fileFound) {
            $this->line('');
            $this->warn("  ❌ Archivo NO encontrado en ubicaciones estándar");
        }

        // Verificar Storage facade
        $this->line("\nVERIFICACIÓN Storage FACADE:");
        if (Storage::disk('public')->exists($ruta)) {
            $this->info("  ✅ Existe en Storage::disk('public')");
            $fileFound = true;
        } else {
            $this->line("  ❌ NO existe en Storage::disk('public')");
        }

        // Búsqueda por glob
        $this->line("\nBÚSQUEDA POR GLOB en storage/app/public/solicitudes/:");
        $glob_pattern = storage_path('app/public/solicitudes/' . basename($ruta, '.pdf') . '*');
        $this->line("  Pattern: $glob_pattern");

        $glob_results = glob($glob_pattern);
        if ($glob_results && count($glob_results) > 0) {
            foreach ($glob_results as $file) {
                $this->info("  ✅ ENCONTRADO: " . str_replace(storage_path('app/public/'), '', $file));
                $fileFound = true;
                $foundPath = $file;
            }
        } else {
            $this->line("  ❌ No se encontraron archivos similares");
        }

        // RESULTADO Y ACCIÓN
        $this->line("\n" . str_repeat("=", 60));
        
        if ($fileFound) {
            $this->info("✅ RESULTADO: Archivo SÍ existe\n");
            $this->line("   Ubicación: $foundPath\n");

            // Determinar origin correcto
            $correctOrigin = 'local';
            $correctGoogleId = null;

            // Actualizar
            $documento->update([
                'origen_archivo' => $correctOrigin,
                'google_file_id' => $correctGoogleId,
            ]);

            $this->info("✏️ ACTUALIZACIÓN BD:");
            $this->line("   - origen_archivo actualizado a: 'local'");
            $this->line("   - google_file_id limpiado (NULL)");
            
            $this->info("\n✅ Metadata reparada exitosamente");
        } else {
            $this->error("❌ RESULTADO: Archivo NO existe\n");
            $this->warn("ACCIONES RECOMENDADAS:\n");
            $this->line("  1. Verificar si el archivo fue movido/eliminado durante aprobación\n");
            $this->line("  2. Buscar en directorio completo:\n");
            $this->line("     dir " . storage_path('app/public/solicitudes\\') . "\n");
            $this->line("  3. Restaurar desde backup si es disponible\n");
            $this->line("  4. Eliminar documento de BD si es test:\n");
            $this->line("     DELETE FROM documentos_expediente WHERE id_documento = $docId;\n");
        }

        $this->line(str_repeat("=", 60));

        return 0;
    }
}
