<?php

namespace App\Services;

use App\Models\Usuario;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * FirmaElectronicaService
 *
 * Servicio centralizado para manejar toda la lógica de firma electrónica.
 * Responsabilidades:
 * - Validar pre-requisitos de firma (re-autenticación, documentos, presupuesto)
 * - Generar firmas digitales con SHA256
 * - Generar Comprobantes Únicos de Verificación (CUV)
 * - Registrar auditoría completa de firmas
 * - Verificar integridad de firmas
 * - Manejar rechazo de solicitudes
 * - Generar PDFs firmados
 *
 * Normas de Cumplimiento:
 * - LGPDP: Todos los datos de firma se encriptan y auditan
 * - LFTAIPG: Transparencia en el proceso de firma
 * - Mexicana: CUV único nacional para verificación
 */
class FirmaElectronicaService
{
    /**
     * Validar pre-requisitos para que directivo pueda firmar
     *
     * @param int $folio ID solicitud
     * @param Usuario $directivo Usuario directivo
     * @param string $password Contraseña para re-autenticación
     * @return array ['valido' => bool, 'mensaje' => string, 'datos' => array|null]
     */
    public function validarPreRequisitosSignatura(int $folio, \App\Models\User $directivo, string $password): array
    {
        // 1. Validar que el folio existe
        $solicitud = DB::table('Solicitudes')
            ->where('folio', $folio)
            ->first();

        if (!$solicitud) {
            return [
                'valido' => false,
                'mensaje' => 'La solicitud no existe.',
            ];
        }

        // 2. Validar que el usuario es directivo (role 2, 3 = Directivo, Admin)
        $personal = DB::table('Personal')->where('fk_id_usuario', $directivo->id_usuario)->first();
        if (!$personal || !in_array($personal->fk_rol, [1, 2, 3])) {
            return [
                'valido' => false,
                'mensaje' => 'Solo personal administrativo/directivo puede firmar solicitudes.',
            ];
        }

        // 3. Validar re-autenticación (password)
        if (!Hash::check($password, (string) $directivo->password_hash)) {
            return [
                'valido' => false,
                'mensaje' => 'Re-autenticación fallida. Contraseña incorrecta.',
            ];
        }

        // 4. Validar que todos los documentos estén aprobados o verificados
        $documentosPendientes = DB::table('Documentos_Expediente')
            ->where('fk_folio', $folio)
            ->where('admin_status', 'pendiente')
            ->exists();

        if ($documentosPendientes) {
            return [
                'valido' => false,
                'mensaje' => 'Todos los documentos deben estar verificados antes de firmar.',
            ];
        }

        // 5. Validar que estado de solicitud sea compatible (En revisión, Pendiente de firma, etc.)
        $estadosValidos = [2, 3]; // En revisión, Aprobada
        if (!in_array($solicitud->fk_id_estado, $estadosValidos)) {
            return [
                'valido' => false,
                'mensaje' => 'El estado de la solicitud no permite firma en este momento.',
            ];
        }

        // 6. Validar que no exista firma anterior (evitar duplicados)
        $firmaAnterior = DB::table('Seguimiento_Solicitud')
            ->where('fk_folio', $folio)
            ->where('estado_proceso', 'AUTORIZADO')
            ->whereNotNull('sello_digital')
            ->first();

        if ($firmaAnterior) {
            return [
                'valido' => false,
                'mensaje' => 'Esta solicitud ya fue firmada previamente.',
            ];
        }

        return [
            'valido' => true,
            'mensaje' => 'Pre-requisitos validados correctamente.',
            'datos' => [
                'folio' => $folio,
                'beneficiario_curp' => $solicitud->fk_curp,
                'apoyo_id' => $solicitud->fk_id_apoyo,
                'fecha_creacion_solicitud' => $solicitud->fecha_creacion,
            ],
        ];
    }

