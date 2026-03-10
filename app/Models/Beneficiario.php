<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class Beneficiario extends Authenticatable
{
    use Notifiable;

    protected $table = 'Beneficiarios';
    protected $primaryKey = 'curp';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = ['curp', 'nombre', 'apellido_paterno', 'apellido_materno', 'correo_electronico', 'correo', 'pass_hash', 'activo'];
    protected $hidden = ['pass_hash', 'remember_token'];

    public function getCorreoElectronicoAttribute()
    {
        return $this->attributes['correo_electronico'] ?? $this->attributes['correo'] ?? null;
    }

    public function getAuthPassword()
    {
        return $this->pass_hash;
    }

    public function getAuthIdentifierName()
    {
        return 'curp';
    }

    public function getAuthIdentifier()
    {
        return $this->curp;
    }
}