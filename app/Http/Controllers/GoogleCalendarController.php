<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DirectivoCalendarioPermiso;
use App\Models\CalendarioSincronizacionLog;
use App\Models\OAuthState;
use App\Services\GoogleCalendarService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Log;

/**
 * GoogleCalendarController
 * 
 * Gestiona la integración con Google Calendar:
 * 1. OAuth 2.0 Flow (autenticación)
 * 2. Sincronización de hitos a eventos
 * 3. Sincronización bidireccional (Google → SIGO)
 * 4. Gestión de permisos y tokens
 */
class GoogleCalendarController extends Controller
{
    protected $googleCalendarService;

    public function __construct(GoogleCalendarService $googleCalendarService)
    {
        $this->googleCalendarService = $googleCalendarService;
    }

    /**
     * Mostrar página de configuración de Google Calendar
     * 
     * Acceso: Solo Directivos (Rol 3)
     * 
     * GET /admin/calendario
     * 
     * @return \Illuminate\View\View
     */
    public function mostrarConfiguracion()
    {
        // Middleware: auth + role:3
        $directivo_id = Auth::user()->id_usuario;
        $permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', $directivo_id)->first();

        // Obtener últimos cambios de sincronización
        $logs = CalendarioSincronizacionLog::where('usuario_id', $directivo_id)
            ->latest('fecha_cambio')
            ->limit(10)
            ->get();

        return view('admin.calendario.configuracion', [
            'permiso' => $permiso,
            'logs' => $logs,
            'conectado' => $permiso && $permiso->activo ? true : false,
            'ultimaSincronizacion' => $permiso?->ultima_sincronizacion,
        ]);
    }

    /**
     * Iniciar OAuth 2.0 Flow
     * 
     * Acciones:
     * 1. Generar URL de autenticación de Google
     * 2. Crear estado CSRF con directivo_id codificado en base64
     * 3. Guardar state en BD (no en sesión - más robusto)
     * 4. Redirigir a Google
     * 
     * GET /admin/calendario/auth
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function redirectToGoogle()
    {
        try {
            $directivo_id = Auth::user()->id_usuario;
            
            // Generar state CSRF seguro (usa BD en lugar de sesión)
            $state = OAuthState::generateState($directivo_id, 'google', 30);
            
            Log::info("GoogleCalendarController: Iniciando OAuth para directivo_id={$directivo_id}, state={$state}");

            // Pasar el state al servicio para que lo configure en el cliente Google
            $url = $this->googleCalendarService->generarUrlAutenticacion($state);

            return redirect($url);
        } catch (\Exception $e) {
            Log::error('Error al iniciar Google OAuth: ' . $e->getMessage());
            return redirect('/')
                ->with('error', 'No se pudo conectar con Google. Verifique la configuración.');
        }
    }

    /**
     * Manejar callback de Google OAuth
     * 
     * Acciones:
     * 1. Validar que el state existe y es válido (desde BD)
     * 2. Validar que no ha expirado
     * 3. Decodificar directivo_id desde state (base64)
     * 4. Intercambiar código por tokens
     * 5. Guardar tokens encriptados en BD
     * 6. Crear entrada en directivos_calendario_permisos
     * 7. Marcar state como usado
     * 8. Redirect a configuración con éxito
     * 
     * GET /admin/calendario/callback
     * 
     * @param Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback(Request $request)
    {
        $state = $request->query('state');
        $code = $request->query('code');
        $error = $request->query('error');

        // Validar que el state existe y es válido (desde BD)
        $oauthState = OAuthState::validateState($state);
        
        if (!$oauthState) {
            Log::error("Estado CSRF inválido o expirado - state: {$state}");
            return redirect('/admin/calendario')
                ->with('error', 'El proceso de autenticación expiró o es inválido. Por favor intente nuevamente.');
        }

        $directivo_id = $oauthState->directivo_id;

        if ($error) {
            Log::error("Google OAuth error: {$error} para directivo_id={$directivo_id}");
            // Marcar como no usado para permitir reintentar
            return redirect('/admin/calendario')
                ->with('error', 'Google OAuth fue cancelado: ' . $error);
        }

        if (!$code) {
            Log::error("Código de autorización no recibido para directivo_id={$directivo_id}");
            return redirect('/admin/calendario')
                ->with('error', 'Código de autorización no recibido. Intente nuevamente.');
        }

        try {
            Log::info("GoogleCalendarController: Procesando callback OAuth para directivo_id={$directivo_id}");
            
            $success = $this->googleCalendarService->manejarCallbackOAuth($code, $directivo_id);
            
            if ($success) {
                // Marcar state como usado
                $oauthState->markAsUsed();
                Log::info("GoogleCalendarController: OAuth exitoso para directivo_id={$directivo_id}");
                return redirect('/admin/calendario')
                    ->with('success', 'Google Calendar conectado exitosamente.');
            } else {
                Log::error("GoogleCalendarController: Error al manejar callback para directivo_id={$directivo_id}");
                return redirect('/admin/calendario')
                    ->with('error', 'Error al autorizar Google Calendar. Intente de nuevo.');
            }
        } catch (\Exception $e) {
            Log::error("Error en callback de Google OAuth para directivo_id={$directivo_id}: " . $e->getMessage());
            return redirect('/admin/calendario')
                ->with('error', 'Error al procesar la autorización: ' . $e->getMessage());
        }
    }

    /**
     * Sincronizar manualmente desde Google Calendar → SIGO
     * 
     * Acciones:
     * 1. Obtener directivo actual
     * 2. Llamar sincronización
     * 3. Retornar resumen de cambios
     * 
     * POST /admin/calendario/sync
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sincronizar()
    {
        try {
            $directivo_id = Auth::id();

            $permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', $directivo_id)
                ->where('activo', 1)
                ->first();

            if (!$permiso) {
                return redirect('/admin/calendario')
                    ->with('error', 'No has conectado Google Calendar. Conecta primero.');
            }

            $resultado = $this->googleCalendarService->sincronizarDesdeGoogle($directivo_id);

            if ($resultado['cambios_procesados'] > 0) {
                return redirect('/admin/calendario')
                    ->with('success', "Se sincronizaron {$resultado['cambios_procesados']} cambios.");
            } else {
                return redirect('/admin/calendario')
                    ->with('info', 'No hay cambios nuevos para sincronizar.');
            }
        } catch (\Exception $e) {
            Log::error('Error al sincronizar Google Calendar: ' . $e->getMessage());
            return redirect('/admin/calendario')
                ->with('error', 'Error al sincronizar. Intente nuevamente.');
        }
    }

    /**
     * Desconectar Google Calendar
     * 
     * Acciones:
     * 1. Revocar tokens en Google
     * 2. Limpiar entrada en directivos_calendario_permisos
     * 3. Logout session
     * 4. Redirect a configuración
     * 
     * POST /admin/calendario/disconnect
     * 
     * @return \Illuminate\Http\RedirectResponse
     */
    public function desconectar()
    {
        try {
            $directivo_id = Auth::id();
            
            $success = $this->googleCalendarService->desconectarCalendar($directivo_id);
            
            if ($success) {
                return redirect('/admin/calendario')
                    ->with('success', 'Google Calendar desconectado exitosamente.');
            } else {
                return redirect('/admin/calendario')
                    ->with('error', 'No se pudo desconectar Google Calendar.');
            }
        } catch (\Exception $e) {
            Log::error('Error al desconectar Google Calendar: ' . $e->getMessage());
            return redirect('/admin/calendario')
                ->with('error', 'Error al desconectar. Intente nuevamente.');
        }
    }

