<?php

namespace App\Http\Controllers;

use App\Services\FirmaElectronicaService;
use App\Http\Controllers\ReauthenticationController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * FirmaController
 * 
 * Controlador para manejar firmas electrónicas de solicitudes
 * Responsabilidades:
 * - Mostrar formulario de firma (aprobación/rechazo)
 * - Procesar firma de solicitud (aprobación)
 * - Procesar rechazo de solicitud
 * - Verificar re-autenticación antes de firmar
 * 
 * Compliance:
 * - LGPDP: Auditoría completa de todas las firmas
 * - LFTAIPG: Transparencia en el proceso
 */
class FirmaController extends Controller
{
    public function __construct(
        private readonly FirmaElectronicaService $firmaService
    ) {
    }

    /**
     * Mostrar formulario de firma para una solicitud
     * GET /solicitudes/{folio}/firma
     *
     * @param Request $request
     * @param int $folio
     * @return \Illuminate\View\View|\Illuminate\Http\JsonResponse
     */
    public function show(Request $request, int $folio)
    {
        $this->authorize('viewAny', \App\Models\Solicitud::class);
        
        $usuario = Auth::user();
        
        // Validar que sea directivo o personal con permisos
        if (!in_array($usuario->role, [2, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para firmar solicitudes.'
            ], 403);
        }

        // Obtener datos de la solicitud
        $solicitud = \DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitud) {
            return response()->json([
                'success' => false,
                'message' => 'Solicitud no encontrada.'
            ], 404);
        }

        // Obtener documentos asociados
        $documentos = \DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->orderBy('id_documento')
            ->get()
            ->map(fn($doc) => [
                'id' => $doc->id_documento,
                'tipo' => $doc->fk_id_tipo_doc,
                'estado' => $doc->estado_validacion,
                'fecha' => $doc->fecha_revision
            ])
            ->toArray();

        // Obtener beneficiario
        $beneficiario = \DB::table('Beneficiarios')
            ->where('curp', $solicitud->fk_curp)
            ->first();

        // Obtener info del apoyo
        $apoyo = \DB::table('Apoyos')
            ->where('id_apoyo', $solicitud->fk_id_apoyo)
            ->first();

        return response()->json([
            'success' => true,
            'solicitud' => [
                'folio' => $folio,
                'estado' => $solicitud->fk_id_estado,
                'fecha_creacion' => $solicitud->fecha_creacion,
                'beneficiario' => $beneficiario ? [
                    'nombre' => $beneficiario->nombre,
                    'apellidos' => $beneficiario->apellido_paterno . ' ' . $beneficiario->apellido_materno,
                    'curp' => $beneficiario->curp
                ] : null,
                'apoyo' => $apoyo ? [
                    'nombre' => $apoyo->nombre_apoyo,
                    'tipo' => $apoyo->tipo_apoyo,
                    'monto' => $apoyo->monto_maximo
                ] : null,
                'documentos' => $documentos
            ]
        ]);
    }

    /**
     * Firmar (Aprobar) solicitud
     * POST /solicitudes/{folio}/firmar
     *
     * @param Request $request
     * @param int $folio
     * @return \Illuminate\Http\JsonResponse
     */
    public function firmar(Request $request, int $folio)
    {
        $this->authorize('viewAny', \App\Models\Solicitud::class);

        $usuario = Auth::user();

        // Validar que sea directivo o personal con permisos
        if (!in_array($usuario->role, [2, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para firmar solicitudes.'
            ], 403);
        }

        // Validar re-autenticación
        $reauth_token = $request->input('reauth_token');
        if (!$reauth_token || !ReauthenticationController::verificarTokenReauth($reauth_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Re-autenticación requerida o expirada. Por favor, re-autentícate.'
            ], 401);
        }

        try {
            // Firmar solicitud usando el servicio
            $resultado = $this->firmaService->firmarSolicitud(
                $folio,
                $usuario,
                $request->input('password') ?? ''  // La contraseña ya fue validada en reauth
            );

            if (!$resultado['exitoso']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['mensaje']
                ], 422);
            }

            Log::channel('seguridad')->info('Solicitud firmada (aprobada)', [
                'folio' => $folio,
                'directivo_id' => $usuario->id_usuario,
                'usuarios' => $usuario->nombre,
                'cuv' => $resultado['firma']['cuv'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => $resultado['mensaje'],
                'firma' => $resultado['firma']
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al firmar solicitud', [
                'folio' => $folio,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al firmar solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Rechazar solicitud (Firma negativa)
     * POST /solicitudes/{folio}/rechazar
     *
     * @param Request $request
     * @param int $folio
     * @return \Illuminate\Http\JsonResponse
     */
    public function rechazar(Request $request, int $folio)
    {
        $this->authorize('viewAny', \App\Models\Solicitud::class);

        $usuario = Auth::user();

        // Validar que sea directivo o personal con permisos
        if (!in_array($usuario->role, [2, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para rechazar solicitudes.'
            ], 403);
        }

        // Validar re-autenticación
        $reauth_token = $request->input('reauth_token');
        if (!$reauth_token || !ReauthenticationController::verificarTokenReauth($reauth_token)) {
            return response()->json([
                'success' => false,
                'message' => 'Re-autenticación requerida o expirada. Por favor, re-autentícate.'
            ], 401);
        }

        // Validar motivo de rechazo
        $request->validate([
            'motivo' => 'required|string|max:500',
            'permite_correcciones' => 'nullable|boolean'
        ]);

        try {
            // Rechazar solicitud usando el servicio
            $resultado = $this->firmaService->rechazarSolicitud(
                $folio,
                $usuario,
                $request->input('password') ?? '',  // La contraseña ya fue validada en reauth
                $request->input('motivo')
            );

            if (!$resultado['exitoso']) {
                return response()->json([
                    'success' => false,
                    'message' => $resultado['mensaje']
                ], 422);
            }

            Log::channel('seguridad')->info('Solicitud rechazada', [
                'folio' => $folio,
                'directivo_id' => $usuario->id_usuario,
                'directivo' => $usuario->nombre,
                'motivo' => $request->input('motivo'),
                'cuv_rechazo' => $resultado['cuv'] ?? null
            ]);

            return response()->json([
                'success' => true,
                'message' => $resultado['mensaje'],
                'cuv' => $resultado['cuv'] ?? null
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al rechazar solicitud', [
                'folio' => $folio,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al rechazar solicitud: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener historial de firmas de una solicitud
     * GET /solicitudes/{folio}/firmas
     *
     * @param Request $request
     * @param int $folio
     * @return \Illuminate\Http\JsonResponse
     */
    public function historialFirmas(Request $request, int $folio)
    {
        $this->authorize('viewAny', \App\Models\Solicitud::class);

        try {
            $firmas = \DB::table('firmas_electronicas')
                ->where('folio_solicitud', $folio)
                ->orderByDesc('fecha_firma')
                ->get()
                ->map(fn($firma) => [
                    'id' => $firma->id,
                    'tipo' => $firma->tipo_firma,
                    'cuv' => $firma->cuv,
                    'sello_digital' => substr($firma->sello_digital, 0, 16) . '...',
                    'fecha' => $firma->fecha_firma,
                    'estado' => $firma->estado,
                    'directivo_id' => $firma->id_directivo,
                    'directivo' => \DB::table('Usuarios')
                        ->where('id_usuario', $firma->id_directivo)
                        ->value('nombre')
                ])
                ->toArray();

            return response()->json([
                'success' => true,
                'firmas' => $firmas,
                'total' => count($firmas)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial: ' . $e->getMessage()
            ], 500);
        }
    }
}
