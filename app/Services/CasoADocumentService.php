<?php

namespace App\Services;

use App\Models\User;
use App\Models\Solicitudes;
use App\Models\DocumentoExpediente;
use App\Models\ClaveSegumientoPrivada;
use App\Models\CadenaDigitalDocumento;
use App\Models\AuditoriaCargaMaterial;
use App\Models\PoliticaRetencionDocumento;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use DB;
use Carbon\Carbon;

/**
 * CasoADocumentService
 * 
 * Gestiona el flujo completo de Caso A (Carga Híbrida):
 * - MOMENTO 1: Beneficiario carga documentos presencialmente
 * - MOMENTO 2: Admin escanea documentos async
 * - MOMENTO 3: Beneficiario consulta privadamente con clave
 */
class CasoADocumentService
{
    /**
     * MOMENTO 1: Beneficiario presente entrega documentos físicos
     * 
     * @param int $beneficiario_id
     * @param int $solicitud_id
     * @param string $documento_identidad (cédula/pasaporte número)
     * @param array $documentos_listados (tipos de docs que trae)
     * @return array ['folio', 'clave_acceso', 'fecha_entrega']
     * @throws \Exception
     */
    public function crearExpedientePresencial($beneficiario_id, $solicitud_id, $documento_identidad, $documentos_listados)
    {
        DB::beginTransaction();
        
        try {
            // 1. Validaciones
            $beneficiario = Usuarios::findOrFail($beneficiario_id);
            $solicitud = Solicitudes::findOrFail($solicitud_id);
            
            if (!$documento_identidad || strlen($documento_identidad) < 10) {
                throw new \Exception('Documento de identidad inválido');
            }
            
            // 2. Generar folio único
            $folio = $this->generarFolio($beneficiario_id);
            
            // 3. Generar clave privada
            $clave_alfanumerica = $this->generarClavePrivada();
            $hash_clave = hash('sha256', $folio . $clave_alfanumerica . config('app.key'));
            
            // 4. Guardar clave en BD
            $clave = ClaveSegumientoPrivada::create([
                'folio' => $folio,
                'clave_alfanumerica' => $clave_alfanumerica,
                'hash_clave' => $hash_clave,
                'beneficiario_id' => $beneficiario_id,
                'fecha_creacion' => now(),
                'intentos_fallidos' => 0,
                'bloqueada' => false,
            ]);
            
            // 5. Cambiar estado solicitud a EXPEDIENTE_CREADO_PRESENCIAL
            $estadoId = DB::table('Cat_EstadosSolicitud')
                ->where('nombre_estado', 'EXPEDIENTE_PRESENCIAL')
                ->value('id_estado') ?? 6;
                
            $solicitud->update([
                'estado_solicitud' => $estadoId,
                'fecha_cambio_estado' => now(),
            ]);
            
            // 6. Registrar auditoría presencial
            AuditoriaCargaMaterial::create([
                'folio' => $folio,
                'evento' => 'expediente_creado_presencial',
                'admin_id' => auth()->id() ?? 1,
                'cantidad_docs' => count($documentos_listados),
                'fecha_evento' => now(),
                'ip_admin' => request()->ip(),
                'navegador_agente' => request()->header('User-Agent'),
                'detalles_evento' => json_encode([
                    'beneficiario_id' => $beneficiario_id,
                    'documento_identidad' => substr($documento_identidad, -4), // No guardar completo
                    'documentos_listados' => $documentos_listados,
                    'admin_id' => auth()->id(),
                ]),
            ]);
            
            DB::commit();
            
            return [
                'folio' => $folio,
                'clave_acceso' => $clave_alfanumerica,
                'fecha_entrega' => now()->format('Y-m-d H:i:s'),
                'documento_identidad_verificado' => true,
                'documentos_esperados' => count($documentos_listados),
            ];
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * MOMENTO 2: Admin escanea documentos físicos async
     * 
     * @param int $solicitud_id
     * @param int $admin_id
     * @param \Illuminate\Http\UploadedFile $archivo
     * @param string $tipo_documento (CEDULA, COMPROBANTE_DOMICILIO, etc.)
     * @return DocumentoExpediente
     * @throws \Exception
     */
    public function escanearDocumentoPresencial($solicitud_id, $admin_id, $archivo, $tipo_documento)
    {
        DB::beginTransaction();
        
        try {
            // 1. Validaciones
            $this->validarArchivo($archivo);
            
            // 2. Obtener solicitud para folio
            $solicitud = Solicitudes::with('beneficiario')->findOrFail($solicitud_id);
            $folio = $solicitud->folio ?? 'UNKN-' . $solicitud_id;
            
            // 3. Generar hash SHA256 del contenido
            $contenidoArchivo = file_get_contents($archivo->getRealPath());
            $hash_documento = hash('sha256', $contenidoArchivo);
            
            // 4. Obtener hash anterior (si existe documento previo)
            $documentoAnterior = DocumentoExpediente::where('fk_id_solicitud', $solicitud_id)
                ->where('tipo_documento', $tipo_documento)
                ->orderByDesc('id_documento')
                ->first();
            $hash_anterior = $documentoAnterior?->hash_documento;
            
            // 5. Almacenar archivo
            $rutaAlmacenamiento = $this->almacenarArchivo($archivo, $solicitud_id, $folio);
            
            // 6. Generar metadata
            $qr_data = $this->generarQRSeguimiento($folio, $hash_documento, $tipo_documento);
            $firma_admin = hash_hmac('sha256', $hash_documento . $folio, config('app.key'));
            
            // 7. Crear registro DocumentoExpediente
            $documento = DocumentoExpediente::create([
                'fk_id_solicitud' => $solicitud_id,
                'tipo_documento' => $tipo_documento,
                'ruta_archivo' => $rutaAlmacenamiento,
                'origen_carga' => 'admin_escaneo_presencial',
                'cargado_por' => $admin_id,
                'hash_documento' => $hash_documento,
                'hash_anterior' => $hash_anterior,
                'firma_admin' => $firma_admin,
                'qr_seguimiento' => $qr_data,
                'marca_agua_aplicada' => false, // TODO: PDF watermarking
                'fecha_carga' => now(),
            ]);
            
            // 8. Crear entrada en cadena digital
            CadenaDigitalDocumento::create([
                'fk_id_documento' => $documento->id_documento,
                'folio' => $folio,
                'hash_actual' => $hash_documento,
                'hash_anterior' => $hash_anterior,
                'admin_creador' => $admin_id,
                'timestamp_creacion' => now(),
                'firma_hmac' => $firma_admin,
                'razon_cambio' => 'Nuevo documento escaneado en Momento 2',
            ]);
            
            // 9. Registrar auditoría
            AuditoriaCargaMaterial::create([
                'folio' => $folio,
                'evento' => 'documento_escaneado',
                'admin_id' => $admin_id,
                'cantidad_docs' => 1,
                'fecha_evento' => now(),
                'ip_admin' => request()->ip(),
                'navegador_agente' => request()->header('User-Agent'),
                'detalles_evento' => json_encode([
                    'tipo_documento' => $tipo_documento,
                    'hash_documento' => $hash_documento,
                    'nombre_archivo_original' => $archivo->getClientOriginalName(),
                ]),
            ]);
            
            // 10. Crear política de retención
            PoliticaRetencionDocumento::create([
                'fk_id_documento' => $documento->id_documento,
                'folio' => $folio,
                'retencion_cumplida' => false,
            ]);
            
            DB::commit();
            
            return $documento->fresh();
            
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * MOMENTO 3: Beneficiario consulta estado con clave privada
     * 
     * Acciones:
     * - Verificar folio + clave privada contra BD
     * - Validar intentos fallidos (Max 5 intentos, luego bloqueo por 24h)
     * - Si válido: mostrar estado documentos + información de apoyo
     * - Si inválido: contar intento fallido
     * 
     * @param string $folio (SIGO-2026-CASO-A-{id}-{ts})
     * @param string $clave_ingresada (KX7M-9P2W-5LQ8)
     * @return array ['válido' => bool, 'mensaje', 'datos_apoyo' => []|null]
     * @throws \Exception
     */
    public function consultarExpedientePrivado($folio, $clave_ingresada)
    {
        // 1. Buscar folio
        $clave = ClaveSegumientoPrivada::where('folio', $folio)->first();
        
        if (!$clave) {
            return [
                'valido' => false,
                'mensaje' => 'Folio no encontrado. Verifica el número.',
                'datos_apoyo' => null,
            ];
        }
        
        // 2. Validar bloqueo
        if ($clave->bloqueada) {
            return [
                'valido' => false,
                'mensaje' => 'Clave bloqueada. Has excedido 5 intentos. Intenta después de 24 horas.',
                'datos_apoyo' => null,
            ];
        }
        
        // 3. Validar expiración (30 días)
        if ($clave->fecha_ultimo_acceso && $clave->fecha_ultimo_acceso->diffInDays(now()) > 30) {
            $clave->update(['bloqueada' => true]);
            return [
                'valido' => false,
                'mensaje' => 'Clave expirada. Contacta a INJUVE para una nueva.',
                'datos_apoyo' => null,
            ];
        }
        
        // 4. Validar clave
        $hash_ingresada = hash('sha256', $folio . $clave_ingresada . config('app.key'));
        
        if ($hash_ingresada !== $clave->hash_clave) {
            // Incrementar intentos fallidos
            $clave->increment('intentos_fallidos');
            
            if ($clave->intentos_fallidos >= 5) {
                $clave->update(['bloqueada' => true]);
                return [
                    'valido' => false,
                    'mensaje' => 'Clave incorrecta. Has alcanzado 5 intentos. Bloqueada por 24 horas.',
                    'datos_apoyo' => null,
                ];
            }
            
            return [
                'valido' => false,
                'mensaje' => "Clave incorrecta. Intento " . $clave->intentos_fallidos . " de 5.",
                'datos_apoyo' => null,
            ];
        }
        
        // 5. Clave válida - actualizar acceso
        $clave->update([
            'fecha_ultimo_acceso' => now(),
            'intentos_fallidos' => 0,
        ]);
        
        // 6. Obtener datos del apoyo
        $solicitud = Solicitudes::where('beneficiario_id', $clave->beneficiario_id)->first();
        
        $datos_apoyo = [
            'folio' => $folio,
            'nombre_beneficiario' => $clave->beneficiario->nombre ?? 'N/A',
            'apoyo_nombre' => $solicitud?->apoyo?->nombre_apoyo ?? 'Desconocido',
            'apoyo_tipo' => $solicitud?->apoyo?->tipo_apoyo ?? 'N/A',
            'apoyo_monto' => $solicitud?->apoyo?->monto_apoyo ?? 0,
            'estado_solicitud' => $solicitud?->estado_solicitud_nombre ?? 'Pendiente',
            'documentos_cargados' => $solicitud?->documentos()->count() ?? 0,
            'hitos' => $solicitud?->apoyo?->hitos?->map(function ($hito) {
                return [
                    'nombre' => $hito->nombre_hito,
                    'fecha' => $hito->fecha_hito_aproximada?->format('Y-m-d'),
                ];
            })->toArray() ?? [],
        ];
        
        return [
            'valido' => true,
            'mensaje' => 'Acceso verificado correctamente',
            'datos_apoyo' => $datos_apoyo,
        ];
    }

    /**
     * Helper: Generar clave privada de acceso
     * Formato: KX7M-9P2W-5LQ8 (20 caracteres alphanumeric con guiones)
     * 
     * @return string Clave en formato XXXX-XXXX-XXXX-XXXX
     */
    private function generarClavePrivada()
    {
        // Caracteres permitidos: A-Z (mayúscula), 0-9
        $caracteres = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $clave = '';
        
        for ($i = 0; $i < 16; $i++) {
            // 4 segmentos de 4 caracteres, separados por guiones
            if ($i > 0 && $i % 4 == 0) {
                $clave .= '-';
            }
            $clave .= $caracteres[rand(0, strlen($caracteres) - 1)];
        }
        
        return $clave;
    }

    /**
     * Helper: Generar folio único para Caso A
     * Formato: SIGO-2026-CASO-A-{beneficiario_id}-{uniqid}
     * 
     * @param int $beneficiario_id
     * @return string Folio único
     */
    private function generarFolio($beneficiario_id)
    {
        $basefolio = 'SIGO-2026-CASO-A-' . $beneficiario_id . '-' . uniqid();
        
        // Verificar unicidad
        while (ClaveSegumientoPrivada::where('folio', $basefolio)->exists()) {
            $basefolio = 'SIGO-2026-CASO-A-' . $beneficiario_id . '-' . uniqid();
        }
        
        return $basefolio;
    }

    /**
     * Helper: Generar QR de seguimiento
     * 
     * @param string $folio
     * @param string $hash_documento
     * @param string $tipo_documento
     * @return string QR data (URL o data base64)
     */
    private function generarQRSeguimiento($folio, $hash_documento, $tipo_documento)
    {
        // TODO: Implementar con qrcode library
        // Por ahora retornar string que contenga folio + hash
        return base64_encode($folio . '|' . substr($hash_documento, 0, 16));
    }

    /**
     * Helper: Crear entrada en cadena digital
     * Para verificación e inmutabilidad de documentos
     * 
     * @param int $documento_id
     * @param string $hash_nuevo (SHA256 del contenido)
     * @param int $admin_id
     * @param string $razon_cambio
     * @return CadenaDigitalDocumento
     */
    private function crearEntradaCadenaDigital($documento_id, $hash_nuevo, $admin_id, $razon_cambio)
    {
        // TO BE IMPLEMENTED
        // 1. Buscar documento anterior para obtener hash_anterior
        // 2. Generar firma HMAC-SHA256(hash_nuevo + admin_id + timestamp + secret_key)
        // 3. Insertar en cadena_digital_documentos:
        //    - fk_id_documento = documento_id
        //    - hash_actual = hash_nuevo
        //    - hash_anterior = (del documento anterior, o NULL si primer documento)
        //    - admin_creador = admin_id
        //    - firma_hmac = HMAC
        //    - razon_cambio = razon_cambio
        // 4. Retornar entrada creada
    }

    /**
     * Helper: Registrar auditoría de carga material
     * 
     * @param string $folio
     * @param string $evento (CARGA_DOCUMENTO, VERIFICACION, etc.)
     * @param int $admin_id
     * @param int $cantidad_docs
     * @param array $detalles (JSON con información adicional)
     * @return AuditoriaCargaMaterial
     */
    private function registrarAuditoria($folio, $evento, $admin_id, $cantidad_docs, $detalles = [])
    {
        // TO BE IMPLEMENTED
        // 1. Obtener IP del admin: $_SERVER['REMOTE_ADDR']
        // 2. Obtener User-Agent del navegador: $_SERVER['HTTP_USER_AGENT']
        // 3. Insertar en auditorias_carga_material:
        //    - folio = folio
        //    - evento = evento
        //    - admin_id = admin_id
        //    - cantidad_docs = cantidad_docs
        //    - ip_admin = IP
        //    - navegador_agente = User-Agent
        //    - detalles_evento = json_encode($detalles)
        // 4. Retornar entrada creada
    }

    /**
     * Helper: Crear política de retención de documento
     * Se ejecuta cuando el apoyo llega a hito CIERRE
     * 
     * @param int $documento_id
     * @param int $hito_cierre_id
     * @return PoliticaRetencionDocumento
     */
    private function crearPoliticaRetencion($documento_id, $hito_cierre_id)
    {
        // TO BE IMPLEMENTED
        // 1. Buscar hito CIERRE para obtener fecha
        // 2. Insertar en politicas_retencion_documentos:
        //    - fk_id_documento = documento_id
        //    - hito_cierre_apoyo = hito_cierre_id
        //    - fecha_cierre_apoyo = (fecha del hito CIERRE + buffer, ej. 30 días)
        //    - retencion_cumplida = 0 (default, se cumple después)
        // 3. Registrar auditoría
        // 4. Retornar política creada
        //
        // NOTAS:
        // - Luego, Job scheduler: "Cada día a las 00:00, verificar políticas cumplidas"
        // - Si fecha_cierre_apoyo <= DATE(NOW), ejecutar borrado (soft delete o hard delete)
    }

    /**
     * Helper: Aplicar marca de agua a PDF
     * Metadata: Folio, Admin, Fecha, "SIGO - DOCUMENTO VERIFICADO"
     * 
     * @param string $ruta_pdf
     * @param string $folio
     * @param string $admin_nombre
     * @param string $fecha
     * @return string (ruta del PDF con marca de agua)
     */
    private function aplicarMarcaDeAgua($ruta_pdf, $folio, $admin_nombre, $fecha)
    {
        // TO BE IMPLEMENTED
        // OPCIÓN 1: Usar TCPDF / mPDF library para aplicar marca de agua
        // OPCIÓN 2: Usar ImageMagick para PDFs complejos
        // 
        // Metadata a incluir:
        // - "SIGO - DOCUMENTO VERIFICADO"
        // - Folio: {folio}
        // - Verificado por: {admin_nombre}
        // - Fecha: {fecha}
        // - "No alterar. Firmar electrónicamente es delito."
    }

    /**
     * Helper: Almacenar archivo escaneado de forma segura
     * 
     * @param \Illuminate\Http\UploadedFile $archivo
     * @param int $solicitud_id
     * @param string $folio
     * @return string Ruta relativa del archivo almacenado
     */
    private function almacenarArchivo($archivo, $solicitud_id, $folio)
    {
        $ruta = 'documentos/casos-a/' . date('Y/m') . '/' . $solicitud_id;
        $nombreArchivo = $folio . '-' . time() . '.' . $archivo->getClientOriginalExtension();
        
        $archivo->storeAs($ruta, $nombreArchivo, 'local');
        
        return $ruta . '/' . $nombreArchivo;
    }

    /**
     * Helper: Validar integridad y seguridad del archivo
     * 
     * @param \Illuminate\Http\UploadedFile $archivo
     * @return void Lanza excepción si hay error
     * @throws \Exception
     */
    private function validarArchivo($archivo)
    {
        // Validar que existe
        if (!$archivo || !$archivo->isValid()) {
            throw new \Exception('El archivo no es válido o está corrupto');
        }

        // Validar tipo MIME
        $mimeType = $archivo->getMimeType();
        $tiposPermitidos = ['application/pdf', 'image/jpeg', 'image/png', 'image/tiff'];
        
        if (!in_array($mimeType, $tiposPermitidos)) {
            throw new \Exception('Tipo de archivo no permitido. Solo PDF, JPG, PNG o TIFF');
        }

        // Validar tamaño (máx 50MB)
        if ($archivo->getSize() > 50 * 1024 * 1024) {
            throw new \Exception('El archivo supera el tamaño máximo de 50MB');
        }

        // Validar que no es un archivo vacío
        if ($archivo->getSize() == 0) {
            throw new \Exception('El archivo está vacío');
        }
    }
}
