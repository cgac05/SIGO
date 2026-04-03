<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloPresupuestario extends Model
{
    protected $table = 'ciclos_presupuestarios';
    protected $primaryKey = 'id';
    protected $guarded = [];
    protected $casts = [
        'año_fiscal' => 'integer',
        'presupuesto_total' => 'decimal:2',
        'fecha_apertura' => 'datetime',
        'fecha_cierre_programado' => 'datetime',
        'fecha_cierre_efectivo' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

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
        return $query->where('año_fiscal', $año);
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
            'fecha_cierre_efectivo' => now(),
        ]);
    }

    public function reabrir(): bool
    {
        return $this->update([
            'estado' => 'ABIERTO',
            'fecha_cierre_efectivo' => null,
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
