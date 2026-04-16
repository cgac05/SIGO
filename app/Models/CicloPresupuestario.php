<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloPresupuestario extends Model
{
    protected $table = 'ciclos_presupuestarios';
    protected $primaryKey = 'id';
    public $incrementing = true;
    protected $guarded = [];
    protected $casts = [
        'ano_fiscal' => 'integer',
        'presupuesto_total_inicial' => 'decimal:2',
        'presupuesto_total_aprobado' => 'decimal:2',
        'fecha_inicio' => 'datetime',
        'fecha_cierre' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== ACCESSORS ==========
    
    /**
     * Accessor para presupuesto_total (alias para presupuesto_total_aprobado)
     */
    public function getPresupuestoTotalAttribute()
    {
        return $this->presupuesto_total_aprobado;
    }

    // ========== RELATIONSHIPS ==========
    
    public function categorias()
    {
        return $this->hasMany(PresupuestoCategoria::class, 'id_ciclo', 'id_ciclo');
    }

    // ========== SCOPES ==========
    
    public function scopeAbierto($query)
    {
        return $query->where('estado', 'ABIERTO');
    }

    public function scopeCerrado($query)
    {
        return $query->where('estado', 'CERRADO');
    }

    public function scopeDelAño($query, $año)
    {
        return $query->where('ano_fiscal', $año);
    }

    // ========== METHODS ==========
    
    public function isAbierto(): bool
    {
        return $this->estado === 'ABIERTO';
    }

    public function isCerrado(): bool
    {
        return $this->estado === 'CERRADO';
    }

    public function cerrar(): bool
    {
        return $this->update([
            'estado' => 'CERRADO',
            'fecha_cierre' => now(),
        ]);
    }

    public function reabrir(): bool
    {
        return $this->update([
            'estado' => 'ABIERTO',
            'fecha_cierre' => null,
        ]);
    }

    public function getBadgeColor(): string
    {
        return $this->estado === 'ABIERTO' ? 'green' : 'red';
    }

    public function getIcon(): string
    {
        return $this->estado === 'ABIERTO' ? 'check-circle' : 'x-circle';
    }
}
