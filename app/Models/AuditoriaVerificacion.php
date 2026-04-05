<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditoriaVerificacion extends Model
{
    use HasFactory;

    protected $table = 'auditoria_verificacion';
    protected $primaryKey = 'id_auditoria';
    public $timestamps = true;

    protected $fillable = [
        'id_historico',
        'tipo_verificacion',
        'detalles',
        'ip_terminal',
        'id_usuario_validador',
    ];

    protected $casts = [
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
        return $this->belongsTo(User::class, 'id_usuario_validador', 'id');
    }
}
