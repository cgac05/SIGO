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
        'id_categoria',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'sincronizar_calendario' => 'boolean',
        'recordatorio_dias' => 'integer',
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
    ];

    /**
     * Relación: Un apoyo pertenece a una categoría de presupuesto
     */
    public function categoria()
    {
        return $this->belongsTo(PresupuestoCategoria::class, 'id_categoria', 'id_categoria');
    }

    /**
     * Relación: Un apoyo tiene muchos registros de presupuesto (uno por ciclo)
     */
    public function presupuestos()
    {
        return $this->hasMany(PresupuestoApoyo::class, 'id_apoyo', 'id_apoyo');
    }

    /**
     * Relación: Un apoyo tiene muchas solicitudes
     */
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'fk_id_apoyo', 'id_apoyo');
    }

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

    /**
     * Obtener el presupuesto actual del apoyo (ciclo actual)
     */
    public function getPresupuestoActual()
    {
        return $this->presupuestos()
            ->whereHas('categoria', function ($query) {
                $query->where('activo', true);
            })
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Verificar si el apoyo tiene presupuesto disponible
     */
    public function tienePresupuestoDisponible($monto = 0): bool
    {
        $presupuesto = $this->getPresupuestoActual();
        if (!$presupuesto || !$presupuesto->categoria) {
            return false;
        }
        return $presupuesto->categoria->isDisponibleFor($monto);
    }

    /**
     * Obtener presupuesto disponible en categoría del apoyo
     */
    public function getPresupuestoDisponibleFormato()
    {
        $presupuesto = $this->getPresupuestoActual();
        if (!$presupuesto || !$presupuesto->categoria) {
            return '$0.00';
        }
        return $presupuesto->categoria->getDisponibleFormato();
    }
}

