<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoInventario extends Model
{
    protected $table = 'movimientos_inventario';
    protected $primaryKey = 'id_movimiento';
    public $timestamps = false;

    protected $fillable = [
        'fk_id_inventario',
        'tipo_movimiento',
        'cantidad',
        'cantidad_anterior',
        'cantidad_nueva',
        'fk_id_factura',
        'fk_id_salida',
        'motivo',
        'realizado_por',
        'fecha_movimiento',
    ];

    protected $casts = [
        'cantidad' => 'float',
        'cantidad_anterior' => 'float',
        'cantidad_nueva' => 'float',
        'fecha_movimiento' => 'datetime',
    ];

    /**
     * Relación: Movimiento pertenece a un material
     */
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(InventarioMaterial::class, 'fk_id_inventario', 'id_inventario');
    }

    /**
     * Relación: Movimiento puede estar vinculado a una factura
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaCompra::class, 'fk_id_factura', 'id_factura');
    }

    /**
     * Relación: Movimiento puede estar vinculado a una salida
     */
    public function salida(): BelongsTo
    {
        return $this->belongsTo(SalidaBeneficiario::class, 'fk_id_salida', 'id_salida');
    }

    /**
     * Relación: Usuario que realizó el movimiento
     */
    public function usuario(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'realizado_por', 'id_usuario');
    }

    /**
     * Scope: Obtener movimientos de entrada
     */
    public function scopeEntradas($query)
    {
        return $query->where('tipo_movimiento', 'Entrada');
    }

    /**
     * Scope: Obtener movimientos de salida
     */
    public function scopeSalidas($query)
    {
        return $query->where('tipo_movimiento', 'Salida');
    }

    /**
     * Scope: Obtener movimientos recientes
     */
    public function scopeRecientes($query)
    {
        return $query->orderBy('fecha_movimiento', 'desc');
    }
}
