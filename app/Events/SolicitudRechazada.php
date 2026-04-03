<?php

namespace App\Events;

use App\Models\Solicitud;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SolicitudRechazada
{
    use Dispatchable, SerializesModels;

    public $solicitud;
    public $motivo;

    public function __construct(Solicitud $solicitud, $motivo)
    {
        $this->solicitud = $solicitud;
        $this->motivo = $motivo;
    }
}
}
