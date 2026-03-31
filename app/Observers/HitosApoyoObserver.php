<?php

namespace App\Observers;

use App\Events\HitoCambiado;
use App\Models\HitosApoyo;

class HitosApoyoObserver
{
    /**
     * Handle the HitosApoyo "created" event.
     */
    public function created(HitosApoyo $hito): void
    {
        HitoCambiado::dispatch($hito, 'creacion');
    }

    /**
     * Handle the HitosApoyo "updated" event.
     */
    public function updated(HitosApoyo $hito): void
    {
        HitoCambiado::dispatch($hito, 'actualizacion');
    }

    /**
     * Handle the HitosApoyo "deleted" event.
     */
    public function deleted(HitosApoyo $hito): void
    {
        HitoCambiado::dispatch($hito, 'eliminacion');
    }

    /**
     * Handle the HitosApoyo "restored" event.
     */
    public function restored(HitosApoyo $hito): void
    {
        //
    }

    /**
     * Handle the HitosApoyo "force deleted" event.
     */
    public function forceDeleted(HitosApoyo $hito): void
    {
        //
    }
}
