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
     * 
     * Soporta:
     * 1. Archivos locales: solicitudes/nombre.pdf
     * 2. Google Drive: documento ID de Google Drive
     * 3. Tolerante con datos mal marcados en BD
     */
    public function view($path)
    {
        // Sanitizar path
        $path = str_replace(['../', '..\\', '~/'], '', $path);
        $path = str_replace('storage/', '', $path);
        
        \Log::info("DocumentController::view - Path requested: {$path}");
        
        // Intentar obtener el archivo por Storage disk 'public'
        $filePath = null;
        
        // Opción 1: Verificar en storage/app/public/
        if(Storage::disk('public')->exists($path)) {
            $filePath = Storage::disk('public')->path($path);
            \Log::info("DocumentController::view - Found via Storage::disk('public'): {$filePath}");
        }
        
        // Opción 2: Verificar directamente en filesystem (por si hay desincronización de caché)
        if (!$filePath) {
            $possiblePaths = [
                storage_path('app/public/' . $path),
                public_path('storage/' . $path),
            ];
            
            \Log::info("DocumentController::view - Checking filesystem paths", ['paths' => $possiblePaths]);
            
            foreach ($possiblePaths as $testPath) {
                if (file_exists($testPath)) {
                    $filePath = $testPath;
                    \Log::info("DocumentController::view - Found via filesystem: {$filePath}");
                    break;
                }
            }
        }
        
        // Opción 3: Si no se encontró como archivo local, intentar como Google Drive ID
        // Google Drive IDs tienen un patrón específico: ~32-50 caracteres alfanuméricos
        if (!$filePath) {
            $filename = basename($path);
            
            // Detectar si parece un Google File ID (largo string de caracteres)
            if (preg_match('/^[a-zA-Z0-9_-]{20,}$/', $filename) && !str_contains($filename, '.')) {
                // Probablemente es un Google File ID
                \Log::info("DocumentController::view - Detected Google Drive ID pattern: {$filename}");
                
                // Buscar el documento en la BD para verificar
                $doc = \App\Models\Documento::where('ruta_archivo', 'LIKE', '%' . $filename . '%')
                    ->orWhere('google_file_id', $filename)
                    ->first();
                
                if ($doc && $doc->google_file_id) {
                    \Log::info("DocumentController::view - Found in DB as Google Drive: {$doc->google_file_id}");
                    // Es un documento de Google Drive - redirigir a preview
                    return redirect('https://drive.google.com/file/d/' . $doc->google_file_id . '/preview');
                }
                
                // Si no está en BD pero parece un Google Drive ID, intentar de todas formas
                if (preg_match('/^[a-zA-Z0-9_-]{25,}$/', $filename)) {
                    \Log::info("DocumentController::view - Treating as Google Drive ID (direct): {$filename}");
                    return redirect('https://drive.google.com/file/d/' . $filename . '/preview');
                }
            }
        }
        
        // Si aún no se encontró, retornar error
        if (!$filePath) {
            \Log::error("DocumentController::view - File not found", ['path' => $path]);
            return response()->view('errors.documento-no-existe', [
                'path' => $path,
                'mensaje' => 'El archivo solicitado no se encuentra disponible'
            ], 404);
        }
        
        try {
            // SERVIR DIRECTAMENTE
            $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
            \Log::info("DocumentController::view - Serving file", ['path' => $filePath, 'mime' => $mimeType]);
            
            return response()->file($filePath, [
                'Content-Type' => $mimeType,
            ]);
        } catch (\Exception $e) {
            \Log::error("DocumentController::view - Error serving file", ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Error: ' . $e->getMessage()], 400);
        }
    }
}
