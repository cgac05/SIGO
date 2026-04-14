<?php

namespace App\Models;

use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

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
        'foto_perfil',
        'activo',
        'ultima_conexion',
        'remember_token',
        'two_factor_enabled',
        'two_factor_secret',
        'notif_email_news',
        'notif_email_apoyos',
        'notif_email_status',
        'notif_email_marketing',
        'arco_cancelacion_solicitada',
        'arco_cancelacion_fecha',
        'arco_cancelacion_razon',
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
     * Obtener URL de la foto del usuario
     * Retorna URL de Google Avatar si existe, sino retorna URL de almacenamiento local
     */
    public function getFotoUrl(): string
    {
        $googleAvatar = trim((string) $this->google_avatar);

        if ($googleAvatar !== '' && $this->isValidAvatarUrl($googleAvatar)) {
            return $googleAvatar;
        }

        $fotoPerfil = trim((string) $this->foto_perfil);

        if ($fotoPerfil !== '') {
            $resolvedUrl = $this->resolveStoredAvatarUrl($fotoPerfil);

            if ($resolvedUrl !== null) {
                return $resolvedUrl;
            }
        }

        foreach (['jpg', 'jpeg', 'png', 'gif', 'webp'] as $extension) {
            $localPhotoPath = "storage/fotos/{$this->id_usuario}.{$extension}";

            if (file_exists(public_path($localPhotoPath))) {
                return asset($localPhotoPath);
            }
        }

        return '';
    }

    public function getAvatarInitialAttribute(): string
    {
        $source = Str::squish((string) ($this->display_name ?: $this->email ?: 'U'));

        if ($source === '') {
            return 'U';
        }

        return mb_strtoupper(mb_substr($source, 0, 1));
    }

    public function getAvatarPlaceholderUrlAttribute(): string
    {
        $initial = htmlspecialchars($this->avatar_initial, ENT_QUOTES, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="256" height="256" viewBox="0 0 256 256" role="img" aria-label="Avatar de usuario">
  <defs>
    <linearGradient id="bg" x1="0%" y1="0%" x2="100%" y2="100%">
      <stop offset="0%" stop-color="#3b82f6"/>
      <stop offset="100%" stop-color="#1d4ed8"/>
    </linearGradient>
  </defs>
  <rect width="256" height="256" rx="128" fill="url(#bg)"/>
  <text x="50%" y="54%" dominant-baseline="middle" text-anchor="middle" fill="#ffffff" font-family="Arial, Helvetica, sans-serif" font-size="112" font-weight="700">{$initial}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }

    public function getAvatarUrlAttribute(): string
    {
        $fotoUrl = $this->getFotoUrl();

        return $fotoUrl !== '' ? $fotoUrl : $this->avatar_placeholder_url;
    }

    private function isValidAvatarUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false || str_starts_with($url, 'data:image/');
    }

    private function resolveStoredAvatarUrl(string $path): ?string
    {
        $normalizedPath = str_replace('\\', '/', ltrim($path, '/'));

        if ($normalizedPath === '') {
            return null;
        }

        $publicDiskPath = str_starts_with($normalizedPath, 'storage/')
            ? substr($normalizedPath, strlen('storage/'))
            : $normalizedPath;

        if ($publicDiskPath !== '' && Storage::disk('public')->exists($publicDiskPath)) {
            return Storage::disk('public')->url($publicDiskPath);
        }

        if (file_exists(public_path($normalizedPath))) {
            return asset($normalizedPath);
        }

        if ($publicDiskPath !== '' && file_exists(public_path('storage/' . $publicDiskPath))) {
            return asset('storage/' . $publicDiskPath);
        }

        return null;
    }
}