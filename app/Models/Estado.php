<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Estado extends Model
{
    protected $table = 'Cat_EstadosSolicitud';
    protected $primaryKey = 'id_estado';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'id_estado',
        'nombre_estado',
    ];

    /**
     * Relación con solicitudes
     */
    public function solicitudes(): HasMany
    {
        return $this->hasMany(Solicitud::class, 'fk_id_estado', 'id_estado');
    }

    /**
     * Obtiene el badge color basado en el estado
     */
    public function getBadgeColor(): string
    {
        return match((int)$this->id_estado) {
            1 => 'gray',      // Pendiente
            2 => 'blue',      // Validado
            3 => 'yellow',    // En Subsanación
            4 => 'green',     // Aprobado
            5 => 'red',       // Rechazado
            8 => 'purple',    // Expediente Presencial
            9 => 'indigo',    // Docs Verificados
            default => 'gray',
        };
    }

    /**
     * Obtiene el ícono basado en el estado
     */
    public function getIcon(): string
    {
        return match((int)$this->id_estado) {
            1 => 'history',           // Pendiente
            2 => 'check-circle',      // Validado
            3 => 'exclamation-circle',// En Subsanación
            4 => 'check-double',      // Aprobado
            5 => 'times-circle',      // Rechazado
            8 => 'folder',            // Expediente Presencial
            9 => 'clipboard-check',   // Docs Verificados
            default => 'info-circle',
        };
    }
}
