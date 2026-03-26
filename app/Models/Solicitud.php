<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitud extends Model
{
    protected $table = 'Solicitudes';
    protected $primaryKey = 'folio';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'fk_curp',
        'fk_id_apoyo',
        'fk_id_estado',
        'fk_id_prioridad',
        'fecha_creacion',
        'fecha_actualizacion',
        'observaciones_internas',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
    ];

    /**
     * Relación con Beneficiario
     */
    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(Beneficiario::class, 'fk_curp', 'curp');
    }

    /**
     * Relación con Apoyo
     */
    public function apoyo(): BelongsTo
    {
        return $this->belongsTo(Apoyo::class, 'fk_id_apoyo', 'id_apoyo');
    }

    /**
     * Relación con Documentos
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'fk_folio', 'folio');
    }

    /**
     * Obtiene documentos pendientes de verificación
     */
    public function documentosPendientes(): HasMany
    {
        return $this->documentos()->where('admin_status', 'pendiente');
    }
}
