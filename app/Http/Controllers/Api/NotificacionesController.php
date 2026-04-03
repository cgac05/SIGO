<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Notificacion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Obtener todas las notificaciones del usuario actual
     */
    public function index(): JsonResponse
    {
        $usuario = Auth::user();

        if ($usuario->rol_id !== 0) {
            return response()->json(['error' => 'Acceso no autorizado'], 403);
        }

        $notificaciones = Notificacion::where('id_beneficiario', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($notificaciones);
    }

    /**
     * Obtener solo notificaciones no leídas
     */
    public function noLeidas(): JsonResponse
    {
        $usuario = Auth::user();

        if ($usuario->rol_id !== 0) {
            return response()->json(['error' => 'Acceso no autorizado'], 403);
        }

        $notificaciones = Notificacion::where('id_beneficiario', $usuario->id)
            ->where('leida', false)
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'total' => $notificaciones->count(),
            'notificaciones' => $notificaciones,
        ]);
    }

    /**
     * Marcar una notificación como leída
     */
    public function marcarLeida(string $id): JsonResponse
    {
        $usuario = Auth::user();
        $notificacion = Notificacion::find($id);

        if (!$notificacion || $notificacion->id_beneficiario !== $usuario->id) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->marcarLeida();

        return response()->json([
            'message' => 'Notificación marcada como leída',
            'notificacion' => $notificacion,
        ]);
    }

    /**
     * Eliminar una notificación
     */
    public function destroy(string $id): JsonResponse
    {
        $usuario = Auth::user();
        $notificacion = Notificacion::find($id);

        if (!$notificacion || $notificacion->id_beneficiario !== $usuario->id) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->delete();

        return response()->json(['message' => 'Notificación eliminada']);
    }

    /**
     * Marcar todas las notificaciones como leídas
     */
    public function marcarTodasLeidas(): JsonResponse
    {
        $usuario = Auth::user();

        Notificacion::where('id_beneficiario', $usuario->id)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['message' => 'Todas las notificaciones marcadas como leídas']);
    }

    /**
     * Obtener conteo de notificaciones no leídas
     */
    public function conteoNoLeidas(): JsonResponse
    {
        $usuario = Auth::user();

        $total = Notificacion::where('id_beneficiario', $usuario->id)
            ->where('leida', false)
            ->count();

        return response()->json(['total_no_leidas' => $total]);
    }
}
