<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresupuestoCategoria extends Model
{
    protected $table = 'presupuesto_categorias';
    protected $primaryKey = 'id_categoria';
    protected $guarded = [];
    protected $casts = [
        'presupuesto_anual' => 'decimal:2',
        'disponible' => 'decimal:2',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========
    
    public function ciclo(): BelongsTo
    {
        return $this->belongsTo(CicloPresupuestario::class, 'id_ciclo', 'id');
    }

    public function apoyos(): HasMany
    {
        return $this->hasMany(PresupuestoApoyo::class, 'id_categoria', 'id_categoria');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoPresupuestario::class, 'id_categoria', 'id_categoria');
    }

    // ========== SCOPES ==========
    
    public function scopeActivas($query)
    {
        return $query->where('activo', true);
    }

    public function scopeDelCiclo($query, $id_ciclo)
    {
        return $query->where('id_ciclo', $id_ciclo);
    }

    // ========== METHODS ==========
    
    public function isDisponibleFor($monto): bool
    {
        return (float) $this->disponible >= (float) $monto;
    }

    public function tieneDisponible($monto): bool
    {
        return $this->isDisponibleFor($monto);
    }

    public function getDisponibleFormato(): string
    {
        return '$' . number_format($this->disponible, 2);
    }

    public function getPresupuestoFormato(): string
    {
        return '$' . number_format($this->presupuesto_anual, 2);
    }

    public function getGastadoFormato(): string
    {
        $gastado = (float) $this->presupuesto_anual - (float) $this->disponible;
        return '$' . number_format($gastado, 2);
    }

    public function getPorcentajeUtilizacion(): float
    {
        if ((float) $this->presupuesto_anual === 0) {
            return 0;
        }
        $gastado = (float) $this->presupuesto_anual - (float) $this->disponible;
        return round(($gastado / (float) $this->presupuesto_anual) * 100, 2);
    }

    public function getPorcentajeUtilizacionFormato(): string
    {
        return number_format($this->getPorcentajeUtilizacion(), 2) . '%';
    }

    /**
     * Decrement available budget when apoyo is created (RESERVANDO)
     */
    public function decrementarDisponible($monto): bool
    {
        return $this->decrement('disponible', $monto);
    }

    /**
     * Increment available budget when apoyo is cancelled
     */
    public function incrementarDisponible($monto): bool
    {
        return $this->increment('disponible', $monto);
    }

    public function getBadgeColor(): string
    {
        $utilizado = $this->getPorcentajeUtilizacion();
        if ($utilizado >= 100) {
            return 'red';
        }
        if ($utilizado >= 85) {
            return 'orange';
        }
        if ($utilizado >= 70) {
            return 'yellow';
        }
        return 'green';
    }
}
