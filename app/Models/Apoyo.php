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
<<<<<<< HEAD
    
    // No usamos timestamps automáticos; las fechas se guardan manualmente.
    // Formato personalizado para evitar problemas con el driver SQL Server/DB.
    protected $dateFormat = 'Ymd H:i:s';

    // Campos permitidos para asignación masiva. Deben ser un array indexado
    // de nombres de columna (no asociativo). Asegura que `Model::create()` incluya
    // `fecha_Creacion`, `fechaInicio` y `fechafin` en el INSERT.
=======

>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
    protected $fillable = [
        'nombre_apoyo',
        'anio_fiscal',
        'tipo_apoyo',
        'monto_maximo',
        'cupo_limite',
        'activo',
<<<<<<< HEAD
        'fecha_Creacion',
        'fechaInicio',
        'fechafin',
=======
        'fecha_inicio',
        'fecha_fin',
>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
        'foto_ruta',
        'descripcion',
    ];

<<<<<<< HEAD
    /**
     * Nota sobre migraciones/llaves:
     * - La tabla `Apoyos` se crea por la migración `2026_03_08_000001_create_apoyos_and_aux_tables.php`.
     * - Si se requiere relacionar con Eloquent, se pueden añadir relaciones hasOne/hasMany
     *   a `BDFinanzas` o `BDInventario` según el tipo de apoyo.
     */
=======
    protected $casts = [
        'activo' => 'boolean',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];
>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
}

