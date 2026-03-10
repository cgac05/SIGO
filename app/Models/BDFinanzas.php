<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `BD_Finanzas`.
 *
 * Uso: registra saldos y movimientos financieros asociados a un `Apoyo`.
 * Campos:
 * - id_finanza (int, PK)
 * - fk_id_apoyo (int) -> llave foránea hacia `Apoyos.id_apoyo`
 * - monto_asignado (decimal) -> monto inicial asignado al apoyo
 * - monto_ejercido (decimal) -> monto ya ejercido
 */
class BDFinanzas extends Model
{
    protected $table = 'BD_Finanzas';
    protected $primaryKey = 'id_finanza';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fk_id_apoyo',
        'monto_asignado',
        'monto_ejercido',
    ];

    /**
     * Nota: las inserciones en `BD_Finanzas` se realizan desde
     * `ApoyoController::store()` cuando `tipo_apoyo === 'Económico'`.
     * Si se desea lógica adicional (ej. métodos para sumar movimientos),
     * implementarla en este modelo.
     */
}
