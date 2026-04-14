<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Solicitud extends Model
{
    protected $table = 'Solicitudes';
    protected $primaryKey = 'folio';
    public $timestamps = false;
    public $incrementing = false;
    protected $keyType = 'int';

    protected $fillable = [
        'fk_curp',
        'fk_id_apoyo',
        'fk_id_estado',
        'fk_id_prioridad',
        'fecha_creacion',
        'fecha_actualizacion',
        'observaciones_internas',
        'presupuesto_confirmado',
        'fecha_confirmacion_presupuesto',
        'directivo_autorizo',
    ];

    protected $casts = [
        'fecha_creacion' => 'datetime',
        'fecha_actualizacion' => 'datetime',
        'fecha_confirmacion_presupuesto' => 'datetime',
        'presupuesto_confirmado' => 'boolean',
    ];

    /**
     * Relación con Beneficiario
     */
    public function beneficiario(): BelongsTo
    {
        return $this->belongsTo(Beneficiario::class, 'fk_curp', 'curp');
    }

    /**
     * Relación con Apoyo
     */
    public function apoyo(): BelongsTo
    {
        return $this->belongsTo(Apoyo::class, 'fk_id_apoyo', 'id_apoyo');
    }

    /**
     * Relación con Documentos
     */
    public function documentos(): HasMany
    {
        return $this->hasMany(Documento::class, 'fk_folio', 'folio');
    }

    /**
     * Obtiene documentos pendientes de verificación
     */
    public function documentosPendientes(): HasMany
    {
        return $this->documentos()->where('admin_status', 'pendiente');
    }

    /**
     * Obtener los movimientos presupuestarios asociados a esta solicitud
     */
    public function movimientosPresupuestarios(): HasMany
    {
        return $this->hasMany(MovimientoPresupuestario::class, 'folio_solicitud', 'folio');
    }

    /**
     * Obtener el presupuesto reservado/aprobado para esta solicitud
     * (através del movimiento presupuestario)
     */
    public function getPresupuestoAsignado()
    {
        // Buscar el movimiento presupuestario de tipo RESERVA o ASIGNACION
        $movimiento = $this->movimientosPresupuestarios()
            ->whereIn('tipo_movimiento', ['RESERVA_SOLICITUD', 'ASIGNACION_DIRECTIVO'])
            ->orderBy('created_at', 'desc')
            ->first();

        if ($movimiento && $movimiento->id_apoyo_presupuesto) {
            return PresupuestoApoyo::find($movimiento->id_apoyo_presupuesto);
        }

        return null;
    }

    /**
     * Verificar si la solicitud tiene presupuesto reservado/aprobado
     */
    public function tienePresupuestoAsignado(): bool
    {
        return !is_null($this->getPresupuestoAsignado());
    }

    /**
     * Obtener el monto presupuestario de la solicitud
     */
    public function getMontoPresupuestario()
    {
        $presupuesto = $this->getPresupuestoAsignado();
        if ($presupuesto) {
            return (float) $presupuesto->monto_solicitado;
        }
        return 0;
    }

    /**
     * Obtener el estado presupuestario de la solicitud
     */
    public function getEstadoPresupuestario()
    {
        $presupuesto = $this->getPresupuestoAsignado();
        if ($presupuesto) {
            return $presupuesto->estado;
        }
        return null;
    }

    /**
     * Obtener el directivo que autorizó la solicitud
     */
    public function directivoAutoriza()
    {
        return $this->belongsTo(Usuario::class, 'directivo_autorizo', 'id_usuario');
    }

    /**
     * Verificar si la solicitud tiene presupuesto confirmado
     */
    public function tienePresupuestoConfirmado(): bool
    {
        return $this->presupuesto_confirmado === true;
    }

    /**
     * Obtener diferencia de tiempo entre confirmación de presupuesto y ahora
     */
    public function horasDesdeConfirmacion(): ?int
    {
        if (!$this->fecha_confirmacion_presupuesto) {
            return null;
        }

        return $this->fecha_confirmacion_presupuesto->diffInHours(now());
    }
}
