<?php

namespace App\Http\Controllers;

use App\Models\Documento;
use App\Models\Solicitud;
use App\Services\AdministrativeVerificationService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para la verificación administrativa de documentos.
 * 
 * Endpoints:
 * - GET  /admin/solicitudes                    Listado de solicitudes pendientes con menú de navegación
 * - GET  /admin/solicitudes/{folio}            Detalle de una solicitud con documentos (view única solicitud)
 * - POST /admin/documentos/{id}/verify         Verificar documento (aceptar/rechazar)
 * - GET  /admin/documentos/{id}/view           Stream del documento (local o Google Drive)
 * - GET  /validacion/{token}                   Validación pública del QR (sin contenido, solo metadata)
 */

class DocumentVerificationController extends Controller
{
    public function __construct(private readonly AdministrativeVerificationService $service)
    {
    }

    /**
     * Listado de solicitudes pendientes con menú de navegación
     * 
     * GET /admin/solicitudes?apoyo=2
     */
    public function index(Request $request)
    {
        $this->authorizeAdmin($request);

        // Obtener filtro de apoyo si está disponible
        $apoyoFilter = $request->query('apoyo', null);

        // Obtener solicitudes pendientes
        $solicitudesPendientes = $this->service->getSolicitudesPendientes(100);

        // Filtrar por apoyo si se especifica
        if ($apoyoFilter) {
            $solicitudesPendientes = array_filter($solicitudesPendientes, function ($item) use ($apoyoFilter) {
                return (int) $item['apoyo']->id_apoyo === (int) $apoyoFilter;
            });
        }

        // Obtener filtros disponibles
        $apoyosFiltros = $this->service->getApoyosFiltros();

        // Estadísticas
        $stats = $this->service->getVerificationStats();

        return view('admin.solicitudes.index', [
            'solicitudesPendientes' => array_values($solicitudesPendientes),
            'apoyosFiltros' => $apoyosFiltros,
            'stats' => $stats,
            'apoyoFilter' => $apoyoFilter,
        ]);
    }

    /**
     * Detalle de una solicitud (vista de verificación de documentos)
     * 
     * GET /admin/solicitudes/{folio}
     */
    public function show(Request $request, int $folio)
    {
        $this->authorizeAdmin($request);

        $apoyoFilter = $request->query('apoyo', null);
        $solicitud = $this->service->getSolicitudForReview($folio, $apoyoFilter ? (int) $apoyoFilter : null);

        if (!$solicitud) {
            abort(404, 'Solicitud no encontrada');
        }

        $documentos = $solicitud->documentos()->orderBy('fk_id_tipo_doc')->get();

        return view('admin.solicitudes.show', [
            'solicitud' => $solicitud,
            'documentos' => $documentos,
            'apoyoFilter' => $apoyoFilter,
        ]);
    }

