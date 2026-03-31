<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

/**
 * VerificaSesionPrivada
 * 
 * Middleware para rutas de Momento 3 (Consulta Privada)
 * 
 * Verifica:
 * 1. Existe folio en sesión privada
 * 2. No expiró (< 30 minutos)
 * 3. Folio sigue siendo válido en BD
 */
class VerificaSesionPrivada
{
    public function handle(Request $request, Closure $next)
    {
        // 1. Verificar que existe folio en sesión
        if (!session('caso_a_folio')) {
            return redirect('/consulta-privada')
                ->with('error', 'Sesión privada no iniciada. Ingrese sus datos.');
        }

        // 2. Verificar que sesión privada está marcada
        if (!session('caso_a_privada')) {
            return redirect('/consulta-privada')
                ->with('error', 'Sesión privada expirada. Intente nuevamente.');
        }

        $folio = session('caso_a_folio');

        // 3. Verificar que folio sigue siendo válido en BD
        $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $folio)->first();

        if (!$clave) {
            session()->forget(['caso_a_folio', 'caso_a_privada', 'caso_a_expira']);
            return redirect('/consulta-privada')
                ->with('error', 'Expediente no encontrado');
        }

        // 4. Verificar que no está bloqueada
        if ($clave->bloqueada) {
            session()->forget(['caso_a_folio', 'caso_a_privada', 'caso_a_expira']);
            return redirect('/consulta-privada')
                ->with('error', 'Clave bloqueada. Contacta a INJUVE para desbloquearlo.');
        }

        // 5. Verificar expiración de sesión
        $expiraEn = session('caso_a_expira');
        if ($expiraEn && now() > $expiraEn) {
            session()->forget(['caso_a_folio', 'caso_a_privada', 'caso_a_expira']);
            return redirect('/consulta-privada')
                ->with('error', 'Sesión privada expirada (30 minutos). Ingrese nuevamente sus datos.');
        }

        return $next($request);
    }
}
