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
    // No usamos timestamps automáticos; las fechas se guardan manualmente.
    // Formato personalizado para evitar problemas con el driver SQL Server/DB.
    protected $dateFormat = 'Ymd H:i:s';
    protected $fillable = [
        'nombre_apoyo',
        'tipo_apoyo',
        'monto_maximo',
        'activo',
        'fecha_Creacion',
        'fechaInicio',
        'fechafin',
        'foto_ruta',
        'descripcion',
    ];

    /**
     * Nota sobre migraciones/llaves:
     * - La tabla `Apoyos` se crea por la migración `2026_03_08_000001_create_apoyos_and_aux_tables.php`.
     * - Si se requiere relacionar con Eloquent, se pueden añadir relaciones hasOne/hasMany
     *   a `BDFinanzas` o `BDInventario` según el tipo de apoyo.
     */
}

