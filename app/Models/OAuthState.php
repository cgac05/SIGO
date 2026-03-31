<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * OAuthState
 * 
 * Almacena temporalmente los states CSRF utilizados en flujos OAuth.
 * Se utiliza para validar que el callback de OAuth es legítimo.
 * 
 * Campos:
 * - id: ID único
 * - state: String único del state (formato: base64(directivo_id).random_hex)
 * - directivo_id: ID del usuario que inició el OAuth
 * - provider: Proveedor OAuth (google, microsoft, other)
 * - created_at: Cuándo se generó el state
 * - expires_at: Cuándo expira el state
 * - used_at: Cuándo se utilizó el state (NULL si no se utilizó)
 * - redirect_url: URL a la que redirigir después de éxito (opcional)
 */
class OAuthState extends Model
{
    use HasFactory;

    protected $table = 'oauth_states';
    protected $fillable = [
        'state',
        'directivo_id',
        'provider',
        'created_at',
        'expires_at',
        'used_at',
        'redirect_url',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    // Deshabilitar timestamps automáticos de Eloquent
    public $timestamps = false;

    /**
     * Relación: Usuario (directivo)
     */
    public function directivo()
    {
        return $this->belongsTo(User::class, 'directivo_id', 'id_usuario');
    }

    /**
     * Generar un nuevo state válido
     * 
     * Estado es alfanumérico sin caracteres especiales para compatibilidad con OAuth:
     * Formato: {directivo_id}_{random_hex}
     * 
     * @param int $directivo_id
     * @param string $provider (default: 'google')
     * @param int $expiryMinutes (default: 30 minutos)
     * @return string
     */
    public static function generateState($directivo_id, $provider = 'google', $expiryMinutes = 30)
    {
        // Generar state alfanumérico sin caracteres especiales (compatible con OAuth)
        $randomPart = bin2hex(random_bytes(16));
        $state = $directivo_id . '_' . $randomPart;

        // Guardar en BD - Usar DB::raw para timestamps con SQL Server
        \Illuminate\Support\Facades\DB::table('oauth_states')->insert([
            'state' => $state,
            'directivo_id' => $directivo_id,
            'provider' => $provider,
            'created_at' => \Illuminate\Support\Facades\DB::raw('GETDATE()'),
            'expires_at' => \Illuminate\Support\Facades\DB::raw("DATEADD(MINUTE, {$expiryMinutes}, GETDATE())"),
        ]);

        return $state;
    }

    /**
     * Validar que un state sea válido
     * 
     * @param string $state
     * @return bool|OAuthState (false si inválido, modelo si válido)
     */
    public static function validateState($state)
    {
        if (!$state) {
            return false;
        }

        // Buscar el state en BD - usar whereRaw con GETDATE() de SQL Server
        $oauthState = self::where('state', $state)
            ->whereRaw('expires_at > GETDATE()')
            ->whereNull('used_at')
            ->first();

        if (!$oauthState) {
            return false;
        }

        return $oauthState;
    }

    /**
     * Marcar un state como utilizado
     * 
     * @return bool
     */
    public function markAsUsed()
    {
        // Usar raw query para SQL Server compatibility (evita problemas con type casting de Eloquent)
        return \Illuminate\Support\Facades\DB::table('oauth_states')
            ->where('id', $this->id)
            ->update(['used_at' => \Illuminate\Support\Facades\DB::raw('GETDATE()')]) > 0;
    }

    /**
     * Limpiar states expirados
     * 
     * @return int (número de estados eliminados)
     */
    public static function cleanupExpired()
    {
        return self::whereRaw('expires_at < GETDATE()')
            ->delete();
    }
}
