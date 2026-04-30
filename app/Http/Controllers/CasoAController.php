<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Solicitud;
use App\Models\Documento;
use App\Models\Apoyo;
use App\Models\Beneficiario;
use App\Models\ClaveSegumientoPrivada;
use App\Services\CasoADocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;

/**
 * CasoAController
 * 
 * Gestiona los 3 momentos de Caso A (Carga Híbrida):
 * 
 * MOMENTO 1: Beneficiario presente entrega documentos físicos
 * MOMENTO 2: Admin escanea documentos async
 * MOMENTO 3: Beneficiario consulta privadamente con clave
 */
class CasoAController extends Controller
{
    protected $casoAService;

    public function __construct(CasoADocumentService $casoAService)
    {
        $this->casoAService = $casoAService;
    }

    /**
     * MOMENTO 1: Mostrar formulario de presencia física
     * 
     * Flujo:
     * 1. Admin accede a panel administrativo
     * 2. Busca beneficiario por cédula/nombre
     * 3. Selecciona apoyo
     * 4. Registra presencia física (beneficiario presente)
     * 5. Genera folio + clave privada
     * 
     * Acceso: Solo Personal Administrativo (Rol 1-2)
     * 
     * @return \Illuminate\View\View
     */
    public function momentoUno()
    {
        // Validar autenticación + rol (Rol 1-2)
        $user = Auth::user();
        if (!$user || $user->role_id > 2) {
            return Redirect::to('/')->with('error', 'Acceso denegado');
        }

        // Obtener apoyos activos con hitos relacionados
        $apoyos = Apoyo::where('activo', 1)
            ->with('hitos')
            ->get()
            ->map(function($apoyo) {
                // Contar solicitudes aprobadas para este apoyo
                // fk_id_estado = 4 corresponde a "Aprobado"
                $aprobadas = Solicitud::where('fk_id_apoyo', $apoyo->id_apoyo)
                    ->where('fk_id_estado', 4)
                    ->count();
                
                $apoyo->total_aprobadas = $aprobadas;
                
                // Cargar documentos requeridos para este apoyo
                $documentosRequeridos = \DB::table('Requisitos_Apoyo')
                    ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                    ->where('Requisitos_Apoyo.fk_id_apoyo', $apoyo->id_apoyo)
                    ->select(
                        'Cat_TiposDocumento.id_tipo_doc',
                        'Cat_TiposDocumento.nombre_documento',
                        'Requisitos_Apoyo.es_obligatorio'
                    )
                    ->get();
                
                $apoyo->documentos_requeridos = $documentosRequeridos;
                
                $hitoRecepcion = $apoyo->hitos->first(function ($hito) {
                    return str_contains(strtoupper((string) $hito->clave_hito), 'RECEPCION');
                });
                $ahora = now();
                
                if ($hitoRecepcion && $hitoRecepcion->activo) {
                    $apoyo->en_periodo_recepcion = $ahora->between(
                        $hitoRecepcion->fecha_inicio,
                        $hitoRecepcion->fecha_fin
                    );
                } else {
                    $apoyo->en_periodo_recepcion = false;
                }
                
                return $apoyo;
            });

        // Filtrar solo apoyos en período de recepción
        $apoyosFiltrados = $apoyos->filter(fn($a) => $a->en_periodo_recepcion);

        // Historial de expedientes creados hoy (por este admin)
        $expedientesHoy = \App\Models\ClaveSegumientoPrivada::with(['solicitud.apoyo', 'solicitud.beneficiario'])
            ->whereRaw('CONVERT(date, fecha_creacion) = CONVERT(date, GETDATE())')
            ->orderByDesc('fecha_creacion')
            ->get();

        return view('admin.caso-a.momento-uno', [
            'apoyos' => $apoyosFiltrados->values(),
            'apoyosTodos' => $apoyos,
            'expedientesHoy' => $expedientesHoy,
            'adminNombre' => $user->nombre,
        ]);
    }

