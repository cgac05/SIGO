<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class Beneficiario extends Model
{
    use Notifiable;

    protected $table = 'Beneficiarios';
    protected $primaryKey = 'curp';
    public $incrementing = false;
    protected $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'curp',
        'fk_id_usuario',
        'nombre',
        'apellido_paterno',
        'apellido_materno',
        'telefono',
        'fecha_nacimiento',
        'genero',
        'acepta_privacidad',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_registro' => 'datetime',
        'acepta_privacidad' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_id_usuario', 'id_usuario');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim(collect([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter()->implode(' '));
    }

    /**
     * Retorna el email para notificaciones (obtiene del usuario asociado)
     */
    public function getEmailForNotification(): ?string
    {
        return $this->user?->email;
    }

    /**
     * Retorna el nombre para notificaciones
     */
    public function getNotificationToAttribute(): string
    {
        return $this->getEmailForNotification() ?? 'no-email@example.com';
    }
}