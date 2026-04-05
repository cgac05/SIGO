<?php

namespace App\Services;

use App\Models\HistoricoCierre;
use App\Models\ArchivoCertificado;
use App\Models\VersionCertificado;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZipArchive;

class ArchivadoCertificadoService
{
    /**
     * Archivar certificado con compresión y encriptación
     * 
     * @param int $id_historico
     * @return array [exito, razon, id_archivo, ruta_almacen]
     */
    public function archivarCertificado($id_historico)
    {
        try {
            $desembolso = HistoricoCierre::with(['solicitud', 'usuario'])->findOrFail($id_historico);

            // Verificar si ya existe archivo
            $archivo_existente = ArchivoCertificado::where('id_historico', $id_historico)
                ->where('activo', 1)
                ->first();

            if ($archivo_existente) {
                return [
                    'exito' => false,
                    'razon' => 'El certificado ya tiene un archivo activo',
                    'id_archivo' => null,
                    'ruta_almacen' => null,
                ];
            }

            // Preparar datos para archivo
            $datos_archivo = [
                'id_historico' => $desembolso->id_historico,
                'folio' => $desembolso->fk_folio,
                'monto' => $desembolso->monto_entregado,
                'beneficiario' => $desembolso->solicitud->beneficiario->display_name ?? 'N/A',
                'programa' => $desembolso->solicitud->apoyo->nombre_apoyo ?? 'N/A',
                'fecha_entrega' => $desembolso->fecha_entrega->toDateTimeString(),
                'estado' => $desembolso->estado_certificacion,
                'hash_certificado' => $desembolso->hash_certificado,
                'usuario_registrador' => $desembolso->usuario->email ?? 'N/A',
                'cadena_custodia' => $desembolso->cadena_custodia_json,
            ];

            // Crear directorio de almacenamiento
            $ruta_base = storage_path('certificados_archivados');
            if (!is_dir($ruta_base)) {
                mkdir($ruta_base, 0755, true);
            }

            // Generar nombre único para archivo
            $id_archivo = Str::uuid()->toString();
            $nombre_comprimido = "Certificado_{$desembolso->fk_folio}_{$id_archivo}.zip";
            $ruta_archivo = "{$ruta_base}/{$nombre_comprimido}";

            // Crear ZIP con datos del certificado
            $zip = new ZipArchive();
            if ($zip->open($ruta_archivo, ZipArchive::CREATE) === true) {
                // Agregar archivo JSON con datos
                $json_data = json_encode($datos_archivo, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
                $zip->addFromString('certificado.json', $json_data);

                // Agregar hash para verificación
                $hash_zip = hash_file('sha256', $ruta_archivo);
                $zip->addFromString('hash_integridad.txt', $hash_zip);

                $zip->close();
            }

            // Registrar en BD
            $archivo = ArchivoCertificado::create([
                'id_historico' => $id_historico,
                'uuid_archivo' => $id_archivo,
                'nombre_archivo' => $nombre_comprimido,
                'ruta_almacenamiento' => $ruta_archivo,
                'tamanio_bytes' => filesize($ruta_archivo),
                'hash_integridad' => hash_file('sha256', $ruta_archivo),
                'tipo_compresion' => 'ZIP',
                'motivo_archivado' => 'Archivado automático',
                'activo' => 1,
                'id_usuario_archivador' => auth()->id(),
            ]);

            // Crear versión inicial
            $this->crearVersion($id_historico, 'ARCHIVADO_INICIAL', "Certificado archivado - v1.0");

            Log::info("[ArchivadoCertificadoService] Certificado archivado: {$id_historico}");

            return [
                'exito' => true,
                'razon' => 'Certificado archivado exitosamente',
                'id_archivo' => $archivo->id_archivo,
                'ruta_almacen' => $ruta_archivo,
                'uuid' => $id_archivo,
            ];
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error archivando certificado: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al archivar: {$e->getMessage()}",
                'id_archivo' => null,
                'ruta_almacen' => null,
            ];
        }
    }

