<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InventarioMaterial extends Model
{
    protected $table = 'inventario_material';
    protected $primaryKey = 'id_inventario';
    public $timestamps = false;

    protected $fillable = [
        'codigo_material',
        'nombre_material',
        'descripcion',
        'fk_id_apoyo',
        'unidad_medida',
        'cantidad_actual',
        'cantidad_minima',
        'costo_unitario',
        'proveedor_principal',
        'activo',
    ];

    protected $casts = [
        'cantidad_actual' => 'float',
        'cantidad_minima' => 'float',
        'costo_unitario' => 'float',
        'activo' => 'boolean',
    ];

    /**
     * Relación: Material pertenece a un apoyo (tipo Especie)
     */
    public function apoyo(): BelongsTo
    {
        return $this->belongsTo(Apoyo::class, 'fk_id_apoyo', 'id_apoyo');
    }

    /**
     * Relación: Material aparece en muchas facturas de compra
     */
    public function facturasCompra(): HasMany
    {
        return $this->hasMany(DetalleFacturaCompra::class, 'fk_id_inventario', 'id_inventario');
    }

    /**
     * Relación: Material tiene muchos movimientos de inventario
     */
    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoInventario::class, 'fk_id_inventario', 'id_inventario');
    }

    /**
     * Scope: Obtener materiales de un apoyo
     */
    public function scopeDelApoyo($query, int $idApoyo)
    {
        return $query->where('fk_id_apoyo', $idApoyo);
    }

    /**
     * Scope: Obtener materiales activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }

    /**
     * Scope: Obtener materiales con stock bajo
     */
    public function scopeStockBajo($query)
    {
        return $query->whereRaw('cantidad_actual <= cantidad_minima');
    }

    /**
     * Verificar disponibilidad de stock
     */
    public function tieneStock(float $cantidad): bool
    {
        return $this->cantidad_actual >= $cantidad;
    }

    /**
     * Obtener cantidad disponible
     */
    public function cantidadDisponible(): float
    {
        return max(0, $this->cantidad_actual);
    }

    /**
     * Verificar si necesita reorden
     */
    public function necesitaReorden(): bool
    {
        return $this->cantidad_actual <= $this->cantidad_minima;
    }
}
