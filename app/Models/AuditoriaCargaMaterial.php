<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditoriaCargaMaterial extends Model
{
    protected $table = 'auditorias_carga_material';
    protected $primaryKey = 'id_auditoria';
    public $timestamps = false;

    protected $fillable = [
        'folio',
        'evento',
        'admin_id',
        'cantidad_docs',
        'fecha_evento',
        'ip_admin',
        'navegador_agente',
        'detalles_evento'
    ];

    protected $casts = [
        'fecha_evento' => 'datetime',
        'detalles_evento' => 'array'
    ];

    // Relationships
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id', 'id_usuario');
    }
}
