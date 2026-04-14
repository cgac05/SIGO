<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimientoPresupuestario extends Model
{
    protected $table = 'movimientos_presupuestarios';
    protected $primaryKey = 'id_movimiento';
    protected $guarded = [];
    protected $casts = [
        'monto' => 'decimal:2',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    const TIPO_RESERVACION = 'RESERVACION';
    const TIPO_ASIGNACION_DIRECTIVO = 'ASIGNACION_DIRECTIVO';
    const TIPO_CANCELACION = 'CANCELACION';
    const TIPO_REITERACION = 'REITERACION';

    // ========== RELATIONSHIPS ==========
    
    public function presupuestoApoyo(): BelongsTo
    {
        return $this->belongsTo(PresupuestoApoyo::class, 'id_apoyo_presupuesto', 'id_apoyo_presupuesto');
    }

    public function categoria(): BelongsTo
    {
        return $this->belongsTo(PresupuestoCategoria::class, 'id_categoria', 'id_categoria');
    }

    public function usuarioResponsable(): BelongsTo
    {
        return $this->belongsTo(Usuario::class, 'creado_por', 'id_usuario');
    }

    // ========== SCOPES ==========
    
    public function scopeTipo($query, $tipo)
    {
        return $query->where('tipo_movimiento', $tipo);
    }

    public function scopeReservaciones($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_RESERVACION);
    }

    public function scopeAsignaciones($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_ASIGNACION_DIRECTIVO);
    }

    public function scopeCancelaciones($query)
    {
        return $query->where('tipo_movimiento', self::TIPO_CANCELACION);
    }

    public function scopeDelPresupuesto($query, $id_presupuesto_apoyo)
    {
        return $query->where('id_apoyo_presupuesto', $id_presupuesto_apoyo);
    }

    // ========== METHODS ==========
    
    public function getTipoLabel(): string
    {
        $labels = [
            self::TIPO_RESERVACION => 'Reservación',
            self::TIPO_ASIGNACION_DIRECTIVO => 'Asignación Directivo',
            self::TIPO_CANCELACION => 'Cancelación',
            self::TIPO_REITERACION => 'Reiteración',
        ];
        return $labels[$this->tipo_movimiento] ?? $this->tipo_movimiento;
    }

    public function getTipoColor(): string
    {
        $colors = [
            self::TIPO_RESERVACION => 'yellow',
            self::TIPO_ASIGNACION_DIRECTIVO => 'green',
            self::TIPO_CANCELACION => 'red',
            self::TIPO_REITERACION => 'blue',
        ];
        return $colors[$this->tipo_movimiento] ?? 'gray';
    }

    public function getTipoIcon(): string
    {
        $icons = [
            self::TIPO_RESERVACION => 'shield-check',
            self::TIPO_ASIGNACION_DIRECTIVO => 'check-circle',
            self::TIPO_CANCELACION => 'x-circle',
            self::TIPO_REITERACION => 'refresh-cw',
        ];
        return $icons[$this->tipo_movimiento] ?? 'alert';
    }

    public function getMontoFormato(): string
    {
        return '$' . number_format($this->monto, 2);
    }
}
