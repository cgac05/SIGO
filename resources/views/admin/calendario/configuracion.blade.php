@extends('layouts.app')

@section('title', 'Configuración Google Calendar - SIGO')

@section('content')
<div class="container mx-auto py-12 px-4">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Google Calendar</h1>
        <p class="text-lg text-gray-600 mt-2">Integración y Sincronización</p>
        <p class="text-sm text-gray-500 mt-1">Sincroniza automáticamente los hitos de apoyos con tu Google Calendar</p>
    </div>

    <div class="grid grid-cols-3 gap-8">
        
        <!-- Columna Izq: Estado de Conexión -->
        <div class="col-span-3 lg:col-span-1">
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6">Estado de Conexión</h2>

                @if($permiso && $permiso->activo)
                    <!-- Conectado -->
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200 mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-4 h-4 bg-green-500 rounded-full animate-pulse"></div>
                            <span class="font-semibold text-green-900">Conectado</span>
                        </div>
                        <div class="space-y-3">
                            <div>
                                <p class="text-xs text-gray-600">Email de Google</p>
                                <p class="font-semibold text-gray-900">{{ $permiso->email_directivo }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Última Sincronización</p>
                                <p class="font-semibold text-gray-900">
                                    @if($permiso->ultima_sincronizacion)
                                        {{ $permiso->ultima_sincronizacion->diffForHumans() }}
                                    @else
                                        Pendiente
                                    @endif
                                </p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-600">Apoyos Sincronizados</p>
                                <p class="font-semibold text-gray-900">{{ $permiso->calendarios_sincronizados ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="space-y-3 mb-6">
                        <form action="{{ route('calendario.sync') }}" method="POST">
                            @csrf
                            <button 
                                type="submit"
                                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                </svg>
                                Sincronizar Ahora
                            </button>
                        </form>
                        
                        <form action="{{ route('calendario.disconnect') }}" method="POST">
                            @csrf
                            <button 
                                type="submit"
                                class="w-full bg-red-100 hover:bg-red-200 text-red-900 font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                            >
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                                </svg>
                                Desconectar
                            </button>
                        </form>
                    </div>
                @else
                    <!-- Desconectado -->
                    <div class="bg-gray-50 p-4 rounded-lg border border-gray-200 mb-6">
                        <div class="flex items-center gap-3 mb-4">
                            <div class="w-4 h-4 bg-gray-400 rounded-full"></div>
                            <span class="font-semibold text-gray-700">Desconectado</span>
                        </div>
                        <p class="text-sm text-gray-600">
                            Conecta tu Google Calendar para sincronizar automáticamente los hitos de los apoyos.
                        </p>
                    </div>

                    <!-- Botón de Conexión -->
                    <a 
                        href="{{ route('calendario.auth') }}"
                        class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2 mb-6"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M12.545,10.852v3.286h5.519c-0.75,2.069-2.306,3.795-4.897,3.795c-2.866,0-5.289-2.422-5.289-5.289 c0-2.866,2.422-5.289,5.289-5.289c1.289,0,2.469,0.543,3.334,1.459l2.334-2.334C15.946,2.697,14.146,0.5,12.545,0.5 c-6.588,0-11.952,5.364-11.952,11.952s5.364,11.952,11.952,11.952c3.543,0,6.651-1.604,8.806-4.155 c2.082-2.451,3.367-5.807,3.367-9.797c0-0.718-0.109-1.454-0.272-2.115H12.545z"/>
                        </svg>
                        Conectar Google Calendar
                    </a>
                @endif

                <!-- Enlaces Rápidos -->
                <div class="border-t border-gray-200 pt-4">
                    <p class="text-sm font-semibold text-gray-700 mb-3">Enlaces Rápidos</p>
                    <ul class="space-y-2">
                        <li>
                            <a href="{{ route('calendario.logs') }}" class="text-indigo-600 hover:underline text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path>
                                </svg>
                                Ver Logs
                            </a>
                        </li>
                        <li>
                            <a href="https://calendar.google.com" target="_blank" class="text-indigo-600 hover:underline text-sm flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                                </svg>
                                Ver en Google Calendar
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Columna Der: Información y Configuración -->
        <div class="col-span-3 lg:col-span-2 space-y-8">
            
            <!-- Información de Sincronización -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Cómo Funciona la Sincronización
                </h2>

                <div class="space-y-6">
                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100">
                                <span class="text-indigo-600 font-bold">1</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Creación Automática de Eventos</h3>
                            <p class="text-gray-600 text-sm mt-1">
                                Cuando se crea un apoyo, se generan automáticamente eventos en tu Google Calendar para cada hito (Publicación, Recepción, Análisis, Resultados, Cierre).
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100">
                                <span class="text-indigo-600 font-bold">2</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Sincronización Bidireccional</h3>
                            <p class="text-gray-600 text-sm mt-1">
                                Los cambios en SIGO se reflejan en Google Calendar, y viceversa. Puedes editar fechas desde tu calendario y SIGO se actualizará automáticamente.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100">
                                <span class="text-indigo-600 font-bold">3</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Recordatorios Automáticos</h3>
                            <p class="text-gray-600 text-sm mt-1">
                                Recibirás notificaciones 3 días antes de cada hito importante para que nunca olvides los plazos.
                            </p>
                        </div>
                    </div>

                    <div class="flex gap-4">
                        <div class="flex-shrink-0">
                            <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-indigo-100">
                                <span class="text-indigo-600 font-bold">4</span>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-semibold text-gray-900">Auditoría Completa</h3>
                            <p class="text-gray-600 text-sm mt-1">
                                Todos los cambios de sincronización se registran en logs para mantener trazabilidad e identificar discrepancias.
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Datos Compartidos -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Información Compartida</h2>

                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-6">
                    <p class="text-sm text-blue-900">
                        <strong>🔒 Privacidad:</strong> Solo se comparte información pública del apoyo (nombre, fechas, hitos). 
                        No se comparten datos personales de beneficiarios ni información sensible.
                    </p>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs text-gray-600 mb-1">Información que SÍ se comparte</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✓ Nombre del apoyo</li>
                            <li>✓ Tipo (Económico/Especie)</li>
                            <li>✓ Fechas de hitos</li>
                            <li>✓ Estado del proceso</li>
                            <li>✓ Cantidad de solicitudes</li>
                        </ul>
                    </div>
                    <div class="bg-gray-50 p-4 rounded-lg">
                        <p class="text-xs text-gray-600 mb-1">Información que NO se comparte</p>
                        <ul class="text-sm text-gray-700 space-y-1">
                            <li>✗ Nombres de beneficiarios</li>
                            <li>✗ Datos bancarios</li>
                            <li>✗ Documentos personales</li>
                            <li>✗ Información de contacto</li>
                            <li>✗ Detalles de ingresos</li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Preguntas Frecuentes -->
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Preguntas Frecuentes</h2>

                <div class="space-y-4">
                    <details class="group">
                        <summary class="cursor-pointer font-semibold text-gray-900 select-none">
                            ¿Cada cuánto se sincroniza?
                        </summary>
                        <p class="text-gray-600 text-sm mt-2 group-open:block hidden">
                            La sincronización se ejecuta automáticamente cada 60 minutos. También puedes sincronizar manualmente haciendo click en "Sincronizar Ahora".
                        </p>
                    </details>

                    <details class="group">
                        <summary class="cursor-pointer font-semibold text-gray-900 select-none">
                            ¿Puedo editar eventos en Google Calendar?
                        </summary>
                        <p class="text-gray-600 text-sm mt-2 group-open:block hidden">
                            Sí, puedes editar fechas en Google Calendar y los cambios se sincronizarán a SIGO automáticamente.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="cursor-pointer font-semibold text-gray-900 select-none">
                            ¿Qué pasa si hay un conflicto entre SIGO y Google?
                        </summary>
                        <p class="text-gray-600 text-sm mt-2 group-open:block hidden">
                            SIGO tiene prioridad. Si hay conflictos, se registran en logs y se notifica al administrador.
                        </p>
                    </details>

                    <details class="group">
                        <summary class="cursor-pointer font-semibold text-gray-900 select-none">
                            ¿Puedo desconectar en cualquier momento?
                        </summary>
                        <p class="text-gray-600 text-sm mt-2 group-open:block hidden">
                            Sí, haz click en "Desconectar" para revocar permisos de acceso a Google Calendar. Los eventos existentes no se eliminarán.
                        </p>
                    </details>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
