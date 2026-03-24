<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NotificacionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();

        if (! $user || ! Schema::hasTable('Notificaciones')) {
            return response()->json([
                'items' => [],
                'unread_count' => 0,
            ]);
        }

        $items = DB::table('Notificaciones')
            ->where('fk_id_usuario', $user->id_usuario)
            ->orderByDesc('id_notificacion')
            ->limit(12)
            ->get([
                'id_notificacion',
                'mensaje',
                'evento',
                'leido',
                'fecha_creacion',
            ]);

        $unread = DB::table('Notificaciones')
            ->where('fk_id_usuario', $user->id_usuario)
            ->where('leido', 0)
            ->count();

        return response()->json([
            'items' => $items,
            'unread_count' => $unread,
        ]);
    }

    public function unreadCount(Request $request)
    {
        $user = $request->user();

        if (! $user || ! Schema::hasTable('Notificaciones')) {
            return response()->json(['unread_count' => 0]);
        }

        $unread = DB::table('Notificaciones')
            ->where('fk_id_usuario', $user->id_usuario)
            ->where('leido', 0)
            ->count();

        return response()->json(['unread_count' => $unread]);
    }

    public function marcarLeida(Request $request, int $id)
    {
        $user = $request->user();

        if (! $user || ! Schema::hasTable('Notificaciones')) {
            return response()->json(['ok' => true]);
        }

        DB::table('Notificaciones')
            ->where('id_notificacion', $id)
            ->where('fk_id_usuario', $user->id_usuario)
            ->update([
                'leido' => 1,
                'fecha_lectura' => now(),
            ]);

        return response()->json(['ok' => true]);
    }

    public function marcarTodasLeidas(Request $request)
    {
        $user = $request->user();

        if (! $user || ! Schema::hasTable('Notificaciones')) {
            return response()->json(['ok' => true]);
        }

        DB::table('Notificaciones')
            ->where('fk_id_usuario', $user->id_usuario)
            ->where('leido', 0)
            ->update([
                'leido' => 1,
                'fecha_lectura' => now(),
            ]);

        return response()->json(['ok' => true]);
    }
}
