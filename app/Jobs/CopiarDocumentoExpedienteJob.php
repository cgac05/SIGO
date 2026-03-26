<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class CopiarDocumentoExpedienteJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(private readonly int $idDocumento)
    {
    }

    public function handle(): void
    {
        if (! Schema::hasTable('Documentos_Expediente')) {
            return;
        }

        $documento = DB::table('Documentos_Expediente')->where('id_doc', $this->idDocumento)->first();
        if (! $documento) {
            return;
        }

        $solicitud = DB::table('Solicitudes')
            ->join('Apoyos', 'Solicitudes.fk_id_apoyo', '=', 'Apoyos.id_apoyo')
            ->where('Solicitudes.folio', $documento->fk_folio)
            ->select(['Solicitudes.folio', 'Solicitudes.fk_id_apoyo', 'Apoyos.nombre_apoyo'])
            ->first();

        if (! $solicitud) {
            return;
        }

        $origen = (string) $documento->ruta_archivo;
        if ($origen === '' || ! Storage::disk('public')->exists($origen)) {
            Log::warning('No se pudo copiar documento a expediente oficial: archivo origen no encontrado.', [
                'id_doc' => $this->idDocumento,
                'ruta_origen' => $origen,
            ]);

            return;
        }

        $slugApoyo = preg_replace('/[^A-Za-z0-9\-_]/', '_', (string) $solicitud->nombre_apoyo);
        $directory = 'expediente_oficial/Expediente_Oficial_SIGO_' . $solicitud->fk_id_apoyo . '_' . $slugApoyo . '/folio_' . $solicitud->folio;

        $extension = pathinfo($origen, PATHINFO_EXTENSION);
        $baseName = pathinfo($origen, PATHINFO_FILENAME);
        $newName = $baseName . '_oficial_' . now()->format('YmdHis') . ($extension ? ('.' . $extension) : '');
        $destino = $directory . '/' . $newName;

        Storage::disk('public')->makeDirectory($directory);
        Storage::disk('public')->copy($origen, $destino);

        $officialFileId = strtoupper(substr(sha1($destino . '|' . $this->idDocumento), 0, 20));
        $webViewLink = Storage::disk('public')->url($destino);

        DB::table('Documentos_Expediente')
            ->where('id_doc', $this->idDocumento)
            ->update([
                'official_file_id' => $officialFileId,
                'webview_link' => $webViewLink,
                'fecha_revision' => now(),
            ]);
    }
}
