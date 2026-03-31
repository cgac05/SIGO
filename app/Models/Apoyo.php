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
        'anio_fiscal',
        'tipo_apoyo',
        'monto_maximo',
        'cupo_limite',
        'activo',
        'fecha_inicio',
        'fecha_fin',
        'foto_ruta',
        'descripcion',
        'sincronizar_calendario',
        'recordatorio_dias',
        'google_group_email',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'sincronizar_calendario' => 'boolean',
        'recordatorio_dias' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    /**
     * Relación: Un apoyo tiene muchos hitos
     */
    public function hitos()
    {
        return $this->hasMany(HitosApoyo::class, 'fk_id_apoyo', 'id_apoyo');
    }

    /**
     * Relación: Un apoyo tiene muchos logs de sincronización de calendario
     */
    public function sincronizacionLogs()
    {
        return $this->hasMany(CalendarioSincronizacionLog::class, 'fk_id_apoyo', 'id_apoyo');
    }

    /**
     * Scope: obtener apoyos con sincronización de calendario habilitada
     */
    public function scopeSincronizacionHabilitada($query)
    {
        return $query->where('sincronizar_calendario', true);
    }

    /**
     * Scope: obtener apoyos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }
}

