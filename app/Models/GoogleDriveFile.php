<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GoogleDriveFile extends Model
{
    protected $table = 'google_drive_files';
    protected $primaryKey = 'id';
    public $timestamps = true;

    protected $fillable = [
        'user_id',
        'google_file_id',
        'file_name',
        'file_size',
        'mime_type',
        'storage_path',
    ];

    protected $casts = [
        'file_size' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Relación BelongsTo con User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id_usuario');
    }

    /**
     * Obtener la extensión del archivo
     */
    public function getExtensionAttribute(): string
    {
        return strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
    }

    /**
     * Formatar tamaño de archivo en KB/MB
     */
    public function getFormattedSizeAttribute(): string
    {
        $size = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = max($size, 0);
        $pow = floor(($size ? log($size) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $size /= (1 << (10 * $pow));

        return round($size, 2) . ' ' . $units[$pow];
    }
}
