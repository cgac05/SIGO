<?php

namespace App\Services;

use App\Models\User;
use App\Models\Apoyo;
use App\Models\HitosApoyo;
use App\Models\DirectivoCalendarioPermiso;
use App\Models\CalendarioSincronizacionLog;
use Google\Client as Google_Client;
use Google\Service\Calendar as Google_Service_Calendar;
use Google\Service\Calendar\Event as Google_Service_Calendar_Event;
use Google\Service\Calendar\EventDateTime;
use Google\Service\Oauth2 as Google_Service_Oauth2;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * GoogleCalendarService
 * 
 * Gestiona la integración bidireccional con Google Calendar:
 * 1. Sincronización SIGO → Google (crear/actualizar/eliminar eventos por hitos)
 * 2. Sincronización Google → SIGO (reflejar cambios desde directivos)
 * 3. Manejo de OAuth 2.0 tokens (refresh automático)
 * 4. Registro de cambios en logs para auditoría
 */
class GoogleCalendarService
{
    protected $googleClient;
    protected $calendarService;

    public function __construct()
    {
        try {
            $this->googleClient = new Google_Client();
            $this->googleClient->setApplicationName('SIGO - INJUVE');
            // Scopes necesarios: Calendar para eventos, Oauth2 para obtener email del usuario
            $this->googleClient->setScopes([
                Google_Service_Calendar::CALENDAR,
                Google_Service_Oauth2::USERINFO_EMAIL,
                Google_Service_Oauth2::OPENID,
            ]);
            
            // Las credenciales se configuran en generarUrlAutenticacion()
            // usando las variables de .env (CLIENT_ID, CLIENT_SECRET, REDIRECT_URI)
            
            $this->calendarService = new Google_Service_Calendar($this->googleClient);
        } catch (\Exception $e) {
            Log::error('Error al inicializar GoogleCalendarService: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * FLUJO 1: Crear eventos cuando se publica nuevo apoyo
     * 
     * Acciones:
     * - Buscar todos los directivos activos (Rol 3)
     * - Para cada directivo, crear eventos para CADA HITO del apoyo
     * - Colores diferenciados por tipo de hito
     * - Recuerdos automáticos (configurable por apoyo)
     * - Enviar invitaciones automáticas
     * - Registrar en calendario_sincronizacion_log
     * 
     * @param int $id_apoyo
     * @return array ['eventos_creados' => n, 'directivos' => [...], 'errores' => [...]]
     */
    public function crearEventosApoyo($id_apoyo)
    {
        $resultado = [
            'eventos_creados' => 0,
            'directivos' => [],
            'errores' => [],
        ];

        try {
            $apoyo = Apoyo::with('hitos')->findOrFail($id_apoyo);

            // Validar que sincronización esté habilitada
            if (!$apoyo->sincronizar_calendario) {
                return $resultado;
            }

            // ✅ CORREGIDA: Obtener SOLO el PRIMER directivo activo para evitar duplicados
            // (cada hito debe crearse una única vez, no múltiples)
            $permiso = $this->obtenerDirectivosActivos()->first();
            
            if (!$permiso) {
                $resultado['errores'][] = "No hay directivos con permisos de calendario activos";
                return $resultado;
            }

            try {
                // Actualizar token si expiró
                if ($permiso->tokenVencePronto()) {
                    $this->refrescarToken($permiso);
                }

                // Desencriptar y decodificar el token completo
                $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
                $this->googleClient->setAccessToken($tokenCompleto);
                
                // RE-INICIALIZAR calendar service con el nuevo token
                $this->calendarService = new \Google_Service_Calendar($this->googleClient);

                // Crear evento por cada hito
                foreach ($apoyo->hitos as $hito) {
                    try {
                        if (!$hito->fecha_inicio) {
                            continue; // Saltar hitos sin fecha
                        }

                        // ✅ CORREGIDA: Evitar duplicados
                        if ($hito->google_calendar_event_id) {
                            Log::info("GoogleCalendarService::crearEventosApoyo - Hito {$hito->id_hito} ya tiene evento (ID: {$hito->google_calendar_event_id})");
                            continue;
                        }

                        $event = new Google_Service_Calendar_Event();
                        $event->setSummary("INJUVE - {$apoyo->nombre_apoyo} - {$hito->nombre_hito}");
                        $event->setDescription($this->construirDescripcionEvento($hito, $apoyo));
                        $event->setColorId($this->obtenerColorPorHito($hito->nombre_hito));

                        // ✅ CORREGIDA: Extraer fecha y fijar hora a 23:59 (Mazatlán)
                        $fecha = Carbon::parse($hito->fecha_inicio)->setTime(23, 59, 0);
                        
                        $eventDateTime = new EventDateTime();
                        $eventDateTime->setDateTime($fecha->format(\DateTime::RFC3339));
                        $eventDateTime->setTimeZone(config('app.timezone', 'America/Mexico_City'));
                        $event->setStart($eventDateTime);

                        // Fin a las 23:59:59 del mismo día
                        $endTime = $fecha->clone()->setTime(23, 59, 59);
                        $eventEnd = new EventDateTime();
                        $eventEnd->setDateTime($endTime->format(\DateTime::RFC3339));
                        $eventEnd->setTimeZone(config('app.timezone', 'America/Mexico_City'));
                        $event->setEnd($eventEnd);

                        // Recordatorios
                        if ($apoyo->recordatorio_dias) {
                            // Usar reminders por defecto sin overrides personalizados
                            $reminders = new \Google_Service_Calendar_EventReminders();
                            $reminders->setUseDefault(true);
                            $event->setReminders($reminders);
                        }

                        // Crear evento en Google Calendar
                        $createdEvent = $this->calendarService->events->insert(
                            $permiso->google_calendar_id,
                            $event
                        );

                        // Guardar ID del evento en BD
                        DB::table('hitos_apoyo')
                            ->where('id_hito', $hito->id_hito)
                            ->update([
                                'google_calendar_event_id' => $createdEvent->getId(),
                                'google_calendar_sync' => true,
                                'ultima_sincronizacion' => DB::raw('GETDATE()'),
                            ]);

                        // Registrar en log
                        $this->registrarCambioSincronizacion(
                            $hito->id_hito,
                            $apoyo->id_apoyo,
                            'creacion',
                            'sigo',
                            [],
                            [
                                'evento_id' => $createdEvent->getId(),
                                'titulo' => $event->getSummary(),
                            ],
                            auth()->id() ?? 1
                        );

                        $resultado['eventos_creados']++;
                    } catch (\Exception $e) {
                        Log::error("GoogleCalendarService::crearEventosApoyo - Hito error: {$hito->nombre_hito} - {$e->getMessage()}");
                        $resultado['errores'][] = "Error al crear evento para hito {$hito->nombre_hito}: " . $e->getMessage();
                        
                        // Debug: Log más detalle
                        if ($e->getCode() == 400) {
                            Log::error("GoogleCalendarService::crearEventosApoyo - Debug 400 Error:");
                            Log::error("  Calendar ID: " . $permiso->google_calendar_id);
                            Log::error("  Event Summary: " . $event->getSummary());
                            Log::error("  Event Start: " . $event->getStart()->getDateTime());
                            Log::error("  Event End: " . $event->getEnd()->getDateTime());
                        }
                    }
                }

                $resultado['directivos'][] = $permiso->directivo->nombre;
            } catch (\Exception $e) {
                $resultado['errores'][] = "Error al procesar directivo {$permiso->email_directivo}: " . $e->getMessage();
                Log::error("GoogleCalendarService::crearEventosApoyo - Directivo error: {$e->getMessage()}");
            }
        } catch (\Exception $e) {
            Log::error("GoogleCalendarService::crearEventosApoyo - Error general: {$e->getMessage()}");
            $resultado['errores'][] = $e->getMessage();
        }

        return $resultado;
    }

    /**
     * METODO NUEVO: Crear evento para UN SOLO hito
     * 
     * Se dispara cuando se crea un hito individual
     * Evita duplicados al no iterar sobre todos los hitos del apoyo
     * 
     * @param int $id_hito
     * @return array ['exito' => bool, 'event_id' => string|null, 'error' => string|null]
     */
    public function crearEventoHito($id_hito)
    {
        $resultado = [
            'exito' => false,
            'event_id' => null,
            'error' => null,
        ];

        try {
            $hito = HitosApoyo::findOrFail($id_hito);
            $apoyo = $hito->apoyo;

            // Validaciones
            if (!$apoyo || !$apoyo->sincronizar_calendario) {
                $resultado['error'] = "Apoyo no tiene sincronización habilitada";
                return $resultado;
            }

            if (!$hito->fecha_inicio) {
                $resultado['error'] = "Hito sin fecha";
                return $resultado;
            }

            // ✅ Evitar duplicados: Si ya tiene event_id, no crear otro
            if ($hito->google_calendar_event_id) {
                Log::info("crearEventoHito - Hito {$id_hito} ya tiene evento (ID: {$hito->google_calendar_event_id})");
                $resultado['exito'] = true;
                $resultado['event_id'] = $hito->google_calendar_event_id;
                return $resultado;
            }

            // Obtener directivo activo
            $permiso = $this->obtenerDirectivosActivos()->first();
            
            if (!$permiso) {
                $resultado['error'] = "No hay directivos con permisos activos";
                return $resultado;
            }

            try {
                // Actualizar token si expiró
                if ($permiso->tokenVencePronto()) {
                    $this->refrescarToken($permiso);
                }

                // Desencriptar token
                $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
                $this->googleClient->setAccessToken($tokenCompleto);
                
                // Reinicializar calendar service
                $this->calendarService = new \Google_Service_Calendar($this->googleClient);

                // Crear evento
                $event = new Google_Service_Calendar_Event();
                $event->setSummary("INJUVE - {$apoyo->nombre_apoyo} - {$hito->nombre_hito}");
                $event->setDescription($this->construirDescripcionEvento($hito, $apoyo));
                $event->setColorId($this->obtenerColorPorHito($hito->nombre_hito));

                // ✅ Fijar hora a 23:59
                $fecha = Carbon::parse($hito->fecha_inicio)->setTime(23, 59, 0);
                
                $eventDateTime = new EventDateTime();
                $eventDateTime->setDateTime($fecha->format(\DateTime::RFC3339));
                $eventDateTime->setTimeZone(config('app.timezone', 'America/Mexico_City'));
                $event->setStart($eventDateTime);

                $endTime = $fecha->clone()->setTime(23, 59, 59);
                $eventEnd = new EventDateTime();
                $eventEnd->setDateTime($endTime->format(\DateTime::RFC3339));
                $eventEnd->setTimeZone(config('app.timezone', 'America/Mexico_City'));
                $event->setEnd($eventEnd);

                // Recordatorios
                if ($apoyo->recordatorio_dias) {
                    $reminders = new \Google_Service_Calendar_EventReminders();
                    $reminders->setUseDefault(true);
                    $event->setReminders($reminders);
                }

                // Insertar en Google Calendar
                $createdEvent = $this->calendarService->events->insert(
                    $permiso->google_calendar_id,
                    $event
                );

                // Guardar event_id en BD
                DB::table('hitos_apoyo')
                    ->where('id_hito', $id_hito)
                    ->update([
                        'google_calendar_event_id' => $createdEvent->getId(),
                        'google_calendar_sync' => true,
                        'ultima_sincronizacion' => DB::raw('GETDATE()'),
                    ]);

                // Registrar en log
                $this->registrarCambioSincronizacion(
                    $id_hito,
                    $apoyo->id_apoyo,
                    'creacion',
                    'sigo',
                    [],
                    [
                        'evento_id' => $createdEvent->getId(),
                        'titulo' => $event->getSummary(),
                    ],
                    auth()->id() ?? 1
                );

                $resultado['exito'] = true;
                $resultado['event_id'] = $createdEvent->getId();

                Log::info("crearEventoHito - Evento creado exitosamente: {$createdEvent->getId()}");

            } catch (\Exception $e) {
                $resultado['error'] = $e->getMessage();
                Log::error("crearEventoHito - Error al crear evento: " . $e->getMessage());
                
                if ($e->getCode() == 400) {
                    Log::error("crearEventoHito - Debug 400 Error:");
                    Log::error("  Calendar ID: " . $permiso->google_calendar_id);
                    Log::error("  Event Summary: " . ($event->getSummary() ?? 'N/A'));
                }
            }

        } catch (\Exception $e) {
            $resultado['error'] = $e->getMessage();
            Log::error("crearEventoHito - Error general: " . $e->getMessage());
        }

        return $resultado;
    }

    /**
     * FLUJO 2: Actualizar eventos cuando cambian hitos
     * 
     * Se dispara automáticamente cuando admin modifica:
     * - Fecha del hito
     * - Nombre/descripción del hito
     * - Estado del hito
     * 
     * Acciones:
     * - Buscar evento en Google Calendar
     * - Actualizador campos: fecha, descripción, estado
     * - Registrar cambio en log
     * 
     * @param int $id_hito
     * @return bool (éxito)
     */
    public function actualizarEventoHito($id_hito)
    {
        try {
            $hito = HitosApoyo::findOrFail($id_hito);
            $apoyo = $hito->apoyo;

            if (!$hito->google_calendar_event_id || !$apoyo->sincronizar_calendario) {
                return false;
            }

            // ✅ CORREGIDA: Usar SOLO el primer directivo activo
            $permiso = $this->obtenerDirectivosActivos()->first();
            
            if (!$permiso) {
                return false;
            }

            try {
                if ($permiso->tokenVencePronto()) {
                    $this->refrescarToken($permiso);
                }

                // Desencriptar y decodificar el token completo
                $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
                $this->googleClient->setAccessToken($tokenCompleto);

                // RE-INICIALIZAR calendar service
                $this->calendarService = new \Google_Service_Calendar($this->googleClient);

                // Obtener evento actual
                $event = $this->calendarService->events->get(
                    $permiso->google_calendar_id,
                    $hito->google_calendar_event_id
                );

                // Actualizar descripción
                $event->setDescription($this->construirDescripcionEvento($hito, $apoyo));

                // ✅ CORREGIDA: Fijar hora a 23:59 (Mazatlán)
                if ($hito->fecha_inicio) {
                    $fecha = Carbon::parse($hito->fecha_inicio)->setTime(23, 59, 0);
                    
                    $eventDateTime = new EventDateTime();
                    $eventDateTime->setDateTime($fecha->format(\DateTime::RFC3339));
                    $eventDateTime->setTimeZone(config('app.timezone', 'America/Mexico_City'));
                    $event->setStart($eventDateTime);

                    $endTime = $fecha->clone()->setTime(23, 59, 59);
                    $eventEnd = new EventDateTime();
                    $eventEnd->setDateTime($endTime->format(\DateTime::RFC3339));
                    $eventEnd->setTimeZone(config('app.timezone', 'America/Mexico_City'));
                    $event->setEnd($eventEnd);
                }

                // Actualizar evento
                $this->calendarService->events->update(
                    $permiso->google_calendar_id,
                    $hito->google_calendar_event_id,
                    $event
                );

                // Registrar en log
                $this->registrarCambioSincronizacion(
                    $hito->id_hito,
                    $apoyo->id_apoyo,
                    'actualizacion',
                    'sigo',
                    [],
                    ['fecha' => $hito->fecha_inicio],
                    auth()->id() ?? 1
                );

                // Actualizar timestamp de sincronización
                DB::table('hitos_apoyo')
                    ->where('id_hito', $hito->id_hito)
                    ->update(['ultima_sincronizacion' => DB::raw('GETDATE()')]);

                return true;
            } catch (\Exception $e) {
                Log::error("GoogleCalendarService::actualizarEventoHito - Error: {$e->getMessage()}");
                return false;
            }
        } catch (\Exception $e) {
            Log::error("GoogleCalendarService::actualizarEventoHito - Error general: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * FLUJO 3: Eliminar eventos cuando se cancela apoyo
     * 
     * Se dispara cuando:
     * - Se cancela el apoyo completo
     * - Se elimina un hito específico
     * 
     * @param int $id_apoyo (NULL si es por hito)
     * @param int $id_hito (NULL si es por apoyo)
     * @return bool
     */
    public function eliminarEventosApoyo($id_apoyo = null, $id_hito = null)
    {
        try {
            if ($id_apoyo) {
                $hitos = HitosApoyo::where('fk_id_apoyo', $id_apoyo)
                    ->whereNotNull('google_calendar_event_id')
                    ->get();
            } elseif ($id_hito) {
                $hitos = HitosApoyo::where('id_hito', $id_hito)
                    ->whereNotNull('google_calendar_event_id')
                    ->get();
            } else {
                return false;
            }

            $directivos = $this->obtenerDirectivosActivos();

            foreach ($hitos as $hito) {
                foreach ($directivos as $permiso) {
                    try {
                        if ($permiso->tokenVencePronto()) {
                            $this->refrescarToken($permiso);
                        }

                        // Desencriptar y decodificar el token completo
                        $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
                        $this->googleClient->setAccessToken($tokenCompleto);

                        // Eliminar evento
                        $this->calendarService->events->delete(
                            $permiso->google_calendar_id,
                            $hito->google_calendar_event_id
                        );

                        // Registrar en log
                        $this->registrarCambioSincronizacion(
                            $hito->id_hito,
                            $hito->fk_id_apoyo,
                            'eliminacion',
                            'sigo',
                            ['evento_id' => $hito->google_calendar_event_id],
                            [],
                            auth()->id() ?? 1
                        );

                    } catch (\Exception $e) {
                        Log::error("GoogleCalendarService::eliminarEventosApoyo - Error: {$e->getMessage()}");
                    }
                }

                // Limpiar datos de Google en BD
                $hito->update([
                    'google_calendar_event_id' => null,
                    'google_calendar_sync' => false,
                ]);
            }

            return true;
        } catch (\Exception $e) {
            Log::error("GoogleCalendarService::eliminarEventosApoyo - Error general: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * FLUJO 4: Sincronizar cambios desde Google Calendar → SIGO
     * 
     * Se ejecuta cada X minutos (scheduler job)
     * Obtiene eventos modificados y actualiza BD
     * 
     * Acciones:
     * - Buscar eventos modificados desde última sincronización
     * - Comparar con BD
     * - Si cambios: actualizar fecha, estado, descripción en SIGO
     * - Solo si cambios hechos por propietario/editor autorizado
     * 
     * @param int $id_directivo
     * @return array ['cambios_procesados' => n, 'errores' => []]
     */
    public function sincronizarDesdeGoogle($id_directivo)
    {
        $resultado = [
            'cambios_procesados' => 0,
            'errores' => [],
        ];

        try {
            // Buscar el permiso por directivo_id, no por ID del permiso
            $permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', $id_directivo)
                ->where('activo', 1)
                ->first();

            if (!$permiso) {
                return $resultado; // Sin permiso activo, no hay nada que sincronizar
            }

            if (!$permiso->activo) {
                return $resultado;
            }

            if ($permiso->tokenVencePronto()) {
                $this->refrescarToken($permiso);
            }

            // Desencriptar y decodificar el token completo
            $tokenCompleto = json_decode(decrypt($permiso->google_access_token), true);
            $this->googleClient->setAccessToken($tokenCompleto);

            // Obtener eventos modificados desde última sincronización
            $optParams = [];
            if ($permiso->ultima_sincronizacion) {
                $optParams['updatedMin'] = $permiso->ultima_sincronizacion->toRfc3339String();
            }
            $optParams['orderBy'] = 'updated';
            $optParams['maxResults'] = 100;

            $events = $this->calendarService->events->listEvents(
                $permiso->google_calendar_id,
                $optParams
            );

            foreach ($events->getItems() as $evento) {
                try {
                    // Buscar hito local por ID de evento
                    $hito = HitosApoyo::where('google_calendar_event_id', $evento->getId())->first();

                    if (!$hito) {
                        continue; // No existe localmente
                    }

                    // Validar que cambio sea seguro
                    if (!$this->validarCambioDesdeGoogle($evento, $hito)) {
                        continue;
                    }

                    $cambios = [];
                    $datos_anteriores = $hito->toArray();

                    // Si fecha cambió en Google, reflejar en SIGO
                    if ($evento->getStart()) {
                        $nuevaFecha = Carbon::parse($evento->getStart()->getDateTime());
                        if ($hito->fecha_inicio->notEqualTo($nuevaFecha)) {
                            $hito->fecha_inicio = $nuevaFecha;
                            $cambios['fecha_inicio'] = $nuevaFecha;
                        }
                    }

                    if (!empty($cambios)) {
                        $hito->save();

                        // Registrar en log
                        $this->registrarCambioSincronizacion(
                            $hito->id_hito,
                            $hito->fk_id_apoyo,
                            'actualizacion',
                            'google',
                            $datos_anteriores,
                            $cambios,
                            $permiso->fk_id_directivo
                        );

                        $resultado['cambios_procesados']++;
                    }
                } catch (\Exception $e) {
                    $resultado['errores'][] = "Error al procesar evento: {$e->getMessage()}";
                    Log::error("GoogleCalendarService::sincronizarDesdeGoogle - Evento error: {$e->getMessage()}");
                }
            }

            // Actualizar timestamp de sincronización
            DB::table('directivos_calendario_permisos')
                ->where('id_permiso', $permiso->id_permiso)
                ->update(['ultima_sincronizacion' => DB::raw('GETDATE()')]);

        } catch (\Exception $e) {
            Log::error("GoogleCalendarService::sincronizarDesdeGoogle - Error general: {$e->getMessage()}");
            $resultado['errores'][] = $e->getMessage();
        }

        return $resultado;
    }

    /**
     * Helper: Autenticar OAuth 2.0
     * 
     * Flujo:
     * 1. Directivo hace click en "Conectar Google Calendar"
     * 2. Se redirige a Google consent screen
     * 3. Si aprueba, vuelve con auth_code
     * 4. Guardamos tokens en directivos_calendario_permisos
     * 
     * @param string $state - El estado CSRF generado en BD
     * @return string (URL de redirección a Google)
     */
    public function generarUrlAutenticacion($state = null)
    {
        try {
            Log::info("GoogleCalendarService::generarUrlAutenticacion - Iniciando con state: {$state}");
            
            // Configurar cliente Google con credenciales
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect');
            
            Log::info("GoogleCalendarService::generarUrlAutenticacion - Configurando cliente:");
            Log::info("  - CLIENT_ID: {$clientId}");
            Log::info("  - REDIRECT_URI: {$redirectUri}");
            
            $this->googleClient->setClientId($clientId);
            $this->googleClient->setClientSecret($clientSecret);
            $this->googleClient->setRedirectUri($redirectUri);
            $this->googleClient->setAccessType('offline');
            $this->googleClient->setApprovalPrompt('force');
            // Scopes necesarios: Calendar para eventos, Oauth2 para obtener email del usuario
            $this->googleClient->setScopes([
                Google_Service_Calendar::CALENDAR,
                Google_Service_Oauth2::USERINFO_EMAIL,
                Google_Service_Oauth2::OPENID,
            ]);
            
            // Si se proporciona un state personalizado, úsalo
            if ($state) {
                Log::info("GoogleCalendarService::generarUrlAutenticacion - Configurando state personalizado: {$state}");
                $this->googleClient->setState($state);
            }
            
            // Generar URL de autenticación con el state configurado
            $authUrl = $this->googleClient->createAuthUrl();
            Log::info("GoogleCalendarService::generarUrlAutenticacion - Auth URL generada (length: " . strlen($authUrl) . ")");
            
            return $authUrl;
        } catch (\Exception $e) {
            Log::error('Error al generar URL de autenticación: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Helper: Manejar callback de OAuth
     * 
     * Google redirige aquí con authorization_code
     * 
     * @param string $auth_code
     * @param int $id_directivo
     * @return bool (éxito)
     */
    public function manejarCallbackOAuth($auth_code, $id_directivo)
    {
        try {
            Log::info("GoogleCalendarService::manejarCallbackOAuth - Iniciando con auth_code length: " . strlen($auth_code) . ", directivo: {$id_directivo}");
            
            // Configurar cliente Google con credenciales ANTES de intercambiar el código
            $clientId = config('services.google.client_id');
            $clientSecret = config('services.google.client_secret');
            $redirectUri = config('services.google.redirect');
            
            Log::info("GoogleCalendarService::manejarCallbackOAuth - Configuración:");
            Log::info("  - CLIENT_ID: {$clientId}");
            Log::info("  - CLIENT_SECRET length: " . strlen($clientSecret));
            Log::info("  - REDIRECT_URI: {$redirectUri}");
            
            $this->googleClient->setClientId($clientId);
            $this->googleClient->setClientSecret($clientSecret);
            $this->googleClient->setRedirectUri($redirectUri);
            
            // Asegurar que los scopes están configurados para esta sesión
            $this->googleClient->setScopes([
                Google_Service_Calendar::CALENDAR,
                Google_Service_Oauth2::USERINFO_EMAIL,
                Google_Service_Oauth2::OPENID,
            ]);
            
            Log::info("GoogleCalendarService::manejarCallbackOAuth - Intercambiando auth_code por token...");
            
            // Intercambiar auth_code por access_token
            $token = $this->googleClient->fetchAccessTokenWithAuthCode($auth_code);
            
            Log::info("GoogleCalendarService::manejarCallbackOAuth - Response de Google: " . json_encode($token));
            
            if (isset($token['error'])) {
                throw new \Exception("OAuth error: " . json_encode($token['error']));
            }

            $this->googleClient->setAccessToken($token);

            // Obtener información del usuario (email, calendar_id)
            Log::info("GoogleCalendarService::manejarCallbackOAuth - Access Token establecido, creando servicio Oauth2...");
            $oauth2 = new \Google\Service\Oauth2($this->googleClient);
            
            Log::info("GoogleCalendarService::manejarCallbackOAuth - Obteniendo información de usuario...");
            $userInfo = $oauth2->userinfo->get();
            
            $email = $userInfo->getEmail();
            $calendarId = $email; // Calendar primario = email
            
            Log::info("GoogleCalendarService::manejarCallbackOAuth - Email del usuario: {$email}, Calendar ID: {$calendarId}");

            // Guardar tokens en BD - usar raw SQL para SQL Server compatibility
            $expiresIn = $token['expires_in'] ?? 3600;
            
            // Guardar el token COMPLETO encriptado (no solo access_token) para que Google Client pueda usarlo
            $tokenEncriptado = encrypt(json_encode($token));
            
            // Buscar si ya existe
            $permiso = DirectivoCalendarioPermiso::where('email_directivo', $email)->first();
            
            if ($permiso) {
                // Actualizar con raw SQL para SQL Server compatibility
                DB::table('directivos_calendario_permisos')
                    ->where('id_permiso', $permiso->id_permiso)
                    ->update([
                        'fk_id_directivo' => $id_directivo,
                        'google_calendar_id' => $calendarId,
                        'google_access_token' => $tokenEncriptado,
                        'google_refresh_token' => encrypt($token['refresh_token'] ?? ''),
                        'token_expiracion' => DB::raw("DATEADD(SECOND, {$expiresIn}, GETDATE())"),
                        'ultima_sincronizacion' => DB::raw('GETDATE()'),
                        'activo' => 1,
                    ]);
            } else {
                // Crear nuevo registro con raw SQL para SQL Server compatibility
                DB::table('directivos_calendario_permisos')->insert([
                    'fk_id_directivo' => $id_directivo,
                    'google_calendar_id' => $calendarId,
                    'google_access_token' => $tokenEncriptado,
                    'google_refresh_token' => encrypt($token['refresh_token'] ?? ''),
                    'token_expiracion' => DB::raw("DATEADD(SECOND, {$expiresIn}, GETDATE())"),
                    'email_directivo' => $email,
                    'calendarios_sincronizados' => 0,
                    'ultima_sincronizacion' => DB::raw('GETDATE()'),
                    'activo' => 1,
                    'created_at' => DB::raw('GETDATE()'),
                    'updated_at' => DB::raw('GETDATE()'),
                ]);
            }

            Log::info("GoogleCalendarService: Permisos de calendario conectados para {$email}");
            return true;
        } catch (\Exception $e) {
            Log::error("GoogleCalendarService::manejarCallbackOAuth - Error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Helper: Refrescar token OAuth automáticamente
     * 
     * Se ejecuta si token está a punto de expirar
     * Importante: ANTES de cualquier llamada a Google API
     * 
     * @param DirectivoCalendarioPermiso $permiso
     * @return bool
     */
    private function refrescarToken(DirectivoCalendarioPermiso $permiso)
    {
        try {
            // Si no necesita refresh, retornar
            if (!$permiso->tokenVencePronto()) {
                return true;
            }

            // Si no hay refresh_token, no se puede refrescar
            if (!$permiso->google_refresh_token) {
                Log::warning("GoogleCalendarService::refrescarToken - No refresh token available for {$permiso->email_directivo}");
                return false;
            }

            $refreshTokenDecrypted = decrypt($permiso->google_refresh_token);
            
            // Si el refresh token está vacío después de decrypt, no se puede usar
            if (empty($refreshTokenDecrypted)) {
                Log::warning("GoogleCalendarService::refrescarToken - Refresh token is empty for {$permiso->email_directivo}");
                return false;
            }

            $this->googleClient->setAccessToken([
                'refresh_token' => $refreshTokenDecrypted,
            ]);

            $newToken = $this->googleClient->fetchAccessTokenWithRefreshToken($refreshTokenDecrypted);

            if (isset($newToken['error'])) {
                throw new \Exception("Token refresh error: " . $newToken['error']);
            }

            // Guardar nuevos tokens - COMPLETO encriptado
            $expiresIn = $newToken['expires_in'] ?? 3600;
            $tokenEncriptado = encrypt(json_encode($newToken));
            
            DB::table('directivos_calendario_permisos')
                ->where('id_permiso', $permiso->id_permiso)
                ->update([
                    'google_access_token' => $tokenEncriptado,
                    'token_expiracion' => DB::raw("DATEADD(SECOND, {$expiresIn}, GETDATE())"),
                ]);

            Log::info("GoogleCalendarService: Token refrescado para {$permiso->email_directivo}");
            return true;
        } catch (\Exception $e) {
            Log::error("GoogleCalendarService::refrescarToken - Error: {$e->getMessage()}");
            return false;
        }
    }

    /**
     * Helper: Obtener color de evento según tipo de hito
     * 
     * Google Calendar API usa IDs de colores:
     * 1=Tomato, 2=Flamingo, ..., 11=Graphite
     * 
     * @param string $nombreHito (PUBLICACION, RECEPCION, ANALISIS_ADMIN, RESULTADOS, CIERRE)
     * @return string (color ID)
     */
    private function obtenerColorPorHito($nombreHito)
    {
        $colores = [
            'PUBLICACION' => '1',      // Tomato (rojo)
            'RECEPCION' => '2',        // Flamingo (rosa)
            'ANALISIS_ADMIN' => '5',   // Banana (amarillo)
            'RESULTADOS' => '6',       // Tangerine (naranja)
            'CIERRE' => '11',          // Graphite (gris oscuro)
        ];

        return $colores[$nombreHito] ?? '8'; // Gray por defecto
    }

    /**
     * Helper: Mapear estado de hito a descripción para Google
     * 
     * @param HitosApoyo $hito
     * @param Apoyos $apoyo
     * @return string (descripción en formato Markdown)
     */
    private function construirDescripcionEvento(HitosApoyo $hito, Apoyo $apoyo)
    {
        $descripcion = "";
        $descripcion .= "**APOYO:** {$apoyo->nombre_apoyo}\n";
        $descripcion .= "**TIPO:** " . ($apoyo->tipo_apoyo ?? 'N/A') . "\n";
        $descripcion .= "**MONTO:** \${$apoyo->monto_maximo}\n\n";
        $descripcion .= "**HITO:** {$hito->nombre_hito}\n";
        if ($hito->fecha_inicio) {
            $descripcion .= "**FECHA PREVISTA:** " . $hito->fecha_inicio->format('Y-m-d') . "\n";
        }
        $descripcion .= "\n";
        $descripcion .= "---\n";
        $descripcion .= "Administrado por: INJUVE Nayarit\n";
        $descripcion .= "Sistema SIGO\n";

        return $descripcion;
    }

    /**
     * Helper: Registrar evento de sincronización en logs
     * 
     * @param int $id_hito
     * @param int $id_apoyo
     * @param string $tipo_cambio (creacion|actualizacion|eliminacion)
     * @param string $origen (sigo|google)
     * @param array $datos_anteriores
     * @param array $datos_nuevos
     * @param int $usuario_id
     * @return CalendarioSincronizacionLog
     */
    private function registrarCambioSincronizacion(
        $id_hito, 
        $id_apoyo, 
        $tipo_cambio, 
        $origen, 
        $datos_anteriores, 
        $datos_nuevos,
        $usuario_id
    ) {
        // Usar DB::table()->insert() para evitar validación de Eloquent con DB::raw()
        $result = DB::table('calendario_sincronizacion_log')->insert([
            'fk_id_hito' => $id_hito,
            'fk_id_apoyo' => $id_apoyo,
            'tipo_cambio' => $tipo_cambio,
            'origen' => $origen,
            'datos_anteriores' => json_encode($datos_anteriores),
            'datos_nuevos' => json_encode($datos_nuevos),
            'usuario_id' => $usuario_id,
            'fecha_cambio' => DB::raw('GETDATE()'),
            'sincronizado' => $origen === 'sigo' ? 1 : 0,
        ]);
        return $result;
    }

    /**
     * Helper: Validar que cambio de Google sea seguro
     * 
     * Protecciones:
     * - Solo cambios por propietario/editores
     * - No permitir cambios maliciosos (borrar eventos, cambiar permisos)
     * - Validar que cambio sea lógico (fecha no en pasado, etc.)
     * 
     * @param Google_Service_Calendar_Event $evento
     * @param HitosApoyo $hito_local
     * @return bool
     */
    private function validarCambioDesdeGoogle($evento, $hito_local)
    {
        // No permitir eventos cancelados
        if ($evento->getStatus() === 'cancelled') {
            return false;
        }

        // Validar que fecha sea lógica (no en pasado lejano)
        try {
            if ($evento->getStart()) {
                $nuevaFecha = Carbon::parse($evento->getStart()->getDateTime());
                if ($nuevaFecha->isPast() && $nuevaFecha->diffInDays(now()) > 30) {
                    return false; // Fecha más de 30 días en el pasado
                }
            }
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Helper: Obtener todos los directivos activos con permisos
     * 
     * @return \Illuminate\Database\Eloquent\Collection
     */
    private function obtenerDirectivosActivos()
    {
        return DirectivoCalendarioPermiso::where('activo', 1)
            ->with('directivo')
            ->get();
    }

    /**
     * Helper: Desconectar Google Calendar
     * 
     * Revoca tokens y limpia BD
     * 
     * @param int $id_directivo
     * @return bool
     */
    public function desconectarCalendar($id_directivo)
    {
        try {
            $permiso = DirectivoCalendarioPermiso::where('fk_id_directivo', $id_directivo)->first();
            
            if (!$permiso) {
                return false;
            }

            // Actualizar estado a inactivo - usar 0 para SQL Server boolean
            DB::table('directivos_calendario_permisos')
                ->where('id_permiso', $permiso->id_permiso)
                ->update(['activo' => 0]);

            Log::info("GoogleCalendarService: Calendario desconectado para {$permiso->email_directivo}");
            return true;
        } catch (\Exception $e) {
            Log::error("GoogleCalendarService::desconectarCalendar - Error: {$e->getMessage()}");
            return false;
        }
    }
}
