<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements MustVerifyEmailContract
{
    use HasFactory;
    use MustVerifyEmail;
    use Notifiable;

    protected $table = 'Usuarios';
    protected $primaryKey = 'id_usuario';
    public $timestamps = false;

    protected $fillable = [
        'email',
        'password_hash',
        'tipo_usuario',
        'google_id',
        'google_token',
        'google_refresh_token',
        'google_avatar',
        'activo',
        'ultima_conexion',
        'remember_token',
        'debe_cambiar_password',
    ];

    protected $hidden = [
        'password_hash',
        'remember_token',
        'google_token',
        'google_refresh_token',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'email_verified_at' => 'datetime',
        'fecha_creacion' => 'datetime',
        'ultima_conexion' => 'datetime',
    ];


    public function getAuthPassword(): string
    {
        return (string) ($this->password_hash ?? '');
    }

    public function personal(): HasOne
    {
        return $this->hasOne(Personal::class, 'fk_id_usuario', 'id_usuario');
    }

    public function beneficiario(): HasOne
    {
        return $this->hasOne(Beneficiario::class, 'fk_id_usuario', 'id_usuario');
    }

    public function isBeneficiario(): bool
    {
        return strcasecmp((string) $this->tipo_usuario, 'Beneficiario') === 0;
    }

    public function isPersonal(): bool
    {
        $tipo = mb_strtolower((string) $this->tipo_usuario);

        if (in_array($tipo, ['personal', 'administrativo', 'directivo'], true)) {
            return true;
        }

        if ($this->relationLoaded('personal')) {
            return $this->personal !== null;
        }

        return $this->personal()->exists();
    }

    public function hasCompleteBeneficiarioProfile(): bool
    {
        return ! $this->isBeneficiario() || $this->beneficiario()->exists();
    }

    public function getDisplayNameAttribute(): string
    {
        $profile = $this->isPersonal() ? $this->personal : $this->beneficiario;

        if (! $profile) {
            return (string) $this->email;
        }

        return trim(collect([
            $profile->nombre,
            $profile->apellido_paterno,
            $profile->apellido_materno,
        ])->filter()->implode(' '));
    }

    public function getNameAttribute(): string
    {
        return $this->display_name;
    }

    public function getIdAttribute(): int
    {
        return (int) $this->id_usuario;
    }

    public function getPasswordAttribute(): string
    {
        return (string) ($this->password_hash ?? '');
    }
}