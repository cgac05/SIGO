<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Personal extends Model
{
    protected $table = 'Personal';
    protected $primaryKey = 'numero_empleado';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'numero_empleado',
        'fk_id_usuario',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'fk_rol',
        'puesto',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_id_usuario', 'id_usuario');
    }
}