<?php

use App\Http\Middleware\EnsureBeneficiarioProfileIsComplete;
use App\Http\Middleware\CheckRole;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        channels: __DIR__.'/../routes/channels.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'beneficiario.profile' => EnsureBeneficiarioProfileIsComplete::class,
            'role' => CheckRole::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException $e, $request) {
            if ($request->wantsJson()) {
                return response()->json([
                    'message' => 'Operación no permitida',
                    'error' => 'El método usado no es válido para esta acción'
                ], 405);
            }

            return response()->view('errors.405', [
                'message' => 'No se puede completar esta acción'
            ], 405);
        });
    })->create();
