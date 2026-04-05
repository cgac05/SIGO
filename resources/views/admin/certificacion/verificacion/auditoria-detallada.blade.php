@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.verificacion.formulario', $desembolso->id_historico) }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver a Verificación
            </a>
            <h1 class="text-3xl font-bold text-gray-900">🔗 Cadena de Custodia Detallada</h1>
            <p class="text-gray-600 mt-1">Folio: <strong>{{ $desembolso->fk_folio }}</strong></p>
        </div>

        @if($auditoria['auditorias'] && $auditoria['auditorias']->count() > 0)
        <!-- Resumen -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📊 Resumen de Auditoría</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                    <p class="text-sm text-gray-700">Total de Eventos</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $auditoria['resumen']['total_eventos'] }}</p>
                </div>
                <div class="bg-green-50 border-l-4 border-green-500 p-4">
                    <p class="text-sm text-gray-700">Verificaciones de Integridad</p>
                    <p class="text-2xl font-bold text-green-600">{{ $auditoria['resumen']['verificaciones_integridad'] }}</p>
                </div>
                <div class="bg-purple-50 border-l-4 border-purple-500 p-4">
                    <p class="text-sm text-gray-700">Validaciones</p>
                    <p class="text-2xl font-bold text-purple-600">{{ $auditoria['resumen']['validaciones'] }}</p>
                </div>
                <div class="bg-orange-50 border-l-4 border-orange-500 p-4">
                    <p class="text-sm text-gray-700">Cambios de Estado</p>
                    <p class="text-2xl font-bold text-orange-600">{{ $auditoria['resumen']['cambios_estado'] }}</p>
                </div>
            </div>
        </div>

        <!-- Información del Certificado -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📋 Información del Certificado</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Creado</p>
                    <p class="text-gray-900">{{ $auditoria['resumen']['certificado_creado'] }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Última Verificación</p>
                    <p class="text-gray-900">{{ $auditoria['resumen']['ultima_verificacion'] ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Estado Actual</p>
                    <p class="text-gray-900">
                        <span class="inline-block px-3 py-1 rounded-full 
                            @if($auditoria['resumen']['estado_actual'] === 'VALIDADO')
                                bg-green-100 text-green-800
                            @elseif($auditoria['resumen']['estado_actual'] === 'CERTIFICADO')
                                bg-blue-100 text-blue-800
                            @else
                                bg-yellow-100 text-yellow-800
                            @endif
                        ">
                            {{ $auditoria['resumen']['estado_actual'] }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Timeline de Auditoría -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-900">⏱️ Línea de Tiempo de Eventos</h2>
            </div>

            <div class="p-6">
                <div class="relative">
                    <!-- Línea vertical del timeline -->
                    <div class="absolute left-8 top-0 bottom-0 w-1 bg-blue-200"></div>

                    <!-- Eventos -->
                    <div class="space-y-8 relative">
                        @foreach($auditoria['auditorias'] as $evento)
                        <div class="ml-20">
                            <!-- Punto del timeline -->
                            <div class="absolute -left-5 mt-1 w-10 h-10 bg-blue-500 border-4 border-white rounded-full flex items-center justify-center text-white text-sm">
                                <span>
                                    @if(str_contains($evento['tipo_evento'], 'INTEGRIDAD'))
                                        🔐
                                    @elseif(str_contains($evento['tipo_evento'], 'VALIDACION'))
                                        ✓
                                    @elseif(str_contains($evento['tipo_evento'], 'ESTADO'))
                                        📝
                                    @else
                                        📋
                                    @endif
                                </span>
                            </div>

                            <!-- Contenido del evento -->
                            <div class="bg-gray-50 rounded-lg p-4 border border-gray-200">
                                <div class="flex justify-between items-start mb-2">
                                    <h3 class="font-bold text-gray-900">{{ $evento['tipo_evento'] }}</h3>
                                    <span class="text-sm text-gray-600">{{ $evento['fecha'] }}</span>
                                </div>

                                <!-- Detalles -->
                                <div class="space-y-1 text-sm text-gray-700">
                                    @if(isset($evento['usuario_validador']))
                                        <p><strong>Usuario:</strong> {{ $evento['usuario_validador'] }}</p>
                                    @endif

                                    @if(isset($evento['ip_terminal']))
                                        <p><strong>IP Terminal:</strong> {{ $evento['ip_terminal'] }}</p>
                                    @endif

                                    @if(is_array($evento['detalles']) && count($evento['detalles']) > 0)
                                        <div class="mt-3 bg-white p-2 rounded border border-gray-300 font-mono text-xs">
                                            @foreach($evento['detalles'] as $key => $value)
                                                @if(!is_array($value))
                                                    <p><strong>{{ $key }}:</strong> {{ $value }}</p>
                                                @else
                                                    <p><strong>{{ $key }}:</strong> {{ json_encode($value) }}</p>
                                                @endif
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Descarga -->
        <div class="mt-6 flex gap-3">
            <a href="{{ route('certificacion.verificacion.descargar-reporte', $desembolso->id_historico) }}" 
                class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                📄 Descargar Reporte de Validación
            </a>
            <a href="{{ route('certificacion.verificacion.formulario', $desembolso->id_historico) }}" 
                class="inline-block bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">
                ← Volver
            </a>
        </div>

        @else
        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-6 rounded">
            <p class="text-yellow-700 font-medium">⚠️ No hay eventos de auditoría registrados para este certificado.</p>
        </div>
        @endif
    </div>
</div>
@endsection
