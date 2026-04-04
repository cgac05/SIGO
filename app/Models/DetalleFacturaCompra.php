<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DetalleFacturaCompra extends Model
{
    protected $table = 'detalle_facturas_compra';
    protected $primaryKey = 'id_detalle';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = null;

    protected $fillable = [
        'fk_id_factura',
        'fk_id_inventario',
        'cantidad_comprada',
        'costo_unitario',
        'lote_numero',
        'fecha_vencimiento',
        'observaciones',
    ];

    protected $casts = [
        'cantidad_comprada' => 'float',
        'costo_unitario' => 'float',
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Relación: Detalle pertenece a una factura
     */
    public function factura(): BelongsTo
    {
        return $this->belongsTo(FacturaCompra::class, 'fk_id_factura', 'id_factura');
    }

    /**
     * Relación: Detalle pertenece a un material del inventario
     */
    public function inventario(): BelongsTo
    {
        return $this->belongsTo(InventarioMaterial::class, 'fk_id_inventario', 'id_inventario');
    }

    /**
     * Obtener el costo total del detalle
     */
    public function getCostoTotal(): float
    {
        return $this->cantidad_comprada * $this->costo_unitario;
    }
}
