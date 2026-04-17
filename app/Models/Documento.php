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
     * 
     * Tolerante con documentos donde el origen está mal marcado
     */
    public function isLocal(): bool
    {
        // Si está explícitamente marcado como local
        if ($this->origen_archivo === 'local') {
            return true;
        }

        // Si tiene ruta_archivo pero NO es ruta de Google Drive Y NO tiene google_file_id
        // Asumir que es local (por si está mal marcado)
        if ($this->ruta_archivo && 
            !str_starts_with($this->ruta_archivo, 'google_drive/') && 
            !$this->google_file_id) {
            return true;
        }

        // Legacy: Si no tiene google_file_id y tiene ruta_archivo local, asumir que es local
        if (!$this->google_file_id && $this->ruta_archivo && !str_starts_with($this->ruta_archivo, 'google_drive/')) {
            return true;
        }

        return false;
    }

    /**
     * Verifica si el documento es de Google Drive
     * 
     * Tolerante con documentos donde el origen está mal marcado
     */
    public function isFromDrive(): bool
    {
        // Si está explícitamente marcado como drive Y tiene google_file_id
        if (($this->origen_archivo === 'drive' || $this->origen_archivo === 'google_drive') && $this->google_file_id) {
            return true;
        }

        // Si tiene google_file_id, es de Drive (sin importar cómo esté marcado)
        if ($this->google_file_id) {
            return true;
        }

        // Si la ruta comienza con google_drive/, probablemente es de Drive
        if ($this->ruta_archivo && str_starts_with($this->ruta_archivo, 'google_drive/')) {
            return true;
        }

        return false;
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
