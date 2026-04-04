<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FacturaCompra extends Model
{
    protected $table = 'facturas_compra';
    protected $primaryKey = 'id_factura';
    public $timestamps = true;
    const CREATED_AT = 'created_at';
    const UPDATED_AT = 'updated_at';

    protected $fillable = [
        'numero_factura',
        'fk_id_proveedor',
        'nombre_proveedor',
        'rfc_proveedor',
        'fecha_compra',
        'fecha_recepcion',
        'monto_total',
        'moneda',
        'estado',
        'observaciones',
        'archivo_factura',
        'registrado_por',
        'actualizado_por',
    ];

    protected $casts = [
        'fecha_compra' => 'datetime',
        'fecha_recepcion' => 'datetime',
        'monto_total' => 'float',
    ];

    /**
     * Relación: Factura fue registrada por un usuario
     */
    public function registradoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'registrado_por', 'id_usuario');
    }

    /**
     * Relación: Factura fue actualizada por un usuario
     */
    public function actualizadoPor(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'actualizado_por', 'id_usuario');
    }

    /**
     * Relación: Una factura tiene muchos detalles (materiales)
     */
    public function detalles(): HasMany
    {
        return $this->hasMany(DetalleFacturaCompra::class, 'fk_id_factura', 'id_factura');
    }

    /**
     * Scope: Obtener facturas recientes
     */
    public function scopeRecientes($query)
    {
        return $query->orderBy('fecha_compra', 'desc');
    }

    /**
     * Scope: Obtener facturas por proveedor
     */
    public function scopePorProveedor($query, string $proveedor)
    {
        return $query->where('nombre_proveedor', 'ilike', "%$proveedor%");
    }

    /**
     * Scope: Obtener facturas pendientes de recepción
     */
    public function scopePendientesRecepcion($query)
    {
        return $query->where('estado', 'Parcial')
            ->orWhere('estado', 'Recibida');
    }
}
