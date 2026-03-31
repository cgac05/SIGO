<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CadenaDigitalDocumento extends Model
{
    protected $table = 'cadena_digital_documentos';
    protected $primaryKey = 'id_cadena';
    public $timestamps = false;

    protected $fillable = [
        'fk_id_documento',
        'folio',
        'hash_actual',
        'hash_anterior',
        'admin_creador',
        'timestamp_creacion',
        'firma_hmac',
        'razon_cambio'
    ];

    protected $casts = [
        'timestamp_creacion' => 'datetime'
    ];

    // Relationships
    public function documento(): BelongsTo
    {
        return $this->belongsTo(DocumentoExpediente::class, 'fk_id_documento', 'id_documento');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_creador', 'id_usuario');
    }
}
