<?php

namespace App\Providers;

use App\Models\HitosApoyo;
use App\Models\Documento;
use App\Observers\HitosApoyoObserver;
use App\Observers\DocumentoObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Forzar HTTP para contrarrestar cualquier APP_URL que esté forzando HTTPS en AWS
        if (config('app.env') === 'production') {
            \Illuminate\Support\Facades\URL::forceScheme('http');
        }

        // Registrar observers para modelos
        HitosApoyo::observe(HitosApoyoObserver::class);
        Documento::observe(DocumentoObserver::class);
    }
}

