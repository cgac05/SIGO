<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VersionCertificado extends Model
{
    use HasFactory;

    protected $table = 'version_certificado';
    protected $primaryKey = 'id_version';
    public $timestamps = true;

    protected $fillable = [
        'id_historico',
        'numero_version',
        'tipo_cambio',
        'datos_version',
        'descripcion',
        'id_usuario',
        'ip_terminal',
    ];

    protected $casts = [
        'numero_version' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relación con HistoricoCierre
    public function historico()
    {
        return $this->belongsTo(HistoricoCierre::class, 'id_historico', 'id_historico');
    }

    // Relación con Usuario
    public function usuario()
    {
        return $this->belongsTo(User::class, 'id_usuario', 'id');
    }

    // Scope para obtener versiones recientes
    public function scopeRecientes($query)
    {
        return $query->orderBy('numero_version', 'desc');
    }
}
