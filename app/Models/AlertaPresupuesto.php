<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaPresupuesto extends Model
{
    protected $table = 'alertas_presupuesto';
    public $timestamps = false;

    protected $fillable = [
        'fk_id_categoria',
        'nivel_alerta',
        'mensaje',
        'fecha_alerta',
        'vista',
        'fecha_vista',
    ];

    protected $casts = [
        'fecha_alerta' => 'datetime',
        'fecha_vista' => 'datetime',
    ];

    // Relationships
    public function categoria()
    {
        return $this->belongsTo(PresupuestoCategoria::class, 'fk_id_categoria', 'id_presupuesto');
    }

    // Scopes
    public function scopeNoVistas($query)
    {
        return $query->where('vista', false);
    }

    public function scopePorNivel($query, $nivel)
    {
        return $query->where('nivel_alerta', $nivel);
    }

    // Métodos
    public function marcarVista()
    {
        $this->update([
            'vista' => true,
            'fecha_vista' => now(),
        ]);
    }

    public function getColorAttribute()
    {
        return match ($this->nivel_alerta) {
            'NORMAL' => 'green',
            'AMARILLA' => 'yellow',
            'ROJA' => 'red',
            'CRITICA' => 'darkred',
            default => 'gray',
        };
    }

    public function getIconoAttribute()
    {
        return match ($this->nivel_alerta) {
            'NORMAL' => '✅',
            'AMARILLA' => '⚠️',
            'ROJA' => '🔴',
            'CRITICA' => '⛔',
            default => 'ℹ️',
        };
    }
}
