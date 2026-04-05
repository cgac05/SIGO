@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.archivado.gestor') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver al Gestor
            </a>
            <h1 class="text-3xl font-bold text-gray-900">📋 Historial de Versiones</h1>
            <p class="text-gray-600 mt-1">Folio: <strong>{{ $desembolso->fk_folio }}</strong></p>
        </div>

        <!-- Resumen -->
        @if($resultado['exito'])
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📊 Resumen</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                    <p class="text-sm text-gray-700">Total de Versiones</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $resultado['total_versiones'] }}</p>
                </div>
                <div class="bg-green-50 border-l-4 border-green-500 p-4">
                    <p class="text-sm text-gray-700">Primera Versión</p>
                    <p class="text-lg text-green-600">{{ $resultado['versiones']->count() > 0 ? $resultado['versiones']->last()['fecha'] : 'N/A' }}</p>
                </div>
                <div class="bg-purple-50 border-l-4 border-purple-500 p-4">
                    <p class="text-sm text-gray-700">Última Actualización</p>
                    <p class="text-lg text-purple-600">{{ $resultado['versiones']->count() > 0 ? $resultado['versiones']->first()['fecha'] : 'N/A' }}</p>
                </div>
            </div>
        </div>

        <!-- Timeline de Versiones -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-bold text-gray-900">⏱️ Línea de Tiempo</h2>
            </div>

            <div class="p-6">
                @if($resultado['versiones']->count() > 0)
                <div class="relative">
                    <!-- Línea vertical -->
                    <div class="absolute left-8 top-0 bottom-0 w-1 bg-purple-200"></div>

                    <!-- Eventos -->
                    <div class="space-y-8 relative">
                        @foreach($resultado['versiones'] as $version)
                        <div class="ml-20">
                            <!-- Punto del timeline -->
                            <div class="absolute -left-5 mt-1 w-10 h-10 bg-purple-500 border-4 border-white rounded-full flex items-center justify-center text-white text-sm">
                                📝
                            </div>

                            <!-- Contenido -->
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="font-bold text-gray-900">v{{ $version['numero'] }}</h3>
                                        <p class="text-sm font-medium text-purple-600">{{ $version['tipo_cambio'] }}</p>
                                    </div>
                                    <span class="text-sm text-gray-600">{{ $version['fecha'] }}</span>
                                </div>

                                <p class="text-sm text-gray-700 mb-3">{{ $version['descripcion'] }}</p>

                                <div class="text-sm text-gray-600">
                                    <p><strong>Usuario:</strong> {{ $version['usuario'] }}</p>
                                </div>

                                @if(is_array($version['datos']) && count($version['datos']) > 0)
                                <div class="mt-3 bg-white p-3 rounded border border-gray-300 font-mono text-xs">
                                    <p class="font-bold text-gray-900 mb-2">Estado del certificado:</p>
                                    @foreach($version['datos'] as $key => $value)
                                        <p class="text-gray-700">
                                            <strong>{{ str_replace('_', ' ', ucfirst($key)) }}:</strong> 
                                            @if(is_array($value))
                                                {{ json_encode($value) }}
                                            @else
                                                {{ $value }}
                                            @endif
                                        </p>
                                    @endforeach
                                </div>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
                @else
                <div class="text-center py-8 text-gray-500">
                    <p>No hay versiones registradas para este certificado</p>
                </div>
                @endif
            </div>
        </div>
        @else
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded">
            <p class="text-yellow-700 font-medium">⚠️ {{ $resultado['razon'] }}</p>
        </div>
        @endif

        <!-- Botón Volver -->
        <div class="mt-6">
            <a href="{{ route('certificacion.archivado.gestor') }}" class="inline-block bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500">
                ← Volver al Gestor
            </a>
        </div>
    </div>
</div>
@endsection