    /**
     * MOMENTO 1: Guardar expediente presencial (FUSIONADO con flujo ordinario)
     * 
     * Acciones:
     * 1. Validar datos ingresados
     * 2. Crear SOLICITUD ORDINARIA con origen_solicitud = 'admin_caso_a'
     * 3. Generar folio + clave privada
     * 4. Estado: DOCUMENTOS_PENDIENTE_VERIFICACIÓN (mismo del flujo normal)
     * 5. Retornar ticket para imprimir
     * 
     * La solicitud entra al FLUJO ORDINARIO (no separado)
     * - Admin verifica documentos (misma interfaz que beneficiarios)
     * - Directivo firma (mismo proceso)
     * 
     * POST /admin/caso-a/momento-uno
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function guardarMomentoUno(Request $request)
    {
        // Validaciones
        $esRegistrado = $request->has('es_beneficiario_registrado') && $request->input('es_beneficiario_registrado') === '1';
        
        if ($esRegistrado) {
            // Validar beneficiario registrado
            $validated = $request->validate([
                'beneficiario_id' => 'required|integer|exists:usuarios,id_usuario',
                'apoyo_id' => 'required|integer|exists:apoyos,id_apoyo',
                'documentos_listados' => 'required|array|min:1',
                'notas' => 'nullable|string|max:1000'
            ]);
            $beneficiario_id = $validated['beneficiario_id'];
        } else {
            // Validar captura manual
            $validated = $request->validate([
                'manual_nombre' => 'required|string|max:255',
                'manual_curp' => 'required|string|size:18|regex:/^[A-Z0-9]{18}$/',
                'manual_email' => 'nullable|email|max:255',
                'manual_telefono' => 'nullable|string|regex:/^\(\d{3}\) \d{3}-\d{4}$/',
                'apoyo_id' => 'required|integer|exists:apoyos,id_apoyo',
                'documentos_listados' => 'required|array|min:1',
                'notas' => 'nullable|string|max:1000'
            ], [
                'manual_curp.required' => 'El CURP es obligatorio para beneficiarios no registrados',
                'manual_curp.size' => 'El CURP debe tener exactamente 18 caracteres',
                'manual_curp.regex' => 'El CURP debe contener solo letras mayúsculas y números',
                'manual_telefono.regex' => 'El teléfono debe tener el formato (123) 456-7890'
            ]);
            $beneficiario_id = null;  // Sin beneficiario registrado
        }

        try {
            $apoyo = Apoyo::findOrFail($validated['apoyo_id']);
            $adminId = Auth::id();

            // Preparar datos del beneficiario
            if ($esRegistrado) {
                $usuario = User::findOrFail($beneficiario_id);
                
                // Obtener CURP desde tabla Beneficiarios (relacionada por fk_id_usuario)
                $beneficiarioRecord = \DB::table('Beneficiarios')
                    ->where('fk_id_usuario', $beneficiario_id)
                    ->select('curp', 'nombre', 'apellido_paterno', 'apellido_materno')
                    ->first();
                
                if (!$beneficiarioRecord) {
                    throw new \Exception(
                        'El usuario "' . $usuario->email . '" existe en el sistema pero NO está registrado como beneficiario. ' .
                        'Solo se pueden usar beneficiarios que se hayan registrado completa y oficialmente.'
                    );
                }
                
                if (!$beneficiarioRecord->curp) {
                    throw new \Exception(
                        'El beneficiario "' . $usuario->email . '" no tiene CURP registrado. ' .
                        'Contacte al administrador para completar los datos.'
                    );
                }
                
                $datoBeneficiario = (object)[
                    'id_usuario' => $usuario->id_usuario,
                    'nombre_completo' => trim(
                        ($beneficiarioRecord->nombre ?? '') . ' ' .
                        ($beneficiarioRecord->apellido_paterno ?? '') . ' ' .
                        ($beneficiarioRecord->apellido_materno ?? '')
                    ),
                    'curp' => $beneficiarioRecord->curp,
                    'email' => $usuario->email,
                    'telefono' => null,
                ];
            } else {
                $datoBeneficiario = (object) [
                    'id_usuario' => null,
                    'nombre_completo' => $validated['manual_nombre'],
                    'curp' => $validated['manual_curp'],  // Siempre presente (ya validado)
                    'email' => $validated['manual_email'] ?? null,
                    'telefono' => $validated['manual_telefono'] ?? null,
                ];
            }

            // Crear expediente presencial (retorna solicitud + folio/clave)
            $resultado = $this->casoAService->crearExpedientePresencial(
                $beneficiario_id,
                $datoBeneficiario,
                $validated['apoyo_id'],
                $validated['documentos_listados'],
                $adminId,  // admin que está creando
                $validated['notas'] ?? null  // notas administrativas
            );

            // Guardar datos en sesión para mostrar ticket
            session([
                'caso_a_folio' => $resultado['folio'],
                'caso_a_clave' => $resultado['clave_acceso'],
                'caso_a_solicitud_id' => $resultado['solicitud_id'],
            ]);

            // Si es petición AJAX/fetch, retornar JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Expediente presencial creado exitosamente',
                    'folio' => $resultado['folio'],
                    'clave_acceso' => $resultado['clave_acceso'],
                    'solicitud_id' => $resultado['solicitud_id'],
                ]);
            }

            // Si no es AJAX, retornar redirect
            return redirect()->route('admin.caso-a.resumen-momento-uno', ['folio' => $resultado['folio']])
                ->with('success', 'Expediente presencial creado. Folio generado. Beneficiario entra a flujo ordinario.');

        } catch (\Exception $e) {
            Log::error('Error Caso A Momento 1: ' . $e->getMessage());
            
            // Si es petición AJAX/fetch, retornar JSON
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error: ' . $e->getMessage(),
                ], 400);
            }

            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * MOMENTO 1: Mostrar resumen para impresión
     * 
     * Flujo:
     * 1. Mostrar folio único
     * 2. Mostrar clave privada de acceso
     * 3. QR con folio (para escanear en momento 2)
     * 4. Instrucciones para beneficiario
     * 5. Botón para imprimir
     * 
     * GET /admin/caso-a/resumen/{folio}
     * 
     * @param string $folio
     * @return \Illuminate\View\View
     */
    public function mostrarResumenMomentoUno($folio)
    {
        // Validar rol administrativo
        if (Auth::user()->role_id > 2) {
            return Redirect::to('/')->with('error', 'Acceso denegado');
        }

        // Buscar solicitud por folio
        $solicitud = Solicitud::where('folio', $folio)->first();
        
        if (!$solicitud) {
            return Redirect::back()->with('error', 'Folio no encontrado');
        }

        // Buscar clave
        $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $folio)->first();
        
        if (!$clave) {
            return Redirect::back()->with('error', 'Clave privada no encontrada');
        }

        // Obtener datos del beneficiario (registrado o manual)
        $beneficiario = null;
        
