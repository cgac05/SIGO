<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PresupuestoApoyo extends Model
{
    protected $table = 'presupuesto_apoyos';
    protected $primaryKey = 'id_presupuesto_apoyo';
    protected $guarded = [];
    protected $casts = [
        'costo_estimado' => 'decimal:2',
        'fecha_reserva' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // ========== RELATIONSHIPS ==========
    
    public function apoyo(): BelongsTo
    {
        return $this->belongsTo(Apoyo::class, 'id_apoyo', 'id_apoyo');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(PresupuestoCategoria::class, 'id_categoria', 'id_categoria');
    }

    public function directivoAprobador(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'id_directivo_aprobador', 'id_usuario');
    }

    public function movimientos(): HasMany
    {
        return $this->hasMany(MovimientoPresupuestario::class, 'id_presupuesto_apoyo', 'id_presupuesto_apoyo');
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
        return $query->where('id_apoyo', $id_apoyo);
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
        if (!$this->categoria || !$this->categoria->isDisponibleFor($this->costo_estimado)) {
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
            'id_directivo_aprobador' => $id_directivo_aprobador,
        ]);
    }

    public function getCostoEstimadoFormato(): string
    {
        return '$' . number_format($this->costo_estimado, 2);
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
