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
        'google_token_expires_at',
        'google_avatar',
        'foto_ruta',
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
        'google_token_expires_at' => 'datetime',
    ];


    /**
     * Obtener URL de la foto del usuario
     */
    public function getFotoUrl(): string
    {
        // Prioridad: foto local > google avatar > avatar por defecto
        if ($this->foto_ruta && file_exists(storage_path('app/fotos/' . $this->foto_ruta))) {
            return asset('storage/fotos/' . $this->foto_ruta);
        }

        if ($this->google_avatar) {
            return $this->google_avatar;
        }

        return asset('images/avatar-default.png');
    }

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

    /**
     * Verificar si el token de Google ha expirado
     */
    public function isGoogleTokenExpired(): bool
    {
        return $this->google_token_expires_at?->isPast() ?? true;
    }

    /**
     * Obtener cliente Google autenticado
     */
    public function getGoogleClient(): \Google_Client
    {
        $client = new \Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        
        if ($this->google_token) {
            $client->setAccessToken(json_decode($this->google_token, true) ?: $this->google_token);
            
            if ($this->isGoogleTokenExpired() && $this->google_refresh_token) {
                try {
                    $client->fetchAccessTokenWithRefreshToken($this->google_refresh_token);
                    $newToken = $client->getAccessToken();
                    $this->update([
                        'google_token' => json_encode($newToken),
                        'google_token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
                    ]);
                } catch (\Exception $e) {
                    \Log::warning('Error al refrescar token de Google: ' . $e->getMessage());
                }
            }
        }
        
        return $client;
    }

    /**
     * Relación hasMany con Google Drive Files
     */
    public function googleDriveFiles()
    {
        return $this->hasMany(GoogleDriveFile::class, 'user_id', 'id_usuario');
    }

    /**
     * Relación hasOne con permisos de Google Calendar
     */
    public function calendarioPermiso()
    {
        return $this->hasOne(DirectivoCalendarioPermiso::class, 'fk_id_directivo', 'id_usuario');
    }
}