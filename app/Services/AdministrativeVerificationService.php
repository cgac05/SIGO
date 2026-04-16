<?php

namespace App\Services;

use App\Models\Documento;
use App\Models\Solicitud;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/**
 * Servicio para gestión de verificación administrativa de documentos.
 * 
 * Responsable de:
 * - Cargar y mostrar documentos de múltiples orígenes
 * - Verificar y aprobar/rechazar documentos
 * - Generar tokens de QR verificacion
 * - Manejar errores de acceso
 */
class AdministrativeVerificationService
{
    /**
     * Obtiene una solicitud con todos sus documentos para revisión
     */
    public function getSolicitudForReview(int $folio, ?int $filtroApoyo = null): ?Solicitud
    {
        $solicitud = Solicitud::with(['beneficiario', 'apoyo', 'documentos'])
            ->where('folio', $folio)
            ->first();

        if ($solicitud && $filtroApoyo && $solicitud->fk_id_apoyo !== $filtroApoyo) {
            return null;
        }

        return $solicitud;
    }

    /**
     * Obtiene la URL o contenido para visualizar un documento
     * 
     * Maneja documentos legacy que pueden no tener origen_archivo establecido
     */
    public function getDocumentAccessUrl(Documento $documento): string|array
    {
        // Si origen_archivo está establecido, usar su valor
        if ($documento->origen_archivo) {
            if ($documento->isLocal()) {
                return $this->getLocalDocumentUrl($documento);
            }
            if ($documento->isFromDrive()) {
                return $this->getGoogleDriveAccessUrl($documento);
            }
        }

        // Fallback para documentos legacy (origen_archivo = NULL)
        // Detectar por ruta o por google_file_id
        if ($documento->google_file_id) {
            return $this->getGoogleDriveAccessUrl($documento);
        }

        if ($documento->ruta_archivo) {
            return $this->getLocalDocumentUrl($documento);
        }

        throw new \Exception('Documento sin ruta de acceso especificada. Origen: ' . ($documento->origen_archivo ?? 'NULL'));
    }

    /**
     * Obtiene URL firmada para documento local
     */
    private function getLocalDocumentUrl(Documento $documento): string
    {
        if (!$documento->ruta_archivo) {
            throw new \Exception('Documento local sin ruta especificada');
        }

        // Generar URL temporal firmada si está en storage public
        if (Storage::disk('public')->exists($documento->ruta_archivo)) {
            return Storage::disk('public')->url($documento->ruta_archivo);
        }

        throw new \Exception('Archivo local no encontrado: ' . $documento->ruta_archivo);
    }

    /**
     * Obtiene link de visualización desde Google Drive
     */
    private function getGoogleDriveAccessUrl(Documento $documento): string
    {
        if (!$documento->google_file_id) {
            throw new \Exception('Documento de Drive sin ID especificado');
        }

        // URL de preview de Google Drive
        return 'https://drive.google.com/file/d/' . $documento->google_file_id . '/preview';
    }

    /**
     * Verifica un documento (acepta/rechaza)
     */
    public function verifyDocument(Documento $documento, string $status, string $observations = ''): bool
    {
        if (!in_array($status, ['aceptado', 'rechazado'], true)) {
            throw new \Exception('Estado de verificación inválido: ' . $status);
        }

        // Validar observaciones obligatorias en rechazo
        if ($status === 'rechazado' && !trim($observations)) {
            throw new \Exception('Las observaciones son obligatorias para rechazar un documento');
        }

        // Generar token de verificación solo para documentos aceptados
        $verificationToken = null;
        if ($status === 'aceptado') {
            $verificationToken = $this->generateVerificationToken(
                $documento->fk_folio,
                0, // No registramos admin por ahora
                $documento->id_doc
            );
        }

        $documento->update([
            'admin_status' => $status,
            'admin_observations' => $observations,
            'verification_token' => $verificationToken,
            'fecha_verificacion' => now(),
        ]);

        // Cambiar estado de la SOLICITUD basado en la decisión
        $solicitud = $documento->solicitud;
        if ($solicitud) {
            if ($status === 'rechazado') {
                // Rechazar TODA la solicitud (id=4)
                $solicitud->update([
                    'fk_id_estado' => 4, // Rechazada
                    'observaciones_internas' => $observations
                ]);

                // Notificar al beneficiario sobre el rechazo
                event(new \App\Events\SolicitudRechazada($solicitud, $observations));
            } elseif ($status === 'aceptado') {
                // Verificar si TODOS los documentos requeridos están aceptados
                $documentsCount = $solicitud->documentos()->count();
                $acceptedCount = $solicitud->documentos()
                    ->where('admin_status', 'aceptado')
                    ->count();

                if ($documentsCount > 0 && $documentsCount === $acceptedCount) {
                    // Todos aprobados → cambiar a "Aprobada" (id=4) para el directivo
                    // ID 4 = "Aprobado" en Cat_EstadosSolicitud
                    $solicitud->update(['fk_id_estado' => 4]);
                }
            }
        }

        return true;
    }