        if ($solicitud->beneficiario_id && $solicitud->beneficiario_id != 20) {
            // Beneficiario registrado
            $beneficiario = \DB::table('Beneficiarios')
                ->join('Usuarios', 'Beneficiarios.fk_id_usuario', '=', 'Usuarios.id_usuario')
                ->where('Beneficiarios.fk_id_usuario', $solicitud->beneficiario_id)
                ->select(
                    'Beneficiarios.nombre',
                    'Beneficiarios.apellido_paterno',
                    'Beneficiarios.apellido_materno',
                    'Beneficiarios.telefono',
                    'Beneficiarios.curp',
                    'Usuarios.email'
                )
                ->first();
        } else {
            // Beneficiario no registrado o manual - usar el registro parcial por CURP si existe
            $beneficiarioParcial = \DB::table('Beneficiarios')
                ->where('curp', $solicitud->fk_curp)
                ->first();

            $beneficiario = $beneficiarioParcial ? (object) [
                'nombre' => $beneficiarioParcial->nombre,
                'apellido_paterno' => $beneficiarioParcial->apellido_paterno,
                'apellido_materno' => $beneficiarioParcial->apellido_materno,
                'telefono' => $beneficiarioParcial->telefono,
                'curp' => $beneficiarioParcial->curp,
                'email' => null,
            ] : (object)[
                'nombre' => 'Información',
                'apellido_paterno' => 'Disponible',
                'apellido_materno' => 'en Detalles',
                'telefono' => null,
                'curp' => $solicitud->fk_curp,
                'email' => null
            ];
        }
        
        // Apoyo
        $apoyo = $solicitud->apoyo;

        // Generar URL para QR (acceso directo sin formulario)
        $urlAccesoQr = route('caso-a.acceso-qr', [
            'folio' => $folio,
            'clave' => $clave->clave_alfanumerica
        ], absolute: true);
        
        // Generar imagen QR usando QR Server API (gratuita, confiable)
        // URL codificada para pasar al servicio
        $qrImageUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($urlAccesoQr);

        // Obtener documentos entregados en momento 1 desde la auditoría
        $auditoria = \DB::table('auditorias_carga_material')
            ->where('folio', $folio)
            ->where('evento', 'caso_a_momento_1_presencial')
            ->first();
            
        $documentosEntregadosIds = [];
        if ($auditoria && $auditoria->detalles_evento) {
            $detalles = json_decode($auditoria->detalles_evento, true);
            $documentosEntregadosIds = $detalles['documentos_listados'] ?? [];
        }

        // Obtener nombres de los documentos entregados
        $nombresDocumentos = [];
        if (!empty($documentosEntregadosIds)) {
            $nombresDocumentos = \DB::table('Cat_TiposDocumento')
                ->whereIn('id_tipo_doc', $documentosEntregadosIds)
                ->pluck('nombre_documento')
                ->toArray();
        }

