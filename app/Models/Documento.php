<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Documento extends Model
{
    protected $table = 'Documentos_Expediente';
    protected $primaryKey = 'id_doc';
    public $timestamps = false;

    protected $fillable = [
        'fk_folio',
        'fk_id_tipo_doc',
        'ruta_archivo',
        'estado_validacion',
        'version',
        'fecha_carga',
        'origen_archivo',
        'google_file_id',
        'google_file_name',
        'admin_status',
        'admin_observations',
        'verification_token',
        'id_admin',
        'fecha_verificacion',
    ];

    protected $casts = [
        'fecha_carga' => 'datetime',
        'fecha_verificacion' => 'datetime',
    ];

    /**
     * Relación con Solicitud
     */
    public function solicitud(): BelongsTo
    {
        return $this->belongsTo(Solicitud::class, 'fk_folio', 'folio');
    }

    /**
     * Relación con TipoDocumento
     */
    public function tipoDocumento(): BelongsTo
    {
        return $this->belongsTo(TipoDocumento::class, 'fk_id_tipo_doc', 'id_tipo_doc');
    }

    /**
     * Relación con admin User
     */
    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'id_admin', 'id_usuario');
    }

    /**
     * Scope para obtener documentos pendientes de verificación
     */
    public function scopePendientes($query)
    {
        return $query->where('admin_status', 'pendiente');
    }

    /**
     * Scope para obtener documentos aceptados
     */
    public function scopeAceptados($query)
    {
        return $query->where('admin_status', 'aceptado');
    }

    /**
     * Scope para obtener documentos rechazados
     */
    public function scopeRechazados($query)
    {
        return $query->where('admin_status', 'rechazado');
    }

    /**
     * Verifica si el documento es de origen local
     */
    public function isLocal(): bool
    {
        return $this->origen_archivo === 'local';
    }

    /**
     * Verifica si el documento es de Google Drive
     */
    public function isFromDrive(): bool
    {
        return $this->origen_archivo === 'drive';
    }

    /**
     * Obtiene la ruta de acceso del documento
     */
    public function getAccessPath(): string
    {
        if ($this->isLocal()) {
            return $this->ruta_archivo;
        }

        return $this->google_file_id ?? '';
    }

    /**
     * Obtiene el nombre del archivo
     */
    public function getFileName(): string
    {
        if ($this->isLocal()) {
            return basename($this->ruta_archivo);
        }

        return $this->google_file_name ?? 'Documento';
    }
}
