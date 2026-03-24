<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `BD_Inventario`.
 *
 * Uso: almacena el stock disponible asociado a un `Apoyo` de tipo 'Especie'.
 * Campos:
 * - id_inventario (int, PK)
 * - fk_id_apoyo (int) -> llave foránea hacia `Apoyos.id_apoyo`
 * - stock_actual (int) -> cantidad disponible
 */
class BDInventario extends Model
{
    protected $table = 'BD_Inventario';
    protected $primaryKey = 'id_inventario';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'fk_id_apoyo',
        'stock_actual',
    ];

    /**
     * Nota: las inserciones en `BD_Inventario` se realizan desde
     * `ApoyoController::store()` cuando `tipo_apoyo === 'Especie'`.
     * Se puede agregar un método `adjustStock($delta)` si se necesita
     * actualizar stock desde otras partes de la aplicación.
     */
}
