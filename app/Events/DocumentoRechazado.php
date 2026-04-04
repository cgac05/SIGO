<?php

namespace App\Events;

use App\Models\Usuario;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DocumentoRechazado
{
    use Dispatchable, SerializesModels;

    public $beneficiario;
    public $nombreDocumento;
    public $motivo;
    public $idSolicitud;

    public function __construct(Usuario $beneficiario, $nombreDocumento, $motivo, $idSolicitud = null)
    {
        $this->beneficiario = $beneficiario;
        $this->nombreDocumento = $nombreDocumento;
        $this->motivo = $motivo;
        $this->idSolicitud = $idSolicitud;
    }
}
