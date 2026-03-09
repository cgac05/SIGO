<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo Eloquent para la tabla `Apoyos`.
 *
 * Campos principales (según migración):
 * - id_apoyo (int, PK, autoincrement)
 * - nombre_apoyo (string)
 * - tipo_apoyo (string) => 'Económico' | 'Especie'
 * - monto_maximo (decimal)
 * - activo (boolean)
 *
 * Este modelo permite asignación masiva de los campos indicados en `$fillable`.
 */
class Apoyo extends Model
{
    protected $table = 'Apoyos';
    protected $primaryKey = 'id_apoyo';
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = false;

    protected $fillable = [
        'nombre_apoyo',
        'tipo_apoyo',
        'monto_maximo',
        'activo',
    ];
}

