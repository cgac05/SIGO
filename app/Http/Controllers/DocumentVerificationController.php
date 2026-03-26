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
     */
    public function viewDocument(Request $request, int $id)
    {
        $this->authorizeAdmin($request);

        $documento = Documento::findOrFail($id);

        try {
            $accessUrl = $this->service->getDocumentAccessUrl($documento);

            // Si es local, devolver el archivo
            if ($documento->isLocal()) {
                return redirect($accessUrl);
            }

            // Si es Google Drive, redirigir a preview
            if ($documento->isFromDrive()) {
                return redirect($accessUrl);
            }
        } catch (\Exception $e) {
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
            $result = $this->service->verifyDocument(
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
