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
}