    /**
     * Genera token único para validación QR
     * 
     * Token format: hash(folio + admin_id + documento_id + timestamp + secret_key)
     */
    private function generateVerificationToken(int $folio, int $adminId, int $docId): string
    {
        $secretKey = config('app.encryption_key_qr', config('app.key'));
        $timestamp = now()->timestamp;
        
        $data = "{$folio}:{$adminId}:{$docId}:{$timestamp}:{$secretKey}";
        return hash('sha256', $data);
    }

    /**
     * Valida un token de verificación (para QR)
     */
    public function validateVerificationToken(string $token): ?Documento
    {
        return Documento::where('verification_token', $token)
            ->where('admin_status', 'aceptado')
            ->first();
    }

    /**
     * Obtiene solicitudes pendientes agrupadas por apoyo
     */
    /**
     * Obtiene solicitudes pendientes de revisión administrativa
     * Agrupa documentos pendientes o no verificados aún
     */
    public function getSolicitudesPendientes(int $limit = 50): array
    {
        // Buscar documentos que NO han sido verificados (admin_status IS NULL o pendiente)
        $documentosPendientes = Documento::query()
            ->where(function ($query) {
                $query->whereNull('admin_status')
                      ->orWhere('admin_status', '=', 'pendiente');
            })
            ->with(['solicitud.apoyo', 'solicitud.beneficiario', 'tipoDocumento'])
            ->orderBy('fecha_carga', 'desc')
            ->limit($limit)
            ->get();

        // Si no hay documentos sin verificar, buscar por estado de solicitud "En revisión"
        if ($documentosPendientes->isEmpty()) {
            $documentosPendientes = Documento::query()
                ->with(['solicitud.apoyo', 'solicitud.beneficiario', 'tipoDocumento'])
                ->whereHas('solicitud', function ($query) {
                    $query->whereIn('fk_id_estado', [1, 2]); // Pendiente o En revisión
                })
                ->orderBy('fecha_carga', 'desc')
                ->limit($limit)
                ->get();
        }

        // Agrupar por folio de solicitud
        $solicitudes = [];
        foreach ($documentosPendientes as $doc) {
            $folio = $doc->fk_folio;
            if (!isset($solicitudes[$folio])) {
                $solicitudes[$folio] = [
                    'solicitud' => $doc->solicitud,
                    'documentos' => [],
                    'apoyo' => $doc->solicitud->apoyo,
                ];
            }
            $solicitudes[$folio]['documentos'][] = $doc;
        }

        return array_values($solicitudes);
    }

    /**
     * Obtiene filtros disponibles de apoyos (solo para documentos pendientes)
     */
    public function getApoyosFiltros(): array
    {
        return Documento::query()
            ->where(function ($query) {
                $query->whereNull('admin_status')
                      ->orWhere('admin_status', '=', 'pendiente');
            })
            ->with('solicitud.apoyo')
            ->get()
            ->pluck('solicitud.apoyo')
            ->unique('id_apoyo')
            ->pluck('nombre_apoyo', 'id_apoyo')
            ->toArray();
    }

    /**
     * Obtiene estadísticas de verificación
     */
    /**
     * Estadísticas de verificación de documentos
     */
    public function getVerificationStats(): array
    {
        return [
            'pendientes' => Documento::query()
                ->where(function ($query) {
                    $query->whereNull('admin_status')
                          ->orWhere('admin_status', '=', 'pendiente');
                })
                ->count(),
            'aceptados' => Documento::where('admin_status', 'aceptado')->count(),
            'rechazados' => Documento::where('admin_status', 'rechazado')->count(),
        ];
    }
}
