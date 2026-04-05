<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ArchivoCertificado extends Model
{
    use HasFactory;

    protected $table = 'archivo_certificado';
    protected $primaryKey = 'id_archivo';
    public $timestamps = true;

    protected $fillable = [
        'id_historico',
        'uuid_archivo',
        'nombre_archivo',
        'ruta_almacenamiento',
        'tamanio_bytes',
        'hash_integridad',
        'tipo_compresion',
        'motivo_archivado',
        'activo',
        'id_usuario_archivador',
        'fecha_eliminacion',
    ];

    protected $casts = [
        'tamanio_bytes' => 'integer',
        'activo' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'fecha_eliminacion' => 'datetime',
    ];

    // Relación con HistoricoCierre
    public function historico()
    {
        return $this->belongsTo(HistoricoCierre::class, 'id_historico', 'id_historico');
    }

    // Relación con Usuario archivador
    public function usuarioArchivador()
    {
        return $this->belongsTo(User::class, 'id_usuario_archivador', 'id');
    }

    // Scope para archivos activos
    public function scopeActivos($query)
    {
        return $query->where('activo', 1);
    }
}
