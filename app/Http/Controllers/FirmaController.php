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
        $usuario = Auth::user();
        
        // Validar que sea directivo o personal con permisos (roles 2, 3)
        $rol = $usuario?->personal?->fk_rol ?? $usuario?->role;
        
        if (!$usuario || !in_array($rol, [2, 3])) {
            if ($request->wantsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No tienes permisos para firmar solicitudes.'
                ], 403);
            }
            abort(403, 'No tienes permisos para firmar solicitudes.');
        }

        // Si es una solicitud AJAX, devolver JSON
        if ($request->wantsJson() || $request->ajax()) {
            return $this->getJsonFirmaData($folio);
        }

        // Si es una solicitud GET normal, renderizar la vista
        return $this->renderFirmaView($folio);
    }

    /**
     * Obtener datos de firma en formato JSON (para AJAX)
     */
    private function getJsonFirmaData(int $folio)
    {
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
            ->orderBy('id_doc')
            ->get()
            ->map(fn($doc) => [
                'id' => $doc->id_doc,
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
                    'nombre_apoyo' => $apoyo->nombre_apoyo,
                    'tipo' => $apoyo->tipo_apoyo,
                    'monto' => $apoyo->monto_maximo
                ] : null,
                'documentos' => $documentos
            ]
        ]);
    }

    /**
     * Renderizar la vista de firma con todos los datos
     */
    private function renderFirmaView(int $folio)
    {
        // Obtener solicitud
        $solicitud = \DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitud) {
            abort(404, 'Solicitud no encontrada');
        }

        // Obtener beneficiario
        $beneficiario = \DB::table('Beneficiarios')
            ->where('curp', $solicitud->fk_curp)
            ->first() ?? (object)[];

        // Obtener apoyo
        $apoyo = \DB::table('Apoyos')
            ->where('id_apoyo', $solicitud->fk_id_apoyo)
            ->first() ?? (object)[];

        // Obtener documentos
        $documentos = \DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->orderBy('id_doc')
            ->get();

        // Obtener hito actual
        $hito_actual = \DB::table('Hitos_Apoyo')
            ->where('id_hito', $solicitud->fk_id_hito_actual)
            ->first();

        // Calcular monto de la solicitud
        $monto_solicitud = $apoyo->monto_maximo ?? 0;

        return view('solicitudes.firma', [
            'folio' => $folio,
            'solicitud' => $solicitud,
            'beneficiario' => $beneficiario,
            'apoyo' => $apoyo,
            'documentos' => $documentos,
            'monto_solicitud' => $monto_solicitud,
            'hito_actual' => $hito_actual
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
        $usuario = Auth::user();
        $rol = $usuario?->personal?->fk_rol ?? $usuario?->role;

        // Validar que sea directivo o personal con permisos
        if (!in_array($rol, [2, 3])) {
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
        $usuario = Auth::user();
        $rol = $usuario?->personal?->fk_rol ?? $usuario?->role;

        // Validar que sea directivo o personal con permisos
        if (!in_array($rol, [2, 3])) {
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

    /**
     * Completar Fase 2: Resumen Crítico
     * POST /solicitudes/{folio}/completar-fase-2
     *
     * @param Request $request
     * @param int $folio
     * @return \Illuminate\Http\JsonResponse
     */
    public function completarFase2(Request $request, int $folio)
    {
        $usuario = Auth::user();
        $rol = $usuario?->personal?->fk_rol ?? $usuario?->role;

        // Validar que sea directivo o personal con permisos
        if (!in_array($rol, [2, 3])) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para completar esta fase.'
            ], 403);
        }

        try {
            // Actualizar hito a RESULTADOS (fase 3)
            $solicitud = \DB::table('Solicitudes')
                ->where('folio', $folio)
                ->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'message' => 'Solicitud no encontrada.'
                ], 404);
            }

            // Obtener el next hito (RESULTADOS)
            $hitoActual = \DB::table('Hitos_Apoyo')
                ->where('id_hito', $solicitud->fk_id_hito_actual)
                ->first();

            $proximoHito = \DB::table('Hitos_Apoyo')
                ->where('fk_id_apoyo', $solicitud->fk_id_apoyo)
                ->where('orden_hito', $hitoActual->orden_hito + 1)
                ->first();

            if ($proximoHito) {
                \DB::table('Solicitudes')
                    ->where('folio', $folio)
                    ->update([
                        'fk_id_hito_actual' => $proximoHito->id_hito,
                        'fecha_actualizacion' => now()
                    ]);
            }

            Log::channel('seguridad')->info('Fase 2 completada (Resumen Crítico)', [
                'folio' => $folio,
                'usuario_id' => $usuario->id_usuario,
                'usuario' => $usuario->nombre,
                'hito_anterior' => $hitoActual->clave_hito ?? 'DESCONOCIDO',
                'hito_nuevo' => $proximoHito->clave_hito ?? 'DESCONOCIDO'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Fase 2 completada exitosamente. Redirigiendo...',
                'hito_nuevo' => $proximoHito->clave_hito ?? null
            ]);
        } catch (\Throwable $e) {
            Log::error('Error al completar Fase 2', [
                'folio' => $folio,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al completar la fase: ' . $e->getMessage()
            ], 500);
        }
    }
}
