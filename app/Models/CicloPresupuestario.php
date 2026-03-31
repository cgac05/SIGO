<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CicloPresupuestario extends Model
{
    use HasFactory;

    protected $table = 'ciclos_presupuestarios';
    protected $primaryKey = 'id_ciclo';
    protected $guarded = [];
    protected $dates = ['fecha_apertura', 'fecha_cierre_programado', 'fecha_cierre_efectivo', 'created_at', 'updated_at'];
    protected $casts = [
        'año_fiscal' => 'integer',
        'presupuesto_total' => 'decimal:2',
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
