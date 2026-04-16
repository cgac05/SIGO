<?php

namespace App\Observers;

use App\Models\Documento;

class DocumentoObserver
{
    /**
     * Handle the Documento "creating" event - ANTES DE INSERTAR
     */
    public function creating(Documento $documento): void
    {
        // Normalizar ruta: quitar prefijo "storage/" si existe
        if ($documento->ruta_archivo && str_contains($documento->ruta_archivo, 'storage/')) {
            $documento->ruta_archivo = str_replace('storage/', '', $documento->ruta_archivo);
        }
    }

    /**
     * Handle the Documento "updating" event - ANTES DE ACTUALIZAR
     */
    public function updating(Documento $documento): void
    {
        // Normalizar ruta: quitar prefijo "storage/" si existe
        if ($documento->ruta_archivo && str_contains($documento->ruta_archivo, 'storage/')) {
            $documento->ruta_archivo = str_replace('storage/', '', $documento->ruta_archivo);
        }
    }
}
