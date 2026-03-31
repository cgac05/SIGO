<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DirectivoCalendarioPermiso extends Model
{
    protected $table = 'directivos_calendario_permisos';
    protected $primaryKey = 'id_permiso';
    public $timestamps = false;

    protected $fillable = [
        'fk_id_directivo',
        'google_calendar_id',
        'google_access_token',
        'google_refresh_token',
        'token_expiracion',
        'email_directivo',
        'calendarios_sincronizados',
        'ultima_sincronizacion',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'token_expiracion' => 'datetime',
        'ultima_sincronizacion' => 'datetime'
    ];

    protected $hidden = [
        'google_access_token',
        'google_refresh_token'
    ];

    // Relationships
    public function directivo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'fk_id_directivo', 'id_usuario');
    }

    /**
     * Verificar si el token de Google vence pronto (dentro de 5 minutos)
     */
    public function tokenVencePronto()
    {
        if (!$this->token_expiracion) {
            return true; // Sin fecha de expiración = vence pronto
        }
        
        // Si el token vence en menos de 5 minutos, se considera que vence pronto
        return $this->token_expiracion->lessThanOrEqualTo(
            \Carbon\Carbon::now()->addMinutes(5)
        );
    }
}