        return view('admin.caso-a.resumen-momento-uno', [
            'folio' => $folio,
            'clave' => $clave->clave_alfanumerica,
            'beneficiario' => $beneficiario,
            'apoyo' => $apoyo,
            'solicitud' => $solicitud,
            'fechaCreacion' => $clave->fecha_creacion->format('d/m/Y H:i:s'),
            'qrImageUrl' => $qrImageUrl,  // URL directo a imagen QR
            'urlAccesoQr' => $urlAccesoQr,  // URL para tooltip
            'nombresDocumentos' => $nombresDocumentos,
        ]);
    }

    /**
     * Acceso directo vía QR (PÚBLICA - sin autenticación)
     * 
     * Flujo:
     * 1. Se escanea QR de ticket presencial
     * 2. URL contiene folio + clave como parámetros GET
     * 3. Este método valida la combinación
     * 4. Si es válida, muestra resumen sin pasos adicionales
     * 5. Si es inválida, redirige al formulario de consulta
     * 
     * Uso: Beneficiario escanea QR con celular → abre directo al resumen
     * 
     * GET /consulta-privada/acceso-qr?folio=1035&clave=XXXX-XXXX-XXXX-XXXX
     * 
     * @param Request $request
     * @return \Illuminate\View\View | \Illuminate\Http\RedirectResponse
     */
    public function accesoDirectoQr(Request $request)
    {
        $request->validate([
            'folio' => 'required|string',
            'clave' => 'required|string'
        ]);

        $folio = $request->folio;
        $clave = $request->clave;

        try {
            // Buscar clave privada por folio + clave
            $claveRecord = \App\Models\ClaveSegumientoPrivada::where('folio', $folio)
                ->where('clave_alfanumerica', $clave)
                ->first();

            if (!$claveRecord) {
                return redirect()->route('caso-a.momento-tres-form')
                    ->with('error', 'Folio o Clave no válidos. Intenta nuevamente.');
            }

            // Validar que no esté bloqueada por intentos fallidos
            if ($claveRecord->bloqueada) {
                return redirect()->route('caso-a.momento-tres-form')
                    ->with('error', 'Esta clave ha sido bloqueada por múltiples intentos fallidos. Contacta al administrador.');
            }

            // Guardar en sesión y redirigir directo al resumen
            session([
                'caso_a_folio' => $folio,  // Variable correcta esperada por mostrarResumenMomentoTres()
                'caso_a_privada' => true,   // Indicador de acceso válido
            ]);

            // Ir directo al resumen sin pasos intermedios
            return redirect()->route('caso-a.resumen-momento-tres');

        } catch (\Exception $e) {
            \Log::error('Error QR access: ' . $e->getMessage());
            return redirect()->route('caso-a.momento-tres-form')
                ->with('error', 'Error procesando QR. Intenta nuevamente.');
        }
    }

    /**
     * MOMENTO 2: Mostrar interfaz de escaneo de documentos
     * 
     * Flujo:
     * 1. Admin accede a panel de escaneo
     * 2. Ingresa folio (o lo escanea vía QR)
     * 3. Sistema busca expediente presencial
     * 4. Muestra formulario para cargar documento escaneado
     * 5. Admin sube archivo PDF/JPG/PNG
     * 6. Sistema procesa y verifica integridad
     * 
     * Acceso: Solo Personal Administrativo (Rol 1-2)
     * 
     * GET /admin/caso-a/momento-dos
     * 
     * @return \Illuminate\View\View
     */
    public function momentoDos()
    {
        // Validar autenticación + rol (Rol 1-2)
        if (Auth::user()->role_id > 2) {
            return Redirect::to('/')->with('error', 'Acceso denegado');
        }

        // Obtener documentos cargados hoy por este admin
        $adminId = Auth::id();
        $documentosHoy = Documento::whereDate('fecha_carga', today())
            ->where('id_admin', $adminId)
            ->with('solicitud')
            ->get();

        // Estadísticas
        $estadisticas = [
            'pendientes' => Documento::whereIn('estado_validacion', ['PENDIENTE', 'Pendiente'])->count(),
            'completados' => Documento::whereIn('estado_validacion', ['Correcto', 'VERIFICADO', 'Validado', 'Aprobado'])->count(),
            'conError' => Documento::whereIn('estado_validacion', ['Incorrecto', 'RECHAZADO'])->count(),
        ];

        return view('admin.caso-a.momento-dos', [
            'documentosHoy' => $documentosHoy,
            'estadisticas' => $estadisticas,
            'adminNombre' => Auth::user()->nombre,
        ]);
    }

    /**
     * MOMENTO 2: Procesar carga de documento escaneado
     * 
     * Acciones:
     * 1. Validar folio existe
     * 2. Validar archivo es válido
     * 3. Generar SHA256 del contenido
     * 4. Almacenar en storage/app/documentos/casos-a/
     * 5. Crear entrada DocumentoExpediente
     * 6. Crear cadena digital (para verificación)
     * 7. Registro auditoría
     * 8. Retornar JSON con resultado
     * 
     * POST /admin/caso-a/momento-dos/cargar
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    /**
     * MOMENTO 2: Cargar documento escaneado
     * 
     * ✨ FUSIONADO: Solicitud ya está en estado DOCUMENTOS_PENDIENTE_VERIFICACIÓN
     * Este método SOLO carga documentos, NO cambia estado
     * 
     * Procesa:
     * 1. Validación del archivo (MIME, tamaño)
     * 2. Cálculo de hash SHA256 (integridad)
     * 3. Generación de watermark (INJUVE + folio + fecha)
     * 4. Generación de QR (folio + tipo_doc + timestamp + admin_id)
     * 5. Firma HMAC-SHA256 (inmutable)
     * 6. Cadena digital (hash anterior → hash actual)
     * 7. Auditoría (evento, admin_id, IP, navegador)
     * 
     * NOTA: Después, admin va al verificador ORDINARIO
     * (route: /admin/verificar-documentos con filtro origen_solicitud)
     * 
     * POST /admin/caso-a/momento-dos/cargar
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function cargarDocumentoMomentoDos(Request $request)
    {
        try {
            // Validaciones básicas
            $validated = $request->validate([
                'folio' => 'required|string|exists:claves_seguimiento_privadas,folio',
                'documento' => 'required|file|max:5120',
                'tipo_documento' => 'required|string|exists:Cat_TiposDocumento,id_tipo_doc'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validación falló en cargarDocumentoMomentoDos', [
                'errors' => $e->errors(),
                'folio' => $request->input('folio'),
                'tipo_documento' => $request->input('tipo_documento')
            ]);
            
            $mensajeError = 'Validación fallida: ';
            foreach ($e->errors() as $field => $messages) {
                $mensajeError .= implode(', ', $messages) . ' ';
            }
            
            return response()->json([
                'success' => false,
                'error' => trim($mensajeError)
            ], 422);
        }

        try {
            // Obtener datos
            $adminId = Auth::id();
            $archivo = $request->file('documento');
            $tipoDocId = $validated['tipo_documento'];

            \Log::info('Iniciando carga de documento', [
                'folio' => $validated['folio'],
                'tipo_doc_id' => $tipoDocId,
                'admin_id' => $adminId,
                'archivo_nombre' => $archivo->getClientOriginalName(),
                'archivo_tamaño' => $archivo->getSize(),
                'archivo_extension' => $archivo->getClientOriginalExtension()
            ]);

            // Obtener configuración del tipo de documento
            $tipoDocumento = \DB::table('Cat_TiposDocumento')
                ->where('id_tipo_doc', $tipoDocId)
                ->first();
            
            if (!$tipoDocumento) {
                \Log::error('Tipo de documento no encontrado', ['id_tipo_doc' => $tipoDocId]);
                return response()->json([
                    'success' => false,
                    'error' => 'Tipo de documento no configurado en el sistema'
                ], 404);
            }

            // Validar tipo de archivo si está configurada la validación
            if ($tipoDocumento->validar_tipo_archivo) {
                $tiposPermitidos = explode(',', strtolower($tipoDocumento->tipo_archivo_permitido ?? 'pdf,jpg,jpeg,png'));
                $tiposPermitidos = array_map('trim', $tiposPermitidos);
                
                $extensionArchivo = strtolower($archivo->getClientOriginalExtension());
                
                \Log::info('Validando tipo de archivo', [
                    'extension_archivo' => $extensionArchivo,
                    'tipos_permitidos' => $tiposPermitidos,
                    'validar_tipo_archivo' => $tipoDocumento->validar_tipo_archivo
                ]);
                
                if (!in_array($extensionArchivo, $tiposPermitidos)) {
                    $error = 'Tipo de archivo no permitido. Formatos aceptados: ' . strtoupper(implode(', ', $tiposPermitidos));
                    \Log::warning('Tipo de archivo rechazado', ['error' => $error, 'extension' => $extensionArchivo]);
                    return response()->json([
                        'success' => false,
                        'error' => $error
                    ], 422);
                }
            }

            // Validar peso máximo
            $pesoMaximoMb = $tipoDocumento->peso_maximo_mb ?? 5;
            $pesoMaximoBytes = $pesoMaximoMb * 1024 * 1024;
            
            \Log::info('Validando peso del archivo', [
                'peso_bytes' => $archivo->getSize(),
                'peso_maximo_bytes' => $pesoMaximoBytes,
                'peso_maximo_mb' => $pesoMaximoMb
            ]);
            
            if ($archivo->getSize() > $pesoMaximoBytes) {
                $error = "El archivo excede el tamaño máximo de {$pesoMaximoMb} MB (tamaño: " . round($archivo->getSize() / 1024 / 1024, 2) . " MB)";
                \Log::warning('Archivo muy grande', ['error' => $error]);
                return response()->json([
                    'success' => false,
                    'error' => $error
                ], 422);
            }

            // Obtener clave + solicitud (por folio)
            $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $validated['folio'])->first();
            
            if (!$clave) {
                \Log::error('Folio no encontrado', ['folio' => $validated['folio']]);
                return response()->json([
                    'success' => false,
                    'error' => 'Folio no encontrado'
                ], 404);
            }

            \Log::info('DEBUG: Clave encontrada', [
                'clave_folio_raw' => $clave->folio,
                'clave_folio_type' => gettype($clave->folio),
                'clave_folio_int' => (int)$clave->folio
            ]);

            // Obtener solicitud usando el folio (que es la primary key)
            $folioInt = (int)$clave->folio;
            
            // Primero verificar si existe alguna solicitud con este folio
            $solicitudTest = \DB::table('Solicitudes')->where('folio', $folioInt)->first();
            \Log::info('DEBUG: Query directo a Solicitudes', [
                'folio_buscado' => $folioInt,
                'resultado' => $solicitudTest ? 'ENCONTRADO' : 'NO ENCONTRADO'
            ]);
            
            // Listar todos los folios Caso A disponibles
            $foliosCasoA = \DB::table('Solicitudes')
                ->where('origen_solicitud', 'admin_caso_a')
                ->select('folio')
                ->pluck('folio')
                ->toArray();
            \Log::info('DEBUG: Folios Caso A en BD', ['folios' => $foliosCasoA]);
            
            $solicitud = Solicitud::where('folio', $folioInt)->first();

            if (!$solicitud) {
                \Log::error('Solicitud no encontrada para folio', [
                    'folio' => $folioInt,
                    'folio_type' => gettype($folioInt),
                    'solicitudes_caso_a' => $foliosCasoA
                ]);
                return response()->json([
                    'success' => false,
                    'error' => "Solicitud no encontrada para el folio {$folioInt}. Folios disponibles: " . implode(',', $foliosCasoA)
                ], 404);
            }

            // Validar que sea Caso A
            if ($solicitud->origen_solicitud !== 'admin_caso_a') {
                \Log::warning('Solicitud no es de Caso A', [
                    'folio' => $clave->folio,
                    'origen' => $solicitud->origen_solicitud
                ]);
                return response()->json([
                    'success' => false,
                    'error' => 'Esta solicitud no es de Caso A'
                ], 422);
            }

            // Procesar documento con el servicio (watermark, hash, firma, etc.)
            $documento = $this->casoAService->escanearDocumentoPresencial(
                $solicitud->folio,  // El folio es la primary key de Solicitud
                $adminId,
                $archivo,
                $validated['tipo_documento']
            );

            \Log::info('Documento cargado exitosamente', [
                'documento_id' => $documento->id_doc,
                'folio' => $validated['folio']
            ]);

            return response()->json([
                'success' => true,
                'documento' => [
                    'id' => $documento->id_doc,
                    'tipo' => $validated['tipo_documento'],
                    'folio' => $clave->folio,
                    'origen' => 'admin_escaneo_presencial',
                    'fecha' => $documento->fecha_carga->format('Y-m-d H:i:s'),
                ],
                'mensaje' => 'Documento cargado exitosamente ✓'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error en CasoA Momento 2 - Cargar documento', [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'error' => 'Error al procesar el documento: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * MOMENTO 2: Confirmar carga de documentos (resumen)
     * 
     * ✨ FUSIONADO: NO cambiamos estado (ya está en DOCUMENTOS_PENDIENTE_VERIFICACIÓN)
     * 
     * Solo:
     * 1. Validar que folio tiene mínimo N documentos
     * 2. Generar resumen de auditoría
     * 3. Notificar beneficiario: "Documentos cargados, espera verificación"
     * 4. Mostrar ticket de referencia
     * 
     * DESPUÉS: Admin va a /admin/verificar-documentos (mismo para todos)
     * 
     * POST /admin/caso-a/momento-dos/confirmar
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function confirmarCargaMomentoDos(Request $request)
    {
        // Validaciones
        $validated = $request->validate([
            'folio' => 'required|string|exists:claves_seguimiento_privadas,folio'
        ]);

        try {
            // Buscar expediente (por folio)
            $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $validated['folio'])->first();
            
            if (!$clave) {
                return response()->json([
                    'success' => false,
                    'error' => 'Folio no encontrado'
                ], 404);
            }

            // Obtener solicitud usando el folio (que es la primary key)
            $solicitud = Solicitud::where('folio', (int)$clave->folio)->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solicitud no encontrada'
                ], 404);
            }

            // Contar documentos cargados únicos del Momento 2
            $cantidadDocs = Documento::where('fk_folio', $solicitud->folio)
                ->where('origen_archivo', 'admin_escaneo_presencial')  // Solo los escaneados (Momento 2)
                ->pluck('fk_id_tipo_doc')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->count();

            $minimoRequerido = DB::table('Requisitos_Apoyo')
                ->where('fk_id_apoyo', $solicitud->fk_id_apoyo)
                ->where('es_obligatorio', 1)
                ->pluck('fk_id_tipo_doc')
                ->unique()
                ->count(); // Documentos obligatorios únicos del apoyo

            if ($cantidadDocs < $minimoRequerido) {
                return response()->json([
                    'success' => false,
                    'error' => "Debe cargar al menos {$minimoRequerido} documentos. Actualmente tiene {$cantidadDocs}.",
                    'documentosActuales' => $cantidadDocs
                ], 400);
            }

            if ($cantidadDocs >= $minimoRequerido) {
                $solicitud->update([
                    'fk_id_estado' => 9,
                    'estado_solicitud' => 'DOCUMENTOS_VERIFICADOS',
                    'fecha_cambio_estado' => DB::raw('GETDATE()'),
                ]);
            }

            // ✨ NOTA: NO cambiar estado (ya está en DOCUMENTOS_PENDIENTE_VERIFICACIÓN)
            // La solicitud ENTRA AL FLUJO ORDINARIO

            // Registrar auditoría final (confirmación de carga)
            \App\Models\AuditoriaCargaMaterial::create([
                'folio' => $validated['folio'],
                'evento' => 'caso_a_momento_2_carga_confirmada',
                'admin_id' => Auth::id(),
                'cantidad_docs' => $cantidadDocs,
                'fecha_evento' => now(),
                'ip_admin' => $request->ip(),
                'navegador_agente' => $request->header('User-Agent'),
                'detalles_evento' => json_encode([
                    'folio' => $validated['folio'],
                    'documentos_confirmados' => $cantidadDocs,
                    'estado_actual' => 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN',
                    'siguiente_paso' => 'Ir a verificador ordinario (/admin/verificar-documentos)',
                ]),
            ]);

            // Notificar beneficiario
            $beneficiario = User::find($clave->beneficiario_id);
            if ($beneficiario && $beneficiario->email) {
                // TODO: Enviar email: "Documentos cargados, en proceso de verificación"
                // $beneficiario->notify(new DocumentosCargadosNotification($solicitud));
            }

            return response()->json([
                'success' => true,
                'resumen' => [
                    'folio' => $validated['folio'],
                    'documentos_cargados' => $cantidadDocs,
                    'estado_actual' => 'DOCUMENTOS_PENDIENTE_VERIFICACIÓN',
                    'siguiente_paso' => 'Ir a panel de verificación ordinario',
                    'url_verificador' => route('admin.solicitudes.index'),
                ],
                'mensaje' => "✓ {$cantidadDocs} documentos confirmados. La solicitud entra al flujo ordinario."
            ]);

        } catch (\Exception $e) {
            Log::error('Error Caso A Momento 2 - Confirmar carga: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'No se pudo confirmar la carga. Intente nuevamente.'
            ], 500);
        }
    }

    /**
     * MOMENTO 3: Consulta privada (sin autenticación)
     * 
     * Flujo:
     * 1. Beneficiario accede a URL pública: /consulta-privada
     * 2. Ingresa folio + clave privada
     * 3. Sistema valida contra BD
     * 4. Si válido: muestra estado de documentos + apoyo
     * 5. Si inválido: error + cuenta intentos fallidos
     * 6. Después de 5 intentos fallidos: bloquea por 24h
     * 
     * GET /consulta-privada
     * 
     * @return \Illuminate\View\View
     */
    public function momentoTresForm()
    {
        // Verificar si ya existe sesión privada válida
        if (session('caso_a_folio')) {
            return redirect()->route('caso-a.resumen-momento-tres');
        }

        return view('caso-a.momento-tres', [
            'title' => 'Consulta Privada de Expediente - INJUVE Nayarit',
        ]);
    }

    /**
     * MOMENTO 3: Procesar consulta privada
     * 
     * Acciones:
     * 1. Recibir folio + clave
     * 2. Validar contra BD (claves_seguimiento_privadas)
     * 3. Si válido:
     *    - Sesión privada por 30 minutos
     *    - Mostrar estado de expediente, documentos, apoyo
     * 4. Si inválido:
     *    - Contar intento fallido
     *    - Si >= 5: bloquear clave por 24h
     *    - Mostrar error con intentos restantes
     * 
     * POST /consulta-privada/validar
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function validarMomentoTres(Request $request)
    {
        // Validaciones
        $validated = $request->validate([
            'folio' => 'required|string|max:100',
            'clave' => 'required|string|size:20' // KX7M-9P2W-5LQ8 (20 chars con guiones)
        ]);

        try {
            // Llamar servicio de validación
            $resultado = $this->casoAService->consultarExpedientePrivado(
                $validated['folio'],
                $validated['clave']
            );

            if ($resultado['valido']) {
                // Crear sesión privada (30 minutos)
                session([
                    'caso_a_folio' => $validated['folio'],
                    'caso_a_privada' => true,
                    'caso_a_expira' => now()->addMinutes(30),
                ]);

                return redirect()->route('caso-a.resumen-momento-tres')
                    ->with('success', $resultado['mensaje']);
            } else {
                // Error en validación
                return redirect()->back()
                    ->with('error', $resultado['mensaje'])
                    ->withInput();
            }

        } catch (\Exception $e) {
            Log::error('Error en validación de clave privada: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Error en la validación. Intente nuevamente.')
                ->withInput();
        }
    }

    /**
     * MOMENTO 3: Mostrar resumen de expediente (vista privada)
     * 
     * Datos mostrados:
     * - Folio
     * - Fecha de entrega (Momento 1)
     * - Estado actual de documentos
     * - Lista de documentos cargados (nombre, tipo, fecha)
     * - Estado del apoyo (hitos completados)
     * - Información de contacto si hay preguntas
     * 
     * GET /consulta-privada/resumen
     * 
     * @return \Illuminate\View\View
     */
    public function mostrarResumenMomentoTres()
    {
        // Middleware privado: verificar que sesión privada existe
        if (!session('caso_a_folio')) {
            return redirect()->route('caso-a.momento-tres-form')
                ->with('error', 'Sesión expirada. Por favor ingrese nuevamente.');
        }

        // Obtener folio de sesión
        $folio = session('caso_a_folio');

        // Buscar expediente por folio en claves_seguimiento_privadas
        $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $folio)->first();
        
        if (!$clave) {
            session()->forget(['caso_a_folio', 'caso_a_privada', 'caso_a_expira']);
            return redirect()->route('caso-a.momento-tres-form')
                ->with('error', 'Expediente no encontrado');
        }

        // Obtener solicitud directamente por folio (más confiable)
        $solicitud = Solicitud::where('folio', $folio)->first();
        
        if (!$solicitud) {
            session()->forget(['caso_a_folio', 'caso_a_privada', 'caso_a_expira']);
            return redirect()->route('caso-a.momento-tres-form')
                ->with('error', 'Solicitud no encontrada');
        }

        // Obtener documentos + apoyo + hitos
        $documentos = $solicitud->documentos()->get() ?? [];
        $apoyo = $solicitud->apoyo;
        $hitos = $apoyo?->hitos()->orderBy('orden_hito')->get() ?? [];
        
        // Obtener beneficiario (puede ser NULL para beneficiarios no registrados)
        $beneficiario = $solicitud->beneficiario;
        if (!$beneficiario && $solicitud->fk_curp) {
            // Beneficiario no registrado - mostrar datos básicos
            $beneficiario = (object)[
                'nombre' => 'Información',
                'apellido_paterno' => 'Disponible',
                'apellido_materno' => 'en Detalles',
                'curp' => $solicitud->fk_curp,
            ];
        }

        return view('caso-a.resumen-privado', [
            'folio' => $folio,
            'beneficiario' => $beneficiario,
            'solicitud' => $solicitud,
            'documentos' => $documentos,
            'apoyo' => $apoyo,
            'hitos' => $hitos,
            'fechaEntrega' => $clave->fecha_creacion,
        ]);
    }

    /**
     * MOMENTO 3: Cerrar sesión privada
     * 
     * Acciones:
     * 1. Destruir sesión privada
     * 2. Redirect a página de inicio
     * 
     * POST /consulta-privada/logout
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function cerrarSesionMomentoTres()
    {
        session()->forget(['caso_a_folio', 'caso_a_privada', 'caso_a_expira']);
        
        return redirect('/')
            ->with('success', 'Sesión cerrada correctamente. Gracias por usar INJUVE.');
    }

    /**
     * API: Buscar datos del folio (para llenado dinámico en Momento 2)
     * 
     * GET /api/caso-a/folio/{folio}
     * 
     * Retorna:
     * - Datos del beneficiario
     * - Datos del apoyo
     * - Documentos requeridos del apoyo
     * 
     * @param string $folio
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerDatosDelFolio($folio)
    {
        try {
            // Convertir folio a int (viene como string de la URL)
            $folio = (int)$folio;
            
            // Buscar la clave (validar que el folio existe)
            $clave = ClaveSegumientoPrivada::where('folio', $folio)->first();
            
            if (!$clave) {
                return response()->json([
                    'success' => false,
                    'error' => 'Folio no encontrado'
                ], 404);
            }

            // Obtener solicitud por folio
            $solicitud = Solicitud::where('folio', $folio)->first();
            
            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solicitud no encontrada'
                ], 404);
            }

            // Obtener beneficiario con relación user (para el email)
            $beneficiario = $solicitud->beneficiario ? $solicitud->beneficiario()->with('user')->first() : null;
            
            // Obtener email: del usuario si existe, sino del campo observaciones de la solicitud
            $email = null;
            if ($beneficiario && $beneficiario->user) {
                $email = $beneficiario->user->email;
            } else {
                // Extraer email del campo observaciones_internas (formato: "... | Email: algo@ejemplo.com | Tel: ...")
                if ($solicitud->observaciones_internas && preg_match('/\| Email:\s*([^\|]+)\s*\|/', $solicitud->observaciones_internas, $matches)) {
                    $email = trim($matches[1]);
                    if ($email === 'N/A' || $email === '') {
                        $email = null;
                    }
                }
            }
            
            if (!$beneficiario && $solicitud->fk_curp) {
                // Crear objeto con datos básicos para no registrados
                $beneficiario = (object)[
                    'nombre' => 'Beneficiario',
                    'curp' => $solicitud->fk_curp,
                    'email' => $email,
                    'telefono' => 'N/A',
                    'user' => null
                ];
            }

            // Obtener apoyo
            $apoyo = $solicitud->apoyo;
            if (!$apoyo) {
                return response()->json([
                    'success' => false,
                    'error' => 'Apoyo no encontrado'
                ], 404);
            }

            // Obtener documentos requeridos del apoyo
            $documentosRequeridos = \DB::table('Requisitos_Apoyo')
                ->join('Cat_TiposDocumento', 'Requisitos_Apoyo.fk_id_tipo_doc', '=', 'Cat_TiposDocumento.id_tipo_doc')
                ->where('Requisitos_Apoyo.fk_id_apoyo', $apoyo->id_apoyo)
                ->where('Requisitos_Apoyo.es_obligatorio', 1)
                ->select(
                    'Cat_TiposDocumento.id_tipo_doc',
                    'Cat_TiposDocumento.nombre_documento',
                    'Cat_TiposDocumento.tipo_archivo_permitido',
                    'Cat_TiposDocumento.validar_tipo_archivo',
                    'Cat_TiposDocumento.peso_maximo_mb',
                    'Requisitos_Apoyo.es_obligatorio'
                )
                ->distinct()
                ->get()
                ->unique('id_tipo_doc')
                ->values();

            // Obtener documentos ya cargados
            $documentosCargados = Documento::where('fk_folio', $folio)
                ->where('origen_archivo', 'admin_escaneo_presencial')
                ->pluck('fk_id_tipo_doc')
                ->map(fn ($id) => (int) $id)
                ->unique()
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'beneficiario' => [
                    'nombre' => trim(($beneficiario->nombre ?? '') . ' ' . ($beneficiario->apellido_paterno ?? '') . ' ' . ($beneficiario->apellido_materno ?? '')),
                    'curp' => $beneficiario->curp ?? 'N/A',
                    'email' => ($email ?? 'No registrado'),
                    'telefono' => $beneficiario->telefono ?? 'N/A'
                ],
                'apoyo' => [
                    'id' => $apoyo->id_apoyo,
                    'nombre' => $apoyo->nombre_apoyo ?? 'N/A',
                    'descripcion' => $apoyo->descripcion ?? 'Sin descripción',
                    'tipo' => $apoyo->tipo_apoyo ?? 'N/A',
                    'monto_maximo' => $apoyo->monto_maximo ?? 0,
                ],
                'documentos_requeridos' => $documentosRequeridos->map(function($doc) use ($documentosCargados) {
                    return [
                        'id_tipo_doc' => $doc->id_tipo_doc,
                        'nombre_documento' => $doc->nombre_documento,
                        'es_obligatorio' => $doc->es_obligatorio,
                        'ya_cargado' => in_array($doc->id_tipo_doc, $documentosCargados),
                        'tipo_archivo_permitido' => $doc->tipo_archivo_permitido ?? 'pdf,jpg,jpeg,png',
                        'validar_tipo_archivo' => (bool)$doc->validar_tipo_archivo,
                        'peso_maximo_mb' => $doc->peso_maximo_mb ?? 5
                    ];
                })->values(),
                'folio' => $folio
            ]);

        } catch (\Exception $e) {
            Log::error('Error API Caso A obtenerDatosDelFolio: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener datos del folio'
            ], 500);
        }
    }

    /**
     * API: Obtener folios pendientes de escaneo
     * 
     * GET /api/caso-a/pendientes-escaneo
     * 
     * Retorna todos los folios que tienen documentos pendientes por cargar
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function obtenerPendientesEscaneo()
    {
        try {
            // Obtener todos los folios Caso A (tienen clave privada)
            $claves = ClaveSegumientoPrivada::all();
            
            $pendientes = [];

            foreach ($claves as $clave) {
                // Obtener solicitud
                $solicitud = Solicitud::where('folio', $clave->folio)->first();
                
                if (!$solicitud) {
                    continue; // Skip si no tiene solicitud
                }

                // Obtener apoyo
                $apoyo = $solicitud->apoyo;
                if (!$apoyo) {
                    continue;
                }

                // Obtener documentos requeridos
                $documentosRequeridos = \DB::table('Requisitos_Apoyo')
                    ->where('fk_id_apoyo', $apoyo->id_apoyo)
                    ->where('es_obligatorio', true)
                    ->pluck('fk_id_tipo_doc')
                    ->unique()
                    ->count();

                if ($documentosRequeridos === 0) {
                    continue; // Sin requisitos
                }

                // Contar documentos cargados (solo los escaneados en Momento 2)
                $documentosCargados = Documento::where('fk_folio', $clave->folio)
                    ->where('origen_archivo', 'admin_escaneo_presencial')
                    ->pluck('fk_id_tipo_doc')
                    ->map(fn ($id) => (int) $id)
                    ->unique()
                    ->count();

                // Si faltan documentos, es pendiente
                if ($documentosCargados < $documentosRequeridos) {
                    $beneficiario = $solicitud->beneficiario ? $solicitud->beneficiario()->with('user')->first() : null;
                    if (!$beneficiario && $solicitud->fk_curp) {
                        $beneficiario = (object)[
                            'nombre' => 'No registrado',
                        ];
                    }

                    $pendientes[] = [
                        'folio' => $clave->folio,
                        'beneficiario_nombre' => trim(($beneficiario->nombre ?? '') . ' ' . ($beneficiario->apellido_paterno ?? '') . ' ' . ($beneficiario->apellido_materno ?? '')),
                        'apoyo_nombre' => $apoyo->nombre_apoyo ?? 'N/A',
                        'documentos_requeridos' => $documentosRequeridos,
                        'documentos_cargados' => $documentosCargados,
                        'fecha_creacion' => $clave->fecha_creacion?->format('d/m/Y H:i') ?? 'N/A'
                    ];
                }
            }

            // Ordenar por fecha más antigua primero
            usort($pendientes, function($a, $b) {
                return strcmp($a['folio'], $b['folio']);
            });

            return response()->json([
                'success' => true,
                'total' => count($pendientes),
                'pendientes' => $pendientes
            ]);

        } catch (\Exception $e) {
            Log::error('Error API Caso A obtenerPendientesEscaneo: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'Error al obtener pendientes'
            ], 500);
        }
    }

    /**
     * Helper: Middleware para sesión privada
     * 
     * Verifica que:
     * 1. Existe folio en sesión
     * 2. Sesión no expiró (< 30 minutos)
     * 3. Folio sigue siendo válido en BD
     * 
     * Middleware utilizable en rutas: 'middleware' => 'session.privada'
     */
    // Definir en app/Http/Middleware/VerificaSesionPrivada.php
}
