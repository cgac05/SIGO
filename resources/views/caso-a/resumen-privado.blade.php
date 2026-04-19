@extends('layouts.app')

@section('title', 'Resumen de Estado - INJUVE')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-2xl mx-auto">

        <!-- Header -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Estado de tu Expediente</h1>
                    <p class="text-gray-600 mt-1">Información privada y confidencial</p>
                </div>
                <div class="text-right">
                    <p class="text-sm text-gray-500">Folio</p>
                    <p class="text-lg font-mono font-semibold text-indigo-600">{{ $folio ?? session('caso_a_folio') }}</p>
                </div>
            </div>

            <!-- Estado General -->
            <div class="bg-indigo-50 border-l-4 border-indigo-600 p-4 rounded">
                <p class="text-sm text-gray-600">Estado Actual</p>
                <div class="flex items-center gap-3 mt-2">
                    <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    <p class="text-lg font-semibold text-gray-900">Documentos Recibidos y Verificados</p>
                </div>
            </div>
        </div>

        <!-- Información de Apoyo -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                </svg>
                Información del Apoyo
            </h2>

            <div class="grid grid-cols-2 gap-4">
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm text-gray-600">Nombre del Apoyo</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">Kit de Útiles Escolares 2026</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm text-gray-600">Tipo</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">En Especie</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm text-gray-600">Monto / Valor</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">$500,000 MXN</p>
                </div>
                <div class="bg-gray-50 p-4 rounded">
                    <p class="text-sm text-gray-600">Estado del Proceso</p>
                    <p class="text-lg font-semibold text-green-600 mt-1">✓ Activo</p>
                </div>
            </div>
        </div>

        <!-- Documentos Cargados -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4 flex items-center gap-2">
                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                Documentos Verificados
            </h2>

            <div class="space-y-3">
                <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">Cédula de Identidad</p>
                            <p class="text-sm text-gray-600">Cargado: 28 de Marzo, 2026</p>
                        </div>
                    </div>
                    <span class="inline-block bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full font-semibold">Verificado</span>
                </div>

                <div class="flex items-center justify-between p-4 bg-green-50 border border-green-200 rounded-lg">
                    <div class="flex items-center gap-3">
                        <svg class="w-5 h-5 text-green-600" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M8 4a4 4 0 100 8 4 4 0 000-8zM2 8a6 6 0 1110.89 3.476l4.817 4.817a1 1 0 01-1.414 1.414l-4.816-4.816A6 6 0 012 8z" clip-rule="evenodd" />
                        </svg>
                        <div>
                            <p class="font-semibold text-gray-900">Comprobante de Domicilio</p>
                            <p class="text-sm text-gray-600">Cargado: 28 de Marzo, 2026</p>
                        </div>
                    </div>
                    <span class="inline-block bg-green-100 text-green-800 text-xs px-3 py-1 rounded-full font-semibold">Verificado</span>
                </div>
            </div>
        </div>

        <!-- Cronología de Hitos -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
                Timeline del Proceso
            </h2>

            <div class="space-y-4">
                <!-- Completado -->
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="w-1 h-12 bg-green-500"></div>
                    </div>
                    <div class="pt-1">
                        <p class="font-semibold text-gray-900">Publicación del Apoyo</p>
                        <p class="text-sm text-gray-600">15 de Marzo, 2026</p>
                    </div>
                </div>

                <!-- Completado -->
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="w-1 h-12 bg-green-500"></div>
                    </div>
                    <div class="pt-1">
                        <p class="font-semibold text-gray-900">Recepción de Solicitudes</p>
                        <p class="text-sm text-gray-600">Abierto hasta: 28 de Marzo, 2026</p>
                        <p class="text-sm text-green-600 font-semibold mt-1">✓ Tu solicitud fue recibida</p>
                    </div>
                </div>

                <!-- Completado -->
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="w-1 h-12 bg-yellow-500"></div>
                    </div>
                    <div class="pt-1">
                        <p class="font-semibold text-gray-900">Análisis Administrativo</p>
                        <p class="text-sm text-gray-600">Inicio: 29 de Marzo, 2026</p>
                        <p class="text-sm text-yellow-600 font-semibold mt-1">⏳ En proceso - Documentos verificados</p>
                    </div>
                </div>

                <!-- Pendiente -->
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="w-1 h-12 bg-gray-300"></div>
                    </div>
                    <div class="pt-1">
                        <p class="font-semibold text-gray-900">Publicación de Resultados</p>
                        <p class="text-sm text-gray-600">Previsto: 10 de Abril, 2026</p>
                    </div>
                </div>

                <!-- Pendiente Final -->
                <div class="flex gap-4">
                    <div class="flex flex-col items-center">
                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                    </div>
                    <div class="pt-1">
                        <p class="font-semibold text-gray-900">Cierre y Entrega</p>
                        <p class="text-sm text-gray-600">Previsto: 30 de Abril, 2026</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-4 mb-6">
            <button 
                type="button"
                onclick="window.print()"
                class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-900 font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4H9a2 2 0 00-2 2v2a2 2 0 002 2h4a2 2 0 002-2v-2a2 2 0 00-2-2m-4-4V9m4 0V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4"></path>
                </svg>
                Imprimir
            </button>
            <form action="{{ route('logout') }}" method="POST" class="flex-1">
                @csrf
                <button 
                    type="submit"
                    class="w-full bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                    </svg>
                    Cerrar Sesión
                </button>
            </form>
        </div>

        <!-- Nota Importante -->
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <p class="text-sm text-gray-700">
                <strong class="text-yellow-900">⚠️ Nota Importante:</strong> Esta sesión privada expirará en <strong>30 minutos</strong> por razones de seguridad. 
                Por tu privacidad, cierra esta sesión cuando termines.
            </p>
        </div>

    </div>
</div>
@endsection
