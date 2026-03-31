<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Solicitudes;
use App\Models\DocumentoExpediente;
use App\Models\Apoyo;
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

        // Obtener apoyos activos (en estado RECEPCION)
        $apoyos = Apoyo::where('estado_apoyo', 'RECEPCION')
            ->orWhere('estado_apoyo', 'ACTIVO')
            ->get();

        // Historial de expedientes creados hoy (por este admin)
        $expedientesHoy = \App\Models\ClaveSegumientoPrivada::whereDate('fecha_creacion', today())->get();

        return view('admin.caso-a.momento-uno', [
            'apoyos' => $apoyos,
            'expedientesHoy' => $expedientesHoy,
            'adminNombre' => $user->nombre,
        ]);
    }

    /**
     * MOMENTO 1: Guardar expediente presencial
     * 
     * Acciones:
     * 1. Validar datos ingresados
     * 2. Verificar beneficiario existe
     * 3. Verificar apoyo existe y está en estado RECEPCION
     * 4. Llamar $casoAService->crearExpedientePresencial()
     * 5. Generar folio + clave privada
     * 6. Mostrar resumen para imprimir
     * 
     * POST /admin/caso-a/momento-uno
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function guardarMomentoUno(Request $request)
    {
        // Validaciones
        $validated = $request->validate([
            'beneficiario_id' => 'required|integer|exists:usuarios,id_usuario',
            'apoyo_id' => 'required|integer|exists:apoyos,id_apoyo',
            'documento_identidad' => 'required|string|min:7|max:20',
            'documentos_listados' => 'required|array|min:1'
        ]);

        try {
            $beneficiario = Usuarios::findOrFail($validated['beneficiario_id']);
            $apoyo = Apoyo::findOrFail($validated['apoyo_id']);
            $adminId = Auth::id();

            // Crear expediente presencial
            $resultado = $this->casoAService->crearExpedientePresencial(
                $validated['beneficiario_id'],
                $validated['apoyo_id'],
                $validated['documento_identidad'],
                $validated['documentos_listados']
            );

            // Guardar folio y clave en sesión para mostrar resumen
            session([
                'caso_a_folio' => $resultado['folio'],
                'caso_a_clave' => $resultado['clave_acceso'],
            ]);

            return redirect()->route('caso-a.resumen-momento-uno', ['folio' => $resultado['folio']])
                ->with('success', 'Expediente presencial creado exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al crear expediente presencial: ' . $e->getMessage());
            return redirect()->back()->with('error', 'No se pudo crear el expediente. Intente nuevamente.');
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

        // Buscar clave en BD
        $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $folio)->first();
        
        if (!$clave) {
            return Redirect::back()->with('error', 'Folio no encontrado');
        }

        $beneficiario = $clave->beneficiario;
        $solicitud = Solicitudes::where('beneficiario_id', $clave->beneficiario_id)->first();
        $apoyo = $solicitud?->apoyo;

        return view('admin.caso-a.resumen-momento-uno', [
            'folio' => $folio,
            'clave' => $clave->clave_alfanumerica,
            'beneficiario' => $beneficiario,
            'apoyo' => $apoyo,
            'fechaCreacion' => $clave->fecha_creacion,
            'qrData' => base64_encode($folio),
        ]);
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
        $documentosHoy = DocumentoExpediente::whereDate('fecha_carga', today())
            ->where('cargado_por', $adminId)
            ->with('solicitud')
            ->get();

        // Estadísticas
        $estadisticas = [
            'pendientes' => DocumentoExpediente::where('estado_verificacion', 'PENDIENTE')->count(),
            'completados' => DocumentoExpediente::where('estado_verificacion', 'VERIFICADO')->count(),
            'conError' => DocumentoExpediente::where('estado_verificacion', 'RECHAZADO')->count(),
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
    public function cargarDocumentoMomentoDos(Request $request)
    {
        // Validaciones
        $validated = $request->validate([
            'folio' => 'required|string|exists:claves_seguimiento_privadas,folio',
            'documento' => 'required|file|mimes:pdf,jpg,jpeg,png|max:5120',
            'tipo_documento' => 'required|string'
        ]);

        try {
            // Obtener datos
            $adminId = Auth::id();
            $archivo = $request->file('documento');

            // Obtener clave + solicitud
            $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $validated['folio'])->first();
            $solicitud = Solicitudes::where('beneficiario_id', $clave->beneficiario_id)->first();

            if (!$solicitud) {
                return response()->json([
                    'success' => false,
                    'error' => 'Solicitud no encontrada'
                ], 404);
            }

            // Procesar documento con el servicio
            $documento = $this->casoAService->escanearDocumentoPresencial(
                $solicitud->id_solicitud,
                $adminId,
                $archivo,
                $validated['tipo_documento']
            );

            return response()->json([
                'success' => true,
                'documento' => [
                    'id' => $documento->id_documento,
                    'tipo' => $documento->tipo_documento,
                    'folio' => $clave->folio,
                    'fecha' => $documento->fecha_carga->format('Y-m-d H:i:s'),
                    'hash' => substr($documento->hash_documento, 0, 16) . '...',
                ],
                'mensaje' => 'Documento cargado y verificado exitosamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error al cargar documento: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'error' => 'No se pudo cargar el documento. Intente nuevamente.'
            ], 500);
        }
    }

    /**
     * MOMENTO 2: Confirmar carga de todos los documentos
     * 
     * Acciones:
     * 1. Validar que folio tiene mínimo N documentos
     * 2. Cambiar estado solicitud a DOCUMENTOS_CARGADOS_Y_VERIFICADOS
     * 3. Generar resumen de auditoría
     * 4. Notificar beneficiario que puede consultar
     * 
     * POST /admin/caso-a/momento-dos/confirmar
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function confirmarCargaMomentoDos(Request $request)
    {
        // Validaciones
        $validated = $request->validate([
            'folio' => 'required|string|exists:claves_seguimiento_privadas,folio'
        ]);

        try {
            // Buscar expediente
            $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $validated['folio'])->first();
            $solicitud = Solicitudes::where('beneficiario_id', $clave->beneficiario_id)->first();

            if (!$solicitud) {
                return redirect()->back()->with('error', 'Solicitud no encontrada');
            }

            // Contar documentos cargados
            $cantidadDocs = DocumentoExpediente::where('fk_id_solicitud', $solicitud->id_solicitud)->count();
            $minimoRequerido = 3; // Mínimo de documentos

            if ($cantidadDocs < $minimoRequerido) {
                return redirect()->back()->with('error', 
                    "Debe cargar al menos {$minimoRequerido} documentos. Actualmente tiene {$cantidadDocs}."
                );
            }

            // Cambiar estado solicitud
            $estadoId = DB::table('Cat_EstadosSolicitud')
                ->where('nombre_estado', 'DOCUMENTOS_CARGADOS_Y_VERIFICADOS')
                ->value('id_estado') ?? 7;

            $solicitud->update([
                'estado_solicitud' => $estadoId,
                'fecha_cambio_estado' => now(),
            ]);

            // Registrar auditoría final
            \App\Models\AuditoriaCargaMaterial::create([
                'folio' => $validated['folio'],
                'evento' => 'carga_confirmada',
                'admin_id' => Auth::id(),
                'cantidad_docs' => $cantidadDocs,
                'fecha_evento' => now(),
                'ip_admin' => $request->ip(),
                'navegador_agente' => $request->header('User-Agent'),
                'detalles_evento' => json_encode([
                    'folio' => $validated['folio'],
                    'beneficiario_id' => $clave->beneficiario_id,
                    'cantidad_documentos' => $cantidadDocs,
                ]),
            ]);

            // TODO: Enviar email a beneficiario
            // Mail::to($clave->beneficiario->email)->send(new CargaConfirmadaMail($validated['folio']));

            return redirect()->route('admin.dashboard')
                ->with('success', 'Carga completada y confirmada exitosamente');

        } catch (\Exception $e) {
            Log::error('Error al confirmar carga: ' . $e->getMessage());
            return redirect()->back()->with('error', 'No se pudo confirmar la carga.');
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

        // Buscar expediente
        $clave = \App\Models\ClaveSegumientoPrivada::where('folio', $folio)->first();
        
        if (!$clave) {
            session()->forget(['caso_a_folio', 'caso_a_privada', 'caso_a_expira']);
            return redirect()->route('caso-a.momento-tres-form')
                ->with('error', 'Expediente no encontrado');
        }

        // Obtener solicitud + documentos
        $solicitud = Solicitudes::where('beneficiario_id', $clave->beneficiario_id)->first();
        $documentos = $solicitud?->documentos()->get() ?? [];
        $apoyo = $solicitud?->apoyo;
        $hitos = $apoyo?->hitos()->orderBy('fecha_hito_aproximada')->get() ?? [];

        return view('caso-a.resumen-privado', [
            'folio' => $folio,
            'beneficiario' => $clave->beneficiario,
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
