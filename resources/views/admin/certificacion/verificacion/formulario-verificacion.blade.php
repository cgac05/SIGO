@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.verificacion.dashboard') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver al Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">🔐 Verificación de Certificado</h1>
            <p class="text-gray-600 mt-1">Folio: <strong>{{ $desembolso->fk_folio }}</strong></p>
        </div>

        <!-- Información del Certificado -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Información del Certificado</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Folio</p>
                    <p class="text-lg font-bold text-gray-900">{{ $desembolso->fk_folio }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Monto Entregado</p>
                    <p class="text-lg font-bold text-green-600">${{ number_format($desembolso->monto_entregado, 2) }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Beneficiario</p>
                    <p class="text-lg text-gray-900">{{ $desembolso->solicitud->beneficiario->display_name ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Estado Actual</p>
                    <p class="text-lg">
                        <span class="inline-block px-3 py-1 rounded-full 
                            @if($desembolso->estado_certificacion === 'VALIDADO')
                                bg-green-100 text-green-800
                            @elseif($desembolso->estado_certificacion === 'CERTIFICADO')
                                bg-blue-100 text-blue-800
                            @else
                                bg-yellow-100 text-yellow-800
                            @endif
                        ">
                            {{ $desembolso->estado_certificacion ?? 'Sin estado' }}
                        </span>
                    </p>
                </div>
            </div>
        </div>

        <!-- VALIDACIONES -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-xl font-bold text-gray-900">✓ Resultados de Validación</h2>
            </div>

            <div class="p-6">
                @php
                    $validaciones = $validacion['validaciones'];
                    $resultado = $validacion['resultado_general'] ? 'VÁLIDO' : 'CON ALERTAS';
                    $color_resultado = $validacion['resultado_general'] ? 'green' : 'yellow';
                @endphp

                <!-- Estado General -->
                <div class="mb-6 p-4 rounded-lg 
                    @if($validacion['resultado_general'])
                        bg-green-50 border-2 border-green-200
                    @else
                        bg-yellow-50 border-2 border-yellow-200
                    @endif
                ">
                    <p class="text-sm font-medium 
                        @if($validacion['resultado_general'])
                            text-green-700
                        @else
                            text-yellow-700
                        @endif
                    ">RESULTADO GENERAL</p>
                    <p class="text-2xl font-bold 
                        @if($validacion['resultado_general'])
                            text-green-600
                        @else
                            text-yellow-600
                        @endif
                    ">{{ $resultado }}</p>
                </div>

                <!-- Validaciones Individuales -->
                <div class="space-y-4">
                    @foreach($validaciones as $clave => $validacion_item)
                    <div class="border-l-4 p-4 rounded
                        @if($validacion_item['valido'])
                            border-green-500 bg-green-50
                        @else
                            border-red-500 bg-red-50
                        @endif
                    ">
                        <div class="flex items-start">
                            <div class="mt-1">
                                @if($validacion_item['valido'])
                                    <span class="text-2xl">✓</span>
                                @else
                                    <span class="text-2xl">✗</span>
                                @endif
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="font-bold text-gray-900">{{ ucfirst(str_replace('_', ' ', $clave)) }}</p>
                                <p class="text-sm text-gray-700 mt-1">{{ $validacion_item['mensaje'] }}</p>
                                
                                @if($clave === 'integridad')
                                    <div class="mt-3 bg-gray-100 p-3 rounded font-mono text-xs">
                                        <p class="text-gray-600 mb-1">Hash Almacenado: {{ $validacion_item['hash_servidor'] }}</p>
                                        <p class="text-gray-600">Hash Verificado: {{ $validacion_item['hash_verificado'] }}</p>
                                    </div>
                                @elseif($clave === 'montos')
                                    <p class="mt-2 text-sm text-gray-700">Monto: <strong>${{ number_format($validacion_item['monto'], 2) }}</strong></p>
                                @elseif($clave === 'fechas')
                                    <div class="mt-2 text-sm text-gray-700">
                                        <p>Entrega: {{ $validacion_item['fecha_entrega'] }}</p>
                                        <p>Certificación: {{ $validacion_item['fecha_certificacion'] }}</p>
                                    </div>
                                @else
                                    <p class="mt-2 text-sm text-gray-700">
                                        @forelse($validacion_item as $key => $value)
                                            @if(!in_array($key, ['valido', 'mensaje']))
                                                <span class="block">{{ ucfirst($key) }}: <strong>{{ $value }}</strong></span>
                                            @endif
                                        @empty
                                        @endforelse
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- INTEGRIDAD HASH -->
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🔒 Verificación de Hash SHA-256</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600 font-medium mb-2">Hash Almacenado</p>
                    <div class="bg-gray-100 p-3 rounded font-mono text-xs break-all border border-gray-300">
                        {{ $integridad['hash_almacenado'] }}
                    </div>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium mb-2">Hash Calculado</p>
                    <div class="bg-gray-100 p-3 rounded font-mono text-xs break-all border border-gray-300">
                        {{ $integridad['hash_calculado'] }}
                    </div>
                </div>
            </div>
            <div class="mt-4 p-3 rounded 
                @if($integridad['integridad_valida'])
                    bg-green-50 border-l-4 border-green-500
                @else
                    bg-red-50 border-l-4 border-red-500
                @endif
            ">
                <p class="font-bold 
                    @if($integridad['integridad_valida'])
                        text-green-700
                    @else
                        text-red-700
                    @endif
                ">
                    @if($integridad['integridad_valida'])
                        ✓ Certificado Íntegro - No hay alteraciones
                    @else
                        ✗ ALERTA: Hash no coincide - Posible alteración
                    @endif
                </p>
            </div>
        </div>

        <!-- CADENA DE CUSTODIA -->
        @if($auditoria['auditorias'] && $auditoria['auditorias']->count() > 0)
        <div class="bg-white rounded-lg shadow mb-6 p-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">🔗 Cadena de Custodia</h2>
            <div class="space-y-3">
                @foreach($auditoria['auditorias']->take(5) as $auditoria_item)
                <div class="border-l-4 border-blue-500 pl-4 py-2">
                    <p class="font-bold text-gray-900">{{ $auditoria_item['tipo_evento'] }}</p>
                    <p class="text-sm text-gray-600">{{ $auditoria_item['fecha'] }}</p>
                    @if(isset($auditoria_item['usuario_validador']))
                        <p class="text-sm text-gray-600">User: {{ $auditoria_item['usuario_validador'] }}</p>
                    @endif
                </div>
                @endforeach
            </div>
            @if($auditoria['auditorias']->count() > 5)
            <a href="{{ route('certificacion.verificacion.auditoria', $desembolso->id_historico) }}" class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                Ver auditoría completa ({{ $auditoria['auditorias']->count() }} eventos)
            </a>
            @endif
        </div>
        @endif

        <!-- BOTONES DE ACCIÓN -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📁 Descargar Reportes</h2>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('certificacion.verificacion.descargar-reporte', $desembolso->id_historico) }}" 
                    class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    📄 Reporte de Validación (PDF)
                </a>
                <a href="{{ route('certificacion.verificacion.reporte-cumplimiento', $desembolso->id_historico) }}" 
                    class="inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    ✓ Cumplimiento LGPDP
                </a>
                <a href="{{ route('certificacion.verificacion.auditoria', $desembolso->id_historico) }}" 
                    class="inline-block bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    🔗 Ver Auditoría Completa
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