    /**
     * Mostrar logs de sincronización
     * 
     * Acciones:
     * 1. Obtener últimos N logs de calendario_sincronizacion_log
     * 2. Filtrar por directivo actual (opcional)
     * 3. Paginar resultados
     * 4. Mostrar con timestamps, cambios, origen (SIGO/Google)
     * 
     * GET /admin/calendario/logs
     * 
     * @return \Illuminate\View\View
     */
    public function mostrarLogs(Request $request)
    {
        $tipo_cambio = $request->query('tipo_cambio');
        $origen = $request->query('origen');
        $desde = $request->query('desde');
        $hasta = $request->query('hasta');

        $query = CalendarioSincronizacionLog::query();

        if ($tipo_cambio) {
            $query->where('tipo_cambio', $tipo_cambio);
        }

        if ($origen) {
            $query->where('origen', $origen);
        }

        if ($desde) {
            $query->whereDate('fecha_cambio', '>=', $desde);
        }

        if ($hasta) {
            $query->whereDate('fecha_cambio', '<=', $hasta);
        }

        $logs = $query->with(['hito', 'apoyo', 'usuario'])
            ->latest('fecha_cambio')
            ->paginate(50);

        // Estadísticas
        $estadisticas = [
            'total' => CalendarioSincronizacionLog::count(),
            'por_tipo' => CalendarioSincronizacionLog::groupBy('tipo_cambio')->selectRaw('tipo_cambio, count(*) as total')->get(),
            'por_origen' => CalendarioSincronizacionLog::groupBy('origen')->selectRaw('origen, count(*) as total')->get(),
        ];

        return view('admin.calendario.logs', [
            'logs' => $logs,
            'filtros' => [
                'tipo_cambio' => $tipo_cambio,
                'origen' => $origen,
                'desde' => $desde,
                'hasta' => $hasta,
            ],
            'estadisticas' => $estadisticas,
        ]);
    }

    /**
     * Endpoint de webhook para cambios en Google Calendar (futuro)
     * 
     * NOTA: Implementación futura - permite que Google notifique a SIGO
     *       cuando hay cambios en Google Calendar en tiempo real
     *       (en lugar de solo sincronizar cada X minutos)
     * 
     * POST /webhook/google-calendar
     * 
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function webhookGoogleCalendar(Request $request)
    {
        // TO BE IMPLEMENTED (Fase 2+)
        // Validar cabecera X-Goog-Resource-State
        // Actualizar last_sync timestamp
        // Procesar cambios notificados
    }

    /**
     * API endpoint: Obtener estado de sincronización (AJAX)
     * 
     * Retorna JSON con:
     * - conectado (boolean)
     * - email_google (string)
     * - última_sincronización (datetime)
     * - cambios_pendientes (int)
     * 
     * GET /api/calendario/status
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function apiStatus()
    {
        // TO BE IMPLEMENTED
        // Middleware: auth + role:3
        
        try {
            // Obtener directivo actual
            $directivo_id = Auth::user()->id_usuario;

            // Buscar permiso
            $permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', $directivo_id)->first();

            // Retornar JSON
            return response()->json([
                'conectado' => $permiso && $permiso->activo ? true : false,
                'email_google' => $permiso?->email_directivo,
                'ultima_sincronizacion' => $permiso?->ultima_sincronizacion?->format('Y-m-d H:i:s'),
                'cambios_pendientes' => 0, // TODO: Calcular basado en logs
                'token_expiracion' => $permiso?->token_expiracion?->format('Y-m-d H:i:s')
            ]);

        } catch (\Exception $e) {
            Log::error('Error en apiStatus: ' . $e->getMessage());
            return response()->json(['error' => 'Error al obtener estado'], 500);
        }
    }
}
