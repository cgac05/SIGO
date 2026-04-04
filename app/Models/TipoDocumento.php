<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TipoDocumento extends Model
{
    protected $table = 'Cat_TiposDocumento';
    protected $primaryKey = 'id_tipo_doc';
    public $timestamps = false;

    protected $fillable = [
        'nombre_documento',
        'tipo_archivo_permitido',
        'validar_tipo_archivo',
        'descripcion',
        'peso_maximo_mb',
    ];

    protected $casts = [
        'validar_tipo_archivo' => 'boolean',
        'peso_maximo_mb' => 'integer',
    ];
}
