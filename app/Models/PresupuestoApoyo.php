<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresupuestoApoyo extends Model
{
    protected $table = 'presupuesto_apoyos';
    protected $primaryKey = 'id_apoyo_presupuesto';
    protected $guarded = [];
    protected $casts = [
        'monto_solicitado' => 'decimal:2',
        'monto_aprobado' => 'decimal:2',
        'fecha_solicitud' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========
    
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'folio', 'folio');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(PresupuestoCategoria::class, 'id_categoria', 'id_categoria');
    }

    public function aprobador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'aprobado_por', 'id_usuario');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoPresupuestario::class, 'id_apoyo_presupuesto', 'id_apoyo_presupuesto');
    }

    // ========== SCOPES ==========
    
    public function scopeReservados($query)
    {
        return $query->where('estado', 'RESERVADO');
    }

    public function scopeAprobados($query)
    {
        return $query->where('estado', 'APROBADO');
    }

    public function scopeDelApoyo($query, $id_apoyo)
    {
        return $query->where('folio', $id_apoyo);
    }

    // ========== ACCESSORS (Compatibilidad con código legado) ==========
    
    /**
     * Alias para monto_solicitado (compatibilidad)
     */
    public function getCostoEstimadoAttribute()
    {
        return $this->monto_solicitado;
    }

    /**
     * Alias para fecha_solicitud (compatibilidad)
     */
    public function getFechaReservaAttribute()
    {
        return $this->fecha_solicitud;
    }

    // ========== METHODS ==========
    
    public function isReservado(): bool
    {
        return $this->estado === 'RESERVADO';
    }

    public function isAprobado(): bool
    {
        return $this->estado === 'APROBADO';
    }

    public function canBeApproved(): bool
    {
        // Must be RESERVADO to be approved
        if (!$this->isReservado()) {
            return false;
        }

        // Categoria must have available budget
        if (!$this->categoria || !$this->categoria->isDisponibleFor($this->monto_solicitado)) {
            return false;
        }

        return true;
    }

    /**
     * Approve this budget allocation (CRITICAL: IRREVERSIBLE)
     * Transitions from RESERVADO → APROBADO
     * Locks budget at categoria level
     */
    public function approve($id_directivo_aprobador): bool
    {
        if (!$this->canBeApproved()) {
            return false;
        }

        return $this->update([
            'estado' => 'APROBADO',
            'fecha_aprobacion' => now(),
            'aprobado_por' => $id_directivo_aprobador,
        ]);
    }

    public function getCostoEstimadoFormato(): string
    {
        return '$' . number_format($this->monto_solicitado, 2);
    }

    public function getBadgeColor(): string
    {
        return $this->estado === 'APROBADO' ? 'green' : 'yellow';
    }

    public function getBadgeIcon(): string
    {
        return $this->estado === 'APROBADO' ? 'check-circle' : 'clock';
    }
}
