<?php

namespace App\Providers;

use App\Events\SolicitudRechazada;
use App\Listeners\EnviarNotificacionRechazo;
use App\Models\HitosApoyo;
use App\Observers\HitosApoyoObserver;
use Illuminate\Support\Facades\Event;
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
        // Registrar listeners para eventos
        Event::listen(
            SolicitudRechazada::class,
            EnviarNotificacionRechazo::class
        );

        // Registrar observers para modelos
        HitosApoyo::observe(HitosApoyoObserver::class);
    }
}

