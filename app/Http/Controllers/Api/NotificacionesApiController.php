<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificacionesApiController extends Controller
{
    /**
     * GET /api/notificaciones
     * Obtener todas las notificaciones del usuario autenticado (paginadas)
     */
    public function index(Request $request): JsonResponse
    {
        $beneficiario = auth()->user();
        
        if (!$beneficiario) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $notificaciones = Notificacion::where('id_beneficiario', $beneficiario->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $notificaciones->items(),
            'pagination' => [
                'total' => $notificaciones->total(),
                'per_page' => $notificaciones->perPage(),
                'current_page' => $notificaciones->currentPage(),
                'last_page' => $notificaciones->lastPage(),
            ],
        ]);
    }

    /**
     * GET /api/notificaciones/no-leidas
     * Endpoint especial para obtener el conteo de notificaciones no leídas
     */
    public function noLeidas(Request $request): JsonResponse
    {
        $beneficiario = auth()->user();
        
        if (!$beneficiario) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $count = Notificacion::where('id_beneficiario', $beneficiario->id)
            ->where('leida', false)
            ->count();

        $notificaciones = Notificacion::where('id_beneficiario', $beneficiario->id)
            ->where('leida', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'count' => $count,
            'recientes' => $notificaciones,
        ]);
    }

    /**
     * POST /api/notificaciones/{id}/marcar-leida
     * Marcar una notificación como leída
     */
    public function marcarLeida(Request $request, $id): JsonResponse
    {
        $beneficiario = auth()->user();
        
        if (!$beneficiario) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        // Verificar que pertenece al usuario
        if ($notificacion->id_beneficiario !== $beneficiario->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $notificacion->marcarLeida();

        return response()->json([
            'success' => true,
            'message' => 'Notificación marcada como leída',
            'data' => $notificacion,
        ]);
    }

    /**
     * DELETE /api/notificaciones/{id}
     * Eliminar una notificación
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $beneficiario = auth()->user();
        
        if (!$beneficiario) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        $notificacion = Notificacion::find($id);

        if (!$notificacion) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        // Verificar que pertenece al usuario
        if ($notificacion->id_beneficiario !== $beneficiario->id) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        $notificacion->delete();

        return response()->json([
            'success' => true,
            'message' => 'Notificación eliminada',
        ]);
    }

    /**
     * POST /api/notificaciones/marcar-todas-leidas
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas(Request $request): JsonResponse
    {
        $beneficiario = auth()->user();
        
        if (!$beneficiario) {
            return response()->json(['error' => 'No autenticado'], 401);
        }

        Notificacion::where('id_beneficiario', $beneficiario->id)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json([
            'success' => true,
            'message' => 'Todas las notificaciones marcadas como leídas',
        ]);
    }
}
