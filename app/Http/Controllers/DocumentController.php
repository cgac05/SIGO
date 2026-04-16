<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;

class DocumentController extends Controller
{
    /**
     * Descargar documento de manera segura
     * Ruta: GET /documentos/descargar/{path}
     */
    public function download($path)
    {
        // Sanitizar path
        $path = str_replace(['../', '..\\', '~/'], '', $path);
        $path = str_replace('storage/', '', $path);
        
        // Verificar que existe
        if(!Storage::disk('public')->exists($path)) {
            return response()->json([
                'error' => 'Archivo no encontrado',
                'path' => $path
            ], 404);
        }
        
        // SERVIR DIRECTAMENTE - COMO EN ADMINISTRATIVO
        $filePath = Storage::disk('public')->path($path);
        return response()->download($filePath);
    }
    
    /**
     * Ver documento en navegador (preview)
     * Ruta: GET /documentos/ver/{path}
     */
    public function view($path)
    {
        // Sanitizar path
        $path = str_replace(['../', '..\\', '~/'], '', $path);
        $path = str_replace('storage/', '', $path);
        
        // Verificar que existe
        if(!Storage::disk('public')->exists($path)) {
            return response()->view('errors.documento-no-existe', [
                'path' => $path,
                'mensaje' => 'El archivo solicitado no se encuentra disponible'
            ], 404);
        }
        
        try {
            // SERVIR DIRECTAMENTE - COMO EN ADMINISTRATIVO
            $filePath = Storage::disk('public')->path($path);
            $mimeType = Storage::disk('public')->mimeType($path);
            
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 400);
        }
    }
}
