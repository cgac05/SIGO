<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBeneficiarioProfileIsComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if (! $user->isBeneficiario()) {
            return redirect()->route('dashboard')->with('error', 'Solo los beneficiarios pueden acceder a este módulo.');
        }

        if (! $user->hasCompleteBeneficiarioProfile()) {
            return redirect()->route('registro.completar-perfil.show');
        }

        return $next($request);
    }
}