<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Notificacion extends Model
{
    use HasFactory;

    protected $table = 'notificaciones';

    protected $fillable = [
        'id_beneficiario',
        'tipo',
        'titulo',
        'mensaje',
        'datos',
        'accion_url',
        'leida',
    ];

    protected $casts = [
        'datos' => 'json',
        'leida' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación con Usuario (Beneficiario)
     */
    public function beneficiario()
    {
        return $this->belongsTo(Usuario::class, 'id_beneficiario');
    }

    /**
     * Scope: Notificaciones no leídas
     */
    public function scopeNoLeidas($query)
    {
        return $query->where('leida', false);
    }

    /**
     * Scope: Por tipo
     */
    public function scopeDelTipo($query, $tipo)
    {
        return $query->where('tipo', $tipo);
    }

    /**
     * Scope: Ordenadas por recientes
     */
    public function scopeRecientes($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    /**
     * Marcar como leída
     */
    public function marcarLeida()
    {
        $this->update(['leida' => true]);
        return $this;
    }

    /**
     * Obtener icono según tipo
     */
    public function getIconoAttribute()
    {
        return match($this->tipo) {
            'documento_rechazado' => '❌',
            'hito_cambio' => '📍',
            'solicitud_rechazada' => '🚫',
            default => '📢'
        };
    }

    /**
     * Obtener color badge según tipo
     */
    public function getColorAttribute()
    {
        return match($this->tipo) {
            'documento_rechazado' => 'red',
            'hito_cambio' => 'blue',
            'solicitud_rechazada' => 'red',
            default => 'gray'
        };
    }

    /**
     * Obtener nombre legible del tipo
     */
    public function getNombreTipoAttribute()
    {
        return match($this->tipo) {
            'documento_rechazado' => 'Documento Rechazado',
            'hito_cambio' => 'Cambio de Etapa',
            'solicitud_rechazada' => 'Solicitud Rechazada',
            default => 'Notificación'
        };
    }
}
