<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class GoogleAvatarService
{
    /**
     * Descargar y guardar avatar de Google localmente
     */
    public static function downloadAndStore(string $googleAvatarUrl, string $userType, string $userId): ?string
    {
        try {
            if (!$googleAvatarUrl || !filter_var($googleAvatarUrl, FILTER_VALIDATE_URL)) {
                return null;
            }

            // Descargar la imagen
            $imageContent = @file_get_contents($googleAvatarUrl);
            if (!$imageContent) {
                return null;
            }

            // Crear directorio si no existe
            $directory = "{$userType}";
            if (!Storage::disk('public')->exists("fotos/{$directory}")) {
                Storage::disk('public')->makeDirectory("fotos/{$directory}");
            }

            // Generar nombre de archivo único
            $filename = "{$userId}_" . Str::random(8) . '.jpg';
            $path = "fotos/{$directory}/{$filename}";

            // Guardar la imagen
            Storage::disk('public')->put($path, $imageContent);

            return $path;
        } catch (\Exception $e) {
            \Log::error('Error downloading Google avatar', [
                'url' => $googleAvatarUrl,
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Obtener URL pública de la foto almacenada
     */
    public static function getStorageUrl(string $storagePath): string
    {
        return asset("storage/{$storagePath}");
    }
}
