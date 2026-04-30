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
        if ($this->fk_id_usuario === null) {
            $nombreParcial = trim((string) $this->nombre);

            if ($nombreParcial !== '') {
                return $nombreParcial;
            }
        }

        return trim(collect([
            $this->nombre,
            $this->apellido_paterno,
            $this->apellido_materno,
        ])->filter()->implode(' '));
    }

    public function getEdadAttribute(): ?int
    {
        if ($this->fecha_nacimiento) {
            return $this->fecha_nacimiento->age;
        }

        if ($this->curp && strlen($this->curp) >= 18) {
            $fechaNacimiento = $this->extraerFechaNacimientoDeCurp($this->curp);
            if ($fechaNacimiento) {
                return $fechaNacimiento->age;
            }
        }

        return null;
    }

    public function getSexoLabelAttribute(): string
    {
        $genero = $this->genero;

        if (!$genero && $this->curp && strlen($this->curp) >= 18) {
            $genero = substr($this->curp, 10, 1);
        }

        return match (mb_strtoupper((string) $genero)) {
            'H' => 'Masculino',
            'M' => 'Femenino',
            default => '—',
        };
    }

    private function extraerFechaNacimientoDeCurp(string $curp): ?\Carbon\Carbon
    {
        if (strlen($curp) < 18) {
            return null;
        }

        $fechaStr = substr($curp, 4, 6); // YYMMDD
        $sigloStr = substr($curp, 16, 1);

        // En la CURP, el carácter 17 (índice 16) determina el siglo
        // 0-9 para nacidos en 1999 o antes
        // A-Z para nacidos en 2000 o después
        if (is_numeric($sigloStr)) {
            $yearPrefix = '19';
        } else {
            $yearPrefix = '20';
        }

        $year = $yearPrefix . substr($fechaStr, 0, 2);
        $month = substr($fechaStr, 2, 2);
        $day = substr($fechaStr, 4, 2);

        try {
            return \Carbon\Carbon::createFromDate((int)$year, (int)$month, (int)$day);
        } catch (\Exception $e) {
            return null;
        }
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