<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PoliticaRetencionDocumento extends Model
{
    protected $table = 'politicas_retencion_documentos';
    protected $primaryKey = 'id_politica';
    public $timestamps = false;

    protected $fillable = [
        'fk_id_documento',
        'folio',
        'hito_cierre_apoyo',
        'fecha_cierre_apoyo',
        'retencion_cumplida',
        'fecha_borrado',
        'razon_borrado'
    ];

    protected $casts = [
        'retencion_cumplida' => 'boolean',
        'fecha_cierre_apoyo' => 'datetime',
        'fecha_borrado' => 'datetime'
    ];

    // Relationships
    public function documento(): BelongsTo
    {
        return $this->belongsTo(Documento::class, 'fk_id_documento', 'id_doc');
    }
}
