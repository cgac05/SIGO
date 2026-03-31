<?php

namespace App\Events;

use App\Models\HitosApoyo;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * HitoCambiado Event
 * 
 * Disparado cuando un hito de apoyo es modificado en SIGO.
 * Usado para sincronizar cambios con Google Calendar automáticamente.
 * 
 * Triggers: 
 * - Hito creado
 * - Fecha de hito modificada
 * - Descripción de hito modificada
 * - Hito eliminado
 */
class HitoCambiado
{
    use Dispatchable, SerializesModels;

    public $hito;
    public $tipo_cambio; // 'creacion', 'actualizacion', 'eliminacion'

    /**
     * Create a new event instance.
     *
     * @param HitosApoyo $hito
     * @param string $tipo_cambio
     */
    public function __construct(HitosApoyo $hito, $tipo_cambio = 'actualizacion')
    {
        $this->hito = $hito;
        $this->tipo_cambio = $tipo_cambio;
    }
}