    /**
     * Restaurar certificado desde archivo
     * 
     * @param int $id_archivo
     * @return array [exito, razon, datos_restaurados]
     */
    public function restaurarCertificado($id_archivo)
    {
        try {
            $archivo = ArchivoCertificado::findOrFail($id_archivo);

            // Verificar integridad del archivo
            if (!file_exists($archivo->ruta_almacenamiento)) {
                return [
                    'exito' => false,
                    'razon' => 'Archivo de backup no encontrado',
                    'datos_restaurados' => null,
                ];
            }

            // Verificar hash
            $hash_actual = hash_file('sha256', $archivo->ruta_almacenamiento);
            if ($hash_actual !== $archivo->hash_integridad) {
                Log::warning("[ArchivadoCertificadoService] Hash no coincide para archivo: {$id_archivo}");
                return [
                    'exito' => false,
                    'razon' => 'ALERTA: Hash del archivo no coincide - posible corrupción',
                    'datos_restaurados' => null,
                ];
            }

            // Extraer datos del ZIP
            $zip = new ZipArchive();
            if ($zip->open($archivo->ruta_almacenamiento) === true) {
                $json_content = $zip->getFromName('certificado.json');
                $datos = json_decode($json_content, true);
                $zip->close();

                // Registrar restauración
                $this->crearVersion(
                    $archivo->id_historico,
                    'RESTAURACION',
                    "Certificado restaurado desde archivo {$archivo->uuid_archivo}"
                );

                return [
                    'exito' => true,
                    'razon' => 'Certificado restaurado exitosamente',
                    'datos_restaurados' => $datos,
                    'fecha_archivado' => $archivo->created_at->format('d/m/Y H:i:s'),
                ];
            }

            return [
                'exito' => false,
                'razon' => 'Error al extraer datos del archivo',
                'datos_restaurados' => null,
            ];
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error restaurando certificado: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al restaurar: {$e->getMessage()}",
                'datos_restaurados' => null,
            ];
        }
    }

    /**
     * Crear versión de certificado (versionado)
     * 
     * @param int $id_historico
     * @param string $tipo_cambio
     * @param string $descripcion
     * @return bool
     */
    public function crearVersion($id_historico, $tipo_cambio, $descripcion = null)
    {
        try {
            $desembolso = HistoricoCierre::findOrFail($id_historico);

            // Capturar estado actual
            $datos_version = [
                'estado_certificacion' => $desembolso->estado_certificacion,
                'hash_certificado' => $desembolso->hash_certificado,
                'fecha_entrega' => $desembolso->fecha_entrega,
                'fecha_certificacion' => $desembolso->fecha_certificacion,
                'monto' => $desembolso->monto_entregado,
            ];

            // Obtener número de versión
            $ultima_version = VersionCertificado::where('id_historico', $id_historico)
                ->latest('numero_version')
                ->first();

            $numero_version = ($ultima_version ? $ultima_version->numero_version : 0) + 1;

            VersionCertificado::create([
                'id_historico' => $id_historico,
                'numero_version' => $numero_version,
                'tipo_cambio' => $tipo_cambio,
                'datos_version' => json_encode($datos_version, JSON_UNESCAPED_UNICODE),
                'descripcion' => $descripcion,
                'id_usuario' => auth()->id(),
                'ip_terminal' => request()->ip(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error creando versión: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Obtener historial de versiones
     * 
     * @param int $id_historico
     * @return array
     */
    public function obtenerVersiones($id_historico)
    {
        try {
            $versiones = VersionCertificado::where('id_historico', $id_historico)
                ->with('usuario')
                ->orderBy('numero_version', 'desc')
                ->get()
                ->map(function ($version) {
                    return [
                        'numero' => $version->numero_version,
                        'tipo_cambio' => $version->tipo_cambio,
                        'descripcion' => $version->descripcion,
                        'fecha' => $version->created_at->format('d/m/Y H:i:s'),
                        'usuario' => $version->usuario->email ?? 'Sistema',
                        'datos' => json_decode($version->datos_version, true),
                    ];
                });

            return [
                'exito' => true,
                'razon' => 'Versiones obtenidas correctamente',
                'versiones' => $versiones,
                'total_versiones' => $versiones->count(),
            ];
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error obteniendo versiones: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al obtener versiones: {$e->getMessage()}",
                'versiones' => [],
                'total_versiones' => 0,
            ];
        }
    }

    /**
     * Descargar certificado archivado
     * 
     * @param int $id_archivo
     * @return file|null
     */
    public function descargarArchivoZip($id_archivo)
    {
        try {
            $archivo = ArchivoCertificado::findOrFail($id_archivo);

            if (!file_exists($archivo->ruta_almacenamiento)) {
                return null;
            }

            // Registrar descarga
            $this->crearVersion(
                $archivo->id_historico,
                'DESCARGA_ARCHIVO',
                "Archivo {$archivo->uuid_archivo} descargado"
            );

            return response()->download($archivo->ruta_almacenamiento, $archivo->nombre_archivo);
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error descargando archivo: {$e->getMessage()}");
            return null;
        }
    }

    /**
     * Generar reporte de archivamiento
     * 
     * @return array
     */
    public function generarReporteArchivamiento()
    {
        try {
            $total_archivos = ArchivoCertificado::where('activo', 1)->count();
            $tamanio_total = ArchivoCertificado::where('activo', 1)
                ->selectRaw('SUM(tamanio_bytes) as total')
                ->value('total') ?? 0;

            // Archivos por período
            $hace_30_dias = Carbon::now()->subDays(30);
            $archivos_mes = ArchivoCertificado::where('created_at', '>=', $hace_30_dias)
                ->count();

            // Certificados versionados
            $total_versiones = VersionCertificado::count();
            $hace_7_dias = Carbon::now()->subDays(7);
            $cambios_semana = VersionCertificado::where('created_at', '>=', $hace_7_dias)
                ->count();

            return [
                'exito' => true,
                'razon' => 'Reporte generado exitosamente',
                'estadisticas' => [
                    'total_archivos_activos' => $total_archivos,
                    'tamanio_total_mb' => round($tamanio_total / (1024 * 1024), 2),
                    'archivos_este_mes' => $archivos_mes,
                    'promedio_tamanio_kb' => $total_archivos > 0 ? round(($tamanio_total / 1024) / $total_archivos, 2) : 0,
                    'total_versiones' => $total_versiones,
                    'cambios_ultima_semana' => $cambios_semana,
                ],
            ];
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error generando reporte: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al generar reporte: {$e->getMessage()}",
                'estadisticas' => [],
            ];
        }
    }

    /**
     * Limpieza de archivos antiguos (política de retención)
     * 
     * @param int $dias
     * @return array [exito, razon, archivos_eliminados, tamanio_liberado]
     */
    public function limpiarArchivosAntiguos($dias = 365)
    {
        try {
            $fecha_limite = Carbon::now()->subDays($dias);

            $archivos_antiguos = ArchivoCertificado::where('created_at', '<', $fecha_limite)
                ->where('activo', 1)
                ->get();

            $total_eliminados = 0;
            $tamanio_liberado = 0;

            foreach ($archivos_antiguos as $archivo) {
                if (file_exists($archivo->ruta_almacenamiento)) {
                    $tamanio_liberado += $archivo->tamanio_bytes;
                    unlink($archivo->ruta_almacenamiento);
                }

                $archivo->update(['activo' => 0, 'fecha_eliminacion' => now()]);
                $total_eliminados++;
            }

            Log::info("[ArchivadoCertificadoService] Limpieza completada: {$total_eliminados} archivos eliminados");

            return [
                'exito' => true,
                'razon' => "Limpieza realizada exitosamente",
                'archivos_eliminados' => $total_eliminados,
                'tamanio_liberado_mb' => round($tamanio_liberado / (1024 * 1024), 2),
            ];
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error limpiando archivos: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al limpiar archivos: {$e->getMessage()}",
                'archivos_eliminados' => 0,
                'tamanio_liberado_mb' => 0,
            ];
        }
    }

    /**
     * Generar backup masivo de certificados
     * 
     * @param array $ids_historico
     * @return array [exito, razon, ruta_backup, cantidad]
     */
    public function generarBackupMasivo($ids_historico = [])
    {
        try {
            // Si no hay IDs específicos, archivar todos los certificados sin archivar
            if (empty($ids_historico)) {
                $ids_historico = HistoricoCierre::whereDoesntHave('archivos', function ($q) {
                    $q->where('activo', 1);
                })
                ->pluck('id_historico')
                ->toArray();
            }

            $ruta_backup_base = storage_path('backups');
            if (!is_dir($ruta_backup_base)) {
                mkdir($ruta_backup_base, 0755, true);
            }

            $fecha_backup = Carbon::now()->format('Y-m-d-His');
            $nombre_backup = "Backup_Certificados_{$fecha_backup}.zip";
            $ruta_backup = "{$ruta_backup_base}/{$nombre_backup}";

            $zip_backup = new ZipArchive();
            if ($zip_backup->open($ruta_backup, ZipArchive::CREATE) !== true) {
                return [
                    'exito' => false,
                    'razon' => 'Error al crear archivo de backup',
                    'ruta_backup' => null,
                    'cantidad' => 0,
                ];
            }

            $cantidad_archivados = 0;
            foreach ($ids_historico as $id_historico) {
                $resultado = $this->archivarCertificado($id_historico);
                if ($resultado['exito'] && file_exists($resultado['ruta_almacen'])) {
                    $desembolso = HistoricoCierre::find($id_historico);
                    $zip_backup->addFile(
                        $resultado['ruta_almacen'],
                        "Certificado_{$desembolso->fk_folio}.zip"
                    );
                    $cantidad_archivados++;
                }
            }

            $zip_backup->close();

            return [
                'exito' => true,
                'razon' => "Backup masivo generado exitosamente",
                'ruta_backup' => $ruta_backup,
                'cantidad' => $cantidad_archivados,
                'nombre_archivo' => $nombre_backup,
            ];
        } catch (\Exception $e) {
            Log::error("[ArchivadoCertificadoService] Error generando backup masivo: {$e->getMessage()}");
            return [
                'exito' => false,
                'razon' => "Error al generar backup: {$e->getMessage()}",
                'ruta_backup' => null,
                'cantidad' => 0,
            ];
        }
    }
}
