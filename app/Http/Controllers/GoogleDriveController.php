<?php

namespace App\Http\Controllers;

use App\Models\GoogleDriveFile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Google_Service_Drive;
use Google_Service_Drive_DriveFile;

class GoogleDriveController extends Controller
{
    protected $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png'];
    protected $maxFileSize = 5242880; // 5MB
    protected $maxRetries = 3;

    /**
     * Obtener token de acceso de Google
     */
    public function getToken(): JsonResponse
    {
        try {
            $user = auth()->user();

            if (!$user || $user->isGoogleTokenExpired()) {
                return response()->json([
                    'error' => 'Token expirado',
                ], 401);
            }

            $token = $user->google_token;

            // Si el token está en formato JSON, decodificar
            if (is_string($token) && strpos($token, '{') === 0) {
                $tokenData = json_decode($token, true);
                $token = $tokenData['access_token'] ?? $token;
            }

            return response()->json([
                'token' => $token,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Descargar archivo desde Google Drive
     */
    public function upload(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'fileId' => 'required|string|max:255',
                'fileName' => 'required|string|max:255',
            ]);

            $user = auth()->user();

            if (!$user) {
                return response()->json([
                    'error' => 'Usuario no autenticado',
                ], 401);
            }

            // Verificar token
            if ($user->isGoogleTokenExpired()) {
                return response()->json([
                    'error' => 'Token de Google expirado. Por favor, reautentícate.',
                    'code' => 'TOKEN_EXPIRED',
                ], 401);
            }

            // Obtener cliente de Google
            $client = $user->getGoogleClient();
            $driveService = new Google_Service_Drive($client);

            // Obtener metadatos del archivo
            $file = $driveService->files->get($validated['fileId'], [
                'fields' => 'id, name, size, mimeType, webContentLink',
            ]);

            // Validaciones
            $this->validateFile($file);

            // Descargar archivo con reintentos
            $content = $this->downloadWithRetry($driveService, $validated['fileId']);

            // Guardar en storage
            $path = $this->saveFile($content, $file->getName(), $user->id);

            // Registrar en base de datos
            $googleFile = GoogleDriveFile::create([
                'user_id' => $user->id_usuario,
                'google_file_id' => $validated['fileId'],
                'file_name' => $file->getName(),
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'storage_path' => $path,
            ]);

            \Log::channel('google_drive')->info('Archivo descargado de Google Drive', [
                'user_id' => $user->id_usuario,
                'google_file_id' => $validated['fileId'],
                'file_name' => $file->getName(),
                'file_size' => $file->getSize(),
            ]);

            return response()->json([
                'success' => true,
                'file' => [
                    'id' => $googleFile->id,
                    'name' => $googleFile->file_name,
                    'size' => $googleFile->formatted_size,
                    'created_at' => $googleFile->created_at->format('Y-m-d H:i:s'),
                ],
                'message' => 'Archivo cargado exitosamente',
            ], 201);

        } catch (\Google_Service_Exception $e) {
            \Log::error('Error en Google Drive API', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            return response()->json([
                'error' => $this->getErrorMessage($e),
            ], $e->getCode() ?: 400);

        } catch (\Exception $e) {
            \Log::error('Error al descargar archivo de Google Drive: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], $e->getCode() ?: 500);
        }
    }

    /**
     * Descargar archivo con reintentos (exponential backoff)
     */
    protected function downloadWithRetry($driveService, $fileId)
    {
        $retries = 0;

        while ($retries < $this->maxRetries) {
            try {
                $response = $driveService->files->get($fileId, ['alt' => 'media']);

                if ($response instanceof Google_Service_Drive_DriveFile) {
                    // Si no es el contenido, reintentar
                    $retries++;
                    if ($retries >= $this->maxRetries) {
                        throw new \Exception('No se pudo obtener el contenido del archivo');
                    }
                    sleep(pow(2, $retries));
                    continue;
                }

                return $response;

            } catch (\Google_Service_Exception $e) {
                $retries++;

                if ($e->getCode() === 429) { // Rate limit
                    if ($retries >= $this->maxRetries) {
                        throw new \Exception('Demasiadas solicitudes. Intenta en unos minutos.');
                    }
                    sleep(pow(2, $retries));
                } elseif ($retries >= $this->maxRetries || $e->getCode() === 403) {
                    throw $e;
                }
            }
        }

        throw new \Exception('No se pudo descargar el archivo tras varios intentos');
    }

    /**
     * Validar archivo
     */
    protected function validateFile($file): void
    {
        if (!$file) {
            throw new \Exception('Archivo no encontrado en Google Drive');
        }

        $extension = strtolower(pathinfo($file->getName(), PATHINFO_EXTENSION));

        if (!in_array($extension, $this->allowedExtensions)) {
            throw new \Exception(
                'Tipo de archivo no permitido. Extensiones válidas: ' .
                implode(', ', array_map('strtoupper', $this->allowedExtensions))
            );
        }

        if ($file->getSize() > $this->maxFileSize) {
            $maxMB = number_format($this->maxFileSize / 1024 / 1024, 2);
            throw new \Exception("Archivo muy grande. Máximo permitido: {$maxMB}MB");
        }
    }

    /**
     * Guardar archivo localmente o en storage
     */
    protected function saveFile($content, $fileName, $userId): string
    {
        $directory = config('services.google_drive.storage_path') . '/' . $userId;
        $filename = uniqid() . '_' . $this->sanitizeFilename($fileName);

        Storage::disk('local')->put($directory . '/' . $filename, $content);

        return $directory . '/' . $filename;
    }

    /**
     * Sanitizar nombre de archivo
     */
    protected function sanitizeFilename($filename): string
    {
        return preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    }

    /**
     * Obtener archivos del usuario
     */
    public function list(): JsonResponse
    {
        try {
            $files = auth()->user()
                ->googleDriveFiles()
                ->latest()
                ->get()
                ->map(fn($file) => [
                    'id' => $file->id,
                    'google_file_id' => $file->google_file_id,
                    'name' => $file->file_name,
                    'size' => $file->formatted_size,
                    'extension' => $file->extension,
                    'created_at' => $file->created_at->format('Y-m-d H:i:s'),
                ]);

            return response()->json([
                'success' => true,
                'files' => $files,
            ]);

        } catch (\Exception $e) {
            \Log::error('Error al obtener archivos de Google Drive: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Eliminar archivo
     */
    public function destroy($fileId): JsonResponse
    {
        try {
            $file = GoogleDriveFile::where('id', $fileId)
                ->where('user_id', auth()->user()->id_usuario)
                ->firstOrFail();

            // Eliminar del storage
            Storage::disk('local')->delete($file->storage_path);

            // Eliminar de BD
            $file->delete();

            \Log::channel('google_drive')->info('Archivo eliminado', [
                'file_id' => $fileId,
                'user_id' => auth()->user()->id_usuario,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado correctamente',
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'error' => 'Archivo no encontrado',
            ], 404);

        } catch (\Exception $e) {
            \Log::error('Error al eliminar archivo: ' . $e->getMessage());

            return response()->json([
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtener mensaje de error amigable
     */
    protected function getErrorMessage(\Google_Service_Exception $e): string
    {
        $code = $e->getCode();

        $messages = [
            401 => 'Token de Google expirado o inválido. Por favor, reautentícate.',
            403 => 'No tienes permiso para acceder a este archivo.',
            404 => 'El archivo no fue encontrado en Google Drive.',
            413 => 'Archivo muy grande. Máximo permitido: 5MB',
            429 => 'Demasiadas solicitudes. Intenta en unos minutos.',
            500 => 'Error en el servidor de Google. Intenta nuevamente.',
        ];

        return $messages[$code] ?? 'Error al procesar la solicitud: ' . $e->getMessage();
    }
}
