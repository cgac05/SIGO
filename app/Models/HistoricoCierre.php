<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Modelo para la tabla `Historico_Cierre`.
 * 
 * Uso: Registra todos los pagos/desembolsos realizados a beneficiarios.
 * 
 * Campos:
 * - id_historico (int, PK)
 * - fk_folio (string) FK -> Solicitudes.folio
 * - fk_id_usuario (int) FK -> Usuarios.id_usuario
 * - monto_entregado (decimal)
 * - fecha_entrega (datetime)
 * - ruta_pdf_final (varchar, nullable) -> Ruta al comprobante PDF
 * - descripcion (text, nullable)
 * - snapshot_json (json, nullable) -> Estado anterior al pago
 * - estado_pago (varchar) -> COMPLETADO, PENDIENTE, CANCELADO
 * - ip_terminal (varchar, nullable) -> IP desde donde se registró
 * - created_at (timestamp)
 * - updated_at (timestamp)
 */
class HistoricoCierre extends Model
{
    protected $table = 'Historico_Cierre';
    protected $primaryKey = 'id_historico';
    public $timestamps = true;

    protected $fillable = [
        'fk_folio',
        'fk_id_usuario',
        'monto_entregado',
        'fecha_entrega',
        'ruta_pdf_final',
        'descripcion',
        'snapshot_json',
        'estado_pago',
        'ip_terminal',
        'hash_certificado',
        'qrcode_data',
        'ruta_qrcode',
        'firma_digital',
        'fecha_certificacion',
        'estado_certificacion',
        'cadena_custodia_json',
    ];

    protected $casts = [
        'monto_entregado' => 'decimal:2',
        'fecha_entrega' => 'datetime',
        'snapshot_json' => 'array',
        'fecha_certificacion' => 'datetime',
        'cadena_custodia_json' => 'array',
    ];

    /**
     * Relación con Solicitud
     */
    public function solicitud()
    {
        return $this->belongsTo(Solicitud::class, 'fk_folio', 'folio');
    }

    /**
     * Relación con Usuario
     */
    public function usuario()
    {
        return $this->belongsTo(User::class, 'fk_id_usuario', 'id_usuario');
    }

    /**
     * Relación con Archivos de Certificado (Fase 9 Parte 4)
     */
    public function archivos()
    {
        return $this->hasMany(ArchivoCertificado::class, 'id_historico', 'id_historico');
    }

    /**
     * Relación con Versiones de Certificado (Fase 9 Parte 4)
     */
    public function versiones()
    {
        return $this->hasMany(VersionCertificado::class, 'id_historico', 'id_historico');
    }

    /**
     * Relación con Auditoría de Verificación (Fase 9 Parte 3)
     */
    public function auditorias()
    {
        return $this->hasMany(AuditoriaVerificacion::class, 'id_historico', 'id_historico');
    }
}
