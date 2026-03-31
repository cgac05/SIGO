<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Events\HitoCambiado;

class HitosApoyo extends Model
{
    protected $table = 'hitos_apoyo';
    protected $primaryKey = 'id_hito';
    public $timestamps = false; // La tabla usa fecha_creacion/fecha_actualizacion manualmente

    protected $fillable = [
        'fk_id_apoyo',
        'nombre_hito',
        'descripcion',
        'fecha_inicio',
        'fecha_fin',
        'estado',
        'google_calendar_event_id',
        'google_calendar_sync',
        'ultima_sincronizacion',
        'cambios_locales_pendientes',
        'orden_hito',
        'clave_hito',
        'activo',
        'es_base',
    ];

    protected $casts = [
        'fecha_inicio' => 'datetime',
        'fecha_fin' => 'datetime',
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'ultima_sincronizacion' => 'datetime',
        'google_calendar_sync' => 'boolean',
        'cambios_locales_pendientes' => 'boolean',
        'activo' => 'boolean',
        'es_base' => 'boolean',
    ];

    /**
     * El evento HitoCambiado se dispara a través del Observer HitosApoyoObserver
     * en lugar de usar $dispatchesEvents, para poder pasar el tipo_cambio correctamente
     * 
     * Ver: app/Observers/HitosApoyoObserver.php
     */

    /**
     * Relación con Apoyo
     */
    public function apoyo()
    {
        return $this->belongsTo(Apoyo::class, 'fk_id_apoyo', 'id_apoyo');
    }

    /**
     * Relación con CalendarioSincronizacionLog
     */
    public function sincronizacionLogs()
    {
        return $this->hasMany(CalendarioSincronizacionLog::class, 'fk_id_hito', 'id_hito');
    }

    /**
     * Scope: obtener hitos pendientes de sincronización
     */
    public function scopePendienteSincronizacion($query)
    {
        return $query->where('cambios_locales_pendientes', true);
    }

    /**
     * Scope: obtener hitos con sincronización habilitada
     */
    public function scopeSincronizacionActiva($query)
    {
        return $query->where('google_calendar_sync', true);
    }

    /**
     * Marcar como sincronizado
     */
    public function marcarComSincronizado()
    {
        $this->update([
            'cambios_locales_pendientes' => false,
            'ultima_sincronizacion' => now(),
        ]);
    }

    /**
     * Marcar cambios como pendientes
     */
    public function marcarCambiosPendientes()
    {
        $this->update([
            'cambios_locales_pendientes' => true,
        ]);
    }
}
