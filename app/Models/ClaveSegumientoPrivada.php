<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ClaveSegumientoPrivada extends Model
{
    protected $table = 'claves_seguimiento_privadas';
    protected $primaryKey = 'id_clave';
    public $timestamps = false;

    protected $fillable = [
        'folio',
        'clave_alfanumerica',
        'hash_clave',
        'beneficiario_id',
        'fecha_creacion',
        'fecha_ultimo_acceso',
        'intentos_fallidos',
        'bloqueada'
    ];

    protected $casts = [
        'bloqueada' => 'boolean',
        'fecha_creacion' => 'datetime',
        'fecha_ultimo_acceso' => 'datetime'
    ];

    // Relationships
    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'beneficiario_id', 'id_usuario');
    }
}
