<?php

namespace App\Http\Controllers;

use App\Models\Notificacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificacionController extends Controller
{
    /**
     * Mostrar página de notificaciones (Inbox)
     */
    public function index(Request $request)
    {
        $usuario = Auth::user();

        if (!$usuario || $usuario->rol_id !== 0) {
            return redirect()->route('dashboard')->with('error', 'Acceso no autorizado');
        }

        $notificaciones = Notificacion::where('id_beneficiario', $usuario->id)
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        $noLeidas = Notificacion::where('id_beneficiario', $usuario->id)
            ->where('leida', false)
            ->count();

        return view('beneficiario.notificaciones.inbox', compact('notificaciones', 'noLeidas'));
    }

    /**
     * Marcar una notificación como leída (JSON)
     */
    public function marcarLeida(Request $request, int $id)
    {
        $usuario = Auth::user();
        $notificacion = Notificacion::find($id);

        if (!$notificacion || $notificacion->id_beneficiario !== $usuario->id) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->marcarLeida();

        return response()->json(['success' => true, 'message' => 'Notificación marcada como leída']);
    }

    /**
     * Marcar todas como leídas (JSON)
     */
    public function marcarTodasLeidas(Request $request)
    {
        $usuario = Auth::user();

        Notificacion::where('id_beneficiario', $usuario->id)
            ->where('leida', false)
            ->update(['leida' => true]);

        return response()->json(['success' => true, 'message' => 'Todas las notificaciones marcadas como leídas']);
    }

    /**
     * Eliminar notificación
     */
    public function destroy(Request $request, int $id)
    {
        $usuario = Auth::user();
        $notificacion = Notificacion::find($id);

        if (!$notificacion || $notificacion->id_beneficiario !== $usuario->id) {
            return response()->json(['error' => 'Notificación no encontrada'], 404);
        }

        $notificacion->delete();

        return response()->json(['success' => true, 'message' => 'Notificación eliminada']);
    }

    /**
     * Obtener conteo de no leídas (AJAX)
     */
    public function conteoNoLeidas()
    {
        $usuario = Auth::user();
        
        if (!$usuario) {
            return response()->json(['total' => 0]);
        }

        $total = Notificacion::where('id_beneficiario', $usuario->id)
            ->where('leida', false)
            ->count();

        return response()->json(['total' => $total]);
    }
}
