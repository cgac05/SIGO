<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CalendarioSincronizacionLog extends Model
{
    protected $table = 'calendario_sincronizacion_log';
    protected $primaryKey = 'id_log';
    public $timestamps = false; // We use custom fecha_cambio column

    protected $fillable = [
        'fk_id_hito',
        'fk_id_apoyo',
        'usuario_id',
        'tipo_cambio',
        'origen',
        'datos_anteriores',
        'datos_nuevos',
        'fecha_cambio',
        'sincronizado',
        'error_sincronizacion'
    ];

    protected $casts = [
        'datos_anteriores' => 'array',
        'datos_nuevos' => 'array',
        'fecha_cambio' => 'datetime',
        'sincronizado' => 'boolean'
    ];

    // Relationships
    public function hito(): BelongsTo
    {
        return $this->belongsTo(HitosApoyo::class, 'fk_id_hito', 'id_hito');
    }

    public function apoyo(): BelongsTo
    {
        return $this->belongsTo(Apoyo::class, 'fk_id_apoyo', 'id_apoyo');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'usuario_id', 'id_usuario');
    }

    /**
     * Get CSS classes based on tipo_cambio value
     */
    public function tipoCambioClass(): string
    {
        return match($this->tipo_cambio) {
            'creacion' => 'bg-green-100 text-green-800',
            'actualizacion' => 'bg-blue-100 text-blue-800',
            'eliminacion' => 'bg-red-100 text-red-800',
            'error' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    /**
     * Get formatted string for tipo_cambio display
     */
    public function getFormatoTipoCambio(): string
    {
        return match($this->tipo_cambio) {
            'creacion' => 'Creación',
            'actualizacion' => 'Actualización',
            'eliminacion' => 'Eliminación',
            'error' => 'Error',
            default => $this->tipo_cambio ?? 'N/A',
        };
    }
}