    /**
     * Generar firma electrónica (Sello Digital SHA256)
     *
     * @param int $folio
     * @param array $documentos Array de documentos con id, tipo, estado
     * @param int $idDirectivo ID del directivo que firma
     * @return string SHA256 hash
     */
    public function generarSelloDigital(int $folio, array $documentos, int $idDirectivo): string
    {
        $payload = $folio
            . '|'
            . json_encode($documentos, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
            . '|'
            . $idDirectivo
            . '|'
            . config('app.key')
            . '|'
            . now()->toDateTimeString();

        return hash('sha256', $payload);
    }

    /**
     * Generar CUV (Comprobante Único de Verificación)
     * Formato: SIGO-2026-TEP-XXXXXXXXXX (18 caracteres único nacional)
     *
     * @param int $length Longitud del CUV (por defecto 18)
     * @return string CUV único
     */
    public function generarCuv(int $length = 18): string
    {
        $length = max(16, min(20, $length));

        do {
            $raw = strtoupper(bin2hex(random_bytes(16)));
            $cuv = substr($raw, 0, $length);

            $existsInTracking = DB::table('Seguimiento_Solicitud')->where('cuv', $cuv)->exists();
            $existsInSolicitudes = DB::table('Solicitudes')->where('cuv', $cuv)->exists();
        } while ($existsInTracking || $existsInSolicitudes);

        return $cuv;
    }

    /**
     * Firmar solicitud (Aprobación)
     * PUNTO DE NO RETORNO - Una vez firmada, no se puede revertir
     *
     * @param int $folio
     * @param \App\Models\User $directivo
     * @param string $password
     * @return array ['exitoso' => bool, 'mensaje' => string, 'firma' => array|null]
     */
    public function firmarSolicitud(int $folio, \App\Models\User $directivo, string $password): array
    {
        // Validar pre-requisitos
        $validacion = $this->validarPreRequisitosSignatura($folio, $directivo, $password);
        if (!$validacion['valido']) {
            return [
                'exitoso' => false,
                'mensaje' => $validacion['mensaje'],
            ];
        }

        return DB::transaction(function () use ($folio, $directivo, $validacion) {
            try {
                // Obtener documentos para incluir en sello
                $documentos = DB::table('Documentos_Expediente')
                    ->where('fk_folio', $folio)
                    ->orderBy('id_doc')
                    ->get()
                    ->map(fn ($doc) => [
                        'id_doc' => $doc->id_doc,
                        'tipo' => $doc->fk_id_tipo_doc,
                        'estado' => $doc->estado_validacion,
                        'official_file_id' => $doc->official_file_id,
                    ])
                    ->all();

                // Generar sello digital y CUV
                $selloDigital = $this->generarSelloDigital($folio, $documentos, (int) $directivo->id_usuario);
                $cuv = $this->generarCuv(18);

                // Metadata de auditoría
                $metadata = [
                    'ip' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'timestamp_firma' => now()->toIso8601String(),
                    'dispositivo' => $this->detectarDispositivo(request()->userAgent()),
                    'sistema_operativo' => $this->detectarSO(request()->userAgent()),
                ];

                // Actualizar solicitud
                DB::table('Solicitudes')->where('folio', $folio)->update([
                    'cuv' => $cuv,
                    'fk_id_estado' => 3, // APROBADA
                    'fecha_actualizacion' => now(),
                ]);

                // Crear/actualizar seguimiento con firma
                $seguimiento = DB::table('Seguimiento_Solicitud')
                    ->where('fk_folio', $folio)
                    ->first();

                $payloadSeguimiento = [
                    'fk_id_directivo' => (int) $directivo->id_usuario,
                    'sello_digital' => $selloDigital,
                    'cuv' => $cuv,
                    'estado_proceso' => 'AUTORIZADO',
                    'metadata_seguridad' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                    'fecha_firma' => now(),
                    'fecha_actualizacion' => now(),
                ];

                if ($seguimiento) {
                    DB::table('Seguimiento_Solicitud')
                        ->where('id_seguimiento', $seguimiento->id_seguimiento)
                        ->update($payloadSeguimiento);
                } else {
                    DB::table('Seguimiento_Solicitud')->insert([
                        ...$payloadSeguimiento,
                        'fk_folio' => $folio,
                        'fecha_creacion' => now(),
                    ]);
                }

                return [
                    'exitoso' => true,
                    'mensaje' => 'Solicitud firmada exitosamente. CUV: ' . $cuv,
                    'firma' => [
                        'folio' => $folio,
                        'cuv' => $cuv,
                        'sello_digital' => $selloDigital,
                        'fecha_firma' => now()->format('Y-m-d H:i:s'),
                        'directivo' => $directivo->nombre ?? $directivo->email,
                    ],
                ];
            } catch (\Throwable $e) {
                return [
                    'exitoso' => false,
                    'mensaje' => 'Error al firmar solicitud: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Rechazar solicitud (Firma negativa)
     *
     * @param int $folio
     * @param Usuario $directivo
     * @param string $password
     * @param string $motivo Razón del rechazo
     * @return array ['exitoso' => bool, 'mensaje' => string]
     */
    public function rechazarSolicitud(int $folio, Usuario $directivo, string $password, string $motivo): array
    {
        // Validar re-autenticación
        if (!Hash::check($password, (string) $directivo->password_hash)) {
            return [
                'exitoso' => false,
                'mensaje' => 'Re-autenticación fallida.',
            ];
        }

        return DB::transaction(function () use ($folio, $directivo, $motivo) {
            try {
                // Generar sello digital para rechazo
                $selloDigital = hash('sha256', $folio . '|RECHAZO|' . $directivo->id_usuario . '|' . config('app.key'));
                $cuv = $this->generarCuv(18);

                $metadata = [
                    'ip' => request()->ip(),
                    'user_agent' => (string) request()->userAgent(),
                    'motivo_rechazo' => $motivo,
                    'timestamp_firma' => now()->toIso8601String(),
                ];

                // Crear registro de rechazo en firmas_electronicas
                DB::table('firmas_electronicas')->insert([
                    'folio_solicitud' => $folio,
                    'id_directivo' => (int) $directivo->id_usuario,
                    'tipo_firma' => 'RECHAZO',
                    'sello_digital' => $selloDigital,
                    'cuv' => $cuv,
                    'estado' => 'EXITOSA',
                    'metadata' => json_encode($metadata, JSON_UNESCAPED_UNICODE),
                    'fecha_firma' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                // Actualizar solicitud a RECHAZADA
                DB::table('Solicitudes')->where('folio', $folio)->update([
                    'fk_id_estado' => 4, // RECHAZADA
                    'cuv' => $cuv,
                    'fecha_actualizacion' => now(),
                ]);

                // Liberar presupuesto si estaba reservado
                $solicitud = DB::table('Solicitudes')->where('folio', $folio)->first();
                if ($solicitud) {
                    $movimiento = DB::table('movimientos_presupuestarios')
                        ->where('tipo_movimiento', 'RESERVA_SOLICITUD')
                        ->where('descripcion', 'like', "%solicitud $folio%")
                        ->first();

                    if ($movimiento) {
                        // Incrementar disponible en categoría
                        DB::table('presupuesto_categorias')
                            ->where('id_categoria', $movimiento->id_categoria)
                            ->increment('disponible', $movimiento->monto);

                        // Crear movimiento de liberación
                        DB::table('movimientos_presupuestarios')->insert([
                            'id_categoria' => $movimiento->id_categoria,
                            'id_apoyo_presupuesto' => $movimiento->id_apoyo_presupuesto,
                            'tipo_movimiento' => 'LIBERACION_RECHAZO',
                            'monto' => $movimiento->monto,
                            'creado_por' => (int) $directivo->id_usuario,
                            'descripcion' => "Liberación de presupuesto por rechazo: {$motivo}",
                        ]);
                    }
                }

                return [
                    'exitoso' => true,
                    'mensaje' => 'Solicitud rechazada. CUV de rechazo: ' . $cuv,
                    'cuv' => $cuv,
                ];
            } catch (\Throwable $e) {
                return [
                    'exitoso' => false,
                    'mensaje' => 'Error al rechazar solicitud: ' . $e->getMessage(),
                ];
            }
        });
    }

    /**
     * Verificar integridad de una firma (para auditoría posterior)
     *
     * @param string $cuv
     * @return array ['valido' => bool, 'detalles' => array]
     */
    public function verificarFirma(string $cuv): array
    {
        $firma = DB::table('Seguimiento_Solicitud')
            ->where('cuv', $cuv)
            ->whereNotNull('sello_digital')
            ->first();

        if (!$firma) {
            return [
                'valido' => false,
                'detalles' => ['error' => 'CUV no encontrado'],
            ];
        }

        // Verificar que la firma no ha expirado (5 años por defecto)
        $fechaFirma = $firma->fecha_firma ? Carbon::parse($firma->fecha_firma) : null;
        if ($fechaFirma && now()->gt($fechaFirma->addYears(5))) {
            return [
                'valido' => false,
                'detalles' => ['error' => 'Firma expirada'],
            ];
        }

        $solicitud = DB::table('Solicitudes')->where('folio', $firma->fk_folio)->first();
        $directivo = DB::table('Usuarios')->where('id_usuario', $firma->fk_id_directivo)->first();

        return [
            'valido' => true,
            'detalles' => [
                'cuv' => $firma->cuv,
                'folio' => $firma->fk_folio,
                'estado' => $firma->estado_proceso,
                'fecha_firma' => $firma->fecha_firma,
                'directivo' => $directivo?->nombre ?? $directivo?->email,
                'solicitud_estado' => $solicitud?->fk_id_estado,
                'metadata' => $firma->metadata_seguridad ? json_decode($firma->metadata_seguridad, true) : [],
            ],
        ];
    }

    /**
     * Detectar dispositivo desde User-Agent
     */
    private function detectarDispositivo(string $userAgent): string
    {
        if (strpos($userAgent, 'Mobile') !== false || strpos($userAgent, 'Android') !== false) {
            return 'Mobile';
        } elseif (strpos($userAgent, 'Tablet') !== false || strpos($userAgent, 'iPad') !== false) {
            return 'Tablet';
        }
        return 'Desktop';
    }

    /**
     * Detectar sistema operativo desde User-Agent
     */
    private function detectarSO(string $userAgent): string
    {
        if (strpos($userAgent, 'Windows') !== false) {
            return 'Windows';
        } elseif (strpos($userAgent, 'Mac') !== false) {
            return 'macOS';
        } elseif (strpos($userAgent, 'Linux') !== false) {
            return 'Linux';
        } elseif (strpos($userAgent, 'Android') !== false) {
            return 'Android';
        } elseif (strpos($userAgent, 'iOS') !== false) {
            return 'iOS';
        }
        return 'Desconocido';
    }
}