    /**
     * Visualizar documento (local o Google Drive)
     * 
     * GET /admin/documentos/{id}/view
     * 
     * ESTRATEGIA DE DETECCIÓN:
     * 1. Confiar en isLocal() que detecta: origen='local' O (ruta + NO google_id + NO google_path)
     * 2. Confiar en isFromDrive() que detecta: origen='drive'|'google_drive' + google_id OR solo google_id
     * 3. Si hay conflicto (origen='google_drive' pero google_id=NULL), buscar como local primero
     * 4. Si encuentra local, actualizar BD para marcar como local y servir
     */
    public function viewDocument(Request $request, int $id)
    {
        $this->authorizeAdmin($request);

        $documento = Documento::findOrFail($id);

        try {
            \Log::info("DocumentVerificationController::viewDocument - Iniciando", [
                'doc_id' => $id,
                'origen' => $documento->origen_archivo,
                'ruta' => $documento->ruta_archivo,
                'google_id' => $documento->google_file_id ?? 'NULL',
                'isLocal' => $documento->isLocal(),
                'isFromDrive' => $documento->isFromDrive(),
            ]);

            // ESTRATEGIA 1: Si detecta como local, servir como local
            if ($documento->isLocal() && $documento->ruta_archivo) {
                \Log::info("DocumentVerificationController::viewDocument - Detectado como local");
                return redirect(route('documentos.view', ['path' => $documento->ruta_archivo]));
            }

            // ESTRATEGIA 2: Si detecta como Google Drive Y tiene google_file_id, servir desde Drive
            if ($documento->isFromDrive() && $documento->google_file_id) {
                \Log::info("DocumentVerificationController::viewDocument - Detectado como Google Drive");
                return redirect('https://drive.google.com/file/d/' . $documento->google_file_id . '/preview');
            }

            // ESTRATEGIA 3: CONFLICTO - origen='google_drive' pero google_id=NULL
            // Intentar buscar como local (puede estar mal marcado en BD)
            if ($documento->origen_archivo === 'google_drive' && !$documento->google_file_id && $documento->ruta_archivo) {
                \Log::warning("DocumentVerificationController::viewDocument - CONFLICTO DETECTADO", [
                    'doc_id' => $id,
                    'origen' => $documento->origen_archivo,
                    'ruta' => $documento->ruta_archivo,
                    'google_id' => 'NULL',
                ]);
                
                // Buscar el archivo en storage
                if (\Storage::disk('public')->exists($documento->ruta_archivo) || 
                    file_exists(storage_path('app/public/' . $documento->ruta_archivo))) {
                    
                    \Log::warning("DocumentVerificationController::viewDocument - Archivo encontrado localmente, corrigiendo BD", [
                        'doc_id' => $id,
                        'ruta' => $documento->ruta_archivo,
                    ]);
                    
                    // Corregir la BD
                    $documento->update([
                        'origen_archivo' => 'local',
                        'google_file_id' => null,
                    ]);
                    
                    // Servir como local
                    return redirect(route('documentos.view', ['path' => $documento->ruta_archivo]));
                }
                
                \Log::error("DocumentVerificationController::viewDocument - Conflicto no resuelto", [
                    'doc_id' => $id,
                    'ruta' => $documento->ruta_archivo,
                    'paths_checked' => [
                        storage_path('app/public/' . $documento->ruta_archivo),
                        public_path('storage/' . $documento->ruta_archivo),
                    ]
                ]);
            }

            // ESTRATEGIA 4: Fallback - intentar por servicio administrativo
            $accessUrl = $this->service->getDocumentAccessUrl($documento);
            return redirect($accessUrl);
        } catch (\Exception $e) {
            \Log::error("DocumentVerificationController::viewDocument - Error", [
                'doc_id' => $id,
                'error' => $e->getMessage(),
            ]);
            
            return response()->json([
                'error' => 'No se pudo acceder al documento: ' . $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Verificar documento (aceptar/rechazar)
     * 
     * POST /admin/documentos/{id}/verify
     */
    public function verifyDocument(Request $request, int $id)
    {
        $this->authorizeAdmin($request);

        $documento = Documento::findOrFail($id);

        $validated = $request->validate([
            'status' => ['required', 'in:aceptado,rechazado'],
            'observations' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $this->service->verifyDocument(
                $documento,
                $validated['status'],
                $validated['observations'] ?? ''
            );

            // Retornar respuesta con formato estandarizado
            return response()->json([
                'success' => true,
                'message' => 'Documento verificado correctamente',
                'verification_token' => $documento->refresh()->verification_token,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Validación pública de QR (sin autenticación)
     * Muestra solo metadata del documento verificado
     * 
     * GET /validacion/{token}
     */
    public function validarPublico(string $token)
    {
        $documento = $this->service->validateVerificationToken($token);

        if (!$documento) {
            return view('admin.validacion-fallida', [
                'mensaje' => 'Token de verificación inválido o expirado',
            ]);
        }

        $documento->load(['solicitud.beneficiario', 'solicitud.apoyo', 'tipoDocumento', 'admin']);

        return view('admin.validacion-exitosa', [
            'documento' => $documento,
            'solicitud' => $documento->solicitud,
            'admin' => $documento->admin,
        ]);
    }

    /**
     * Endpoint API para obtener estadísticas (opcional)
     * GET /admin/documentos/stats
     */
    public function getStats(Request $request)
    {
        $this->authorizeAdmin($request);

        return response()->json($this->service->getVerificationStats());
    }

    /**
     * Autoriza que el usuario sea administrador
     */
    private function authorizeAdmin(Request $request): void
    {
        $user = $request->user()?->loadMissing('personal');

        if (!$user || !$user->isPersonal()) {
            abort(403, 'Solo personal autorizado puede acceder a este módulo');
        }

        // Verificar que sea admin (roles 1, 2 o personal con permiso)
        $isAdmin = $user->personal && in_array((int) $user->personal->fk_rol, [1, 2, 3], true);

        abort_unless($isAdmin, 403, 'No cuentas con permisos para verificar documentos');
    }
}
