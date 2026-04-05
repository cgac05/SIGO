@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.verificacion.formulario', $desembolso->id_historico) }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver a Verificación
            </a>
            <h1 class="text-3xl font-bold text-gray-900">📋 Reporte de Cumplimiento LGPDP</h1>
            <p class="text-gray-600 mt-1">Folio: <strong>{{ $desembolso->fk_folio }}</strong></p>
        </div>

        <!-- Puntuación General -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <div class="text-center">
                <div class="text-6xl font-bold mb-2 
                    @if($cumplimiento['cumplimiento_score'] >= 95)
                        text-green-600
                    @elseif($cumplimiento['cumplimiento_score'] >= 75)
                        text-blue-600
                    @elseif($cumplimiento['cumplimiento_score'] >= 50)
                        text-yellow-600
                    @else
                        text-red-600
                    @endif
                ">{{ $cumplimiento['cumplimiento_score'] }}/100</div>
                <p class="text-xl font-bold mb-2">
                    <span class="inline-block px-4 py-2 rounded-lg 
                        @if($cumplimiento['cumplimiento_score'] >= 95)
                            bg-green-100 text-green-800
                        @elseif($cumplimiento['cumplimiento_score'] >= 75)
                            bg-blue-100 text-blue-800
                        @elseif($cumplimiento['cumplimiento_score'] >= 50)
                            bg-yellow-100 text-yellow-800
                        @else
                            bg-red-100 text-red-800
                        @endif
                    ">
                        🏆 {{ $cumplimiento['nivel_cumplimiento'] }}
                    </span>
                </p>
                <p class="text-gray-600">Evaluación de conformidad con LGPDP</p>
            </div>
        </div>

        <!-- Resumen General -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📊 Resumen General</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-blue-50 border-l-4 border-blue-500 p-4">
                    <p class="text-sm text-gray-700">Total de Criterios</p>
                    <p class="text-2xl font-bold text-blue-600">{{ $cumplimiento['resumen']['total_criterios'] }}</p>
                </div>
                <div class="bg-green-50 border-l-4 border-green-500 p-4">
                    <p class="text-sm text-gray-700">Criterios Cumplidos</p>
                    <p class="text-2xl font-bold text-green-600">{{ $cumplimiento['resumen']['criterios_cumplidos'] }}</p>
                </div>
                <div class="bg-purple-50 border-l-4 border-purple-500 p-4">
                    <p class="text-sm text-gray-700">Porcentaje</p>
                    <p class="text-2xl font-bold text-purple-600">
                        @php
                            $porcentaje = ($cumplimiento['resumen']['criterios_cumplidos'] / $cumplimiento['resumen']['total_criterios']) * 100;
                        @endphp
                        {{ round($porcentaje, 1) }}%
                    </p>
                </div>
            </div>
        </div>

        <!-- Criterios Detallados -->
        <div class="space-y-4 mb-6">
            @foreach($cumplimiento['detalles'] as $criterio_key => $criterio)
            <div class="bg-white rounded-lg shadow p-6 border-l-4 
                @if($criterio['cumple']) border-green-500 @else border-red-500 @endif
            ">
                <div class="flex items-start justify-between mb-3">
                    <div>
                        <h3 class="text-lg font-bold text-gray-900">{{ $criterio['criterio'] }}</h3>
                        <p class="text-sm text-gray-600 mt-1">{{ $criterio['descripcion'] }}</p>
                    </div>
                    <div>
                        <span class="inline-block px-3 py-1 rounded-full text-sm font-bold 
                            @if($criterio['cumple'])
                                bg-green-100 text-green-800
                            @else
                                bg-red-100 text-red-800
                            @endif
                        ">
                            @if($criterio['cumple'])
                                ✓ CUMPLE
                            @else
                                ✗ NO CUMPLE
                            @endif
                        </span>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded mt-3">
                    <p class="text-sm"><strong>Evidencia:</strong> {{ $criterio['evidencia'] }}</p>
                    <p class="text-sm text-gray-600 mt-2"><strong>Puntuación:</strong> {{ $criterio['puntuacion'] }}/25 puntos</p>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Matriz de Cumplimiento -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📈 Matriz de Cumplimiento</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-2 text-left text-sm font-medium text-gray-700">Criterio</th>
                            <th class="px-4 py-2 text-center text-sm font-medium text-gray-700">Estado</th>
                            <th class="px-4 py-2 text-right text-sm font-medium text-gray-700">Puntuación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($cumplimiento['detalles'] as $criterio_key => $criterio)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-3 text-sm text-gray-900">{{ $criterio['criterio'] }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-block px-2 py-1 rounded text-xs font-bold 
                                    @if($criterio['cumple'])
                                        bg-green-100 text-green-800
                                    @else
                                        bg-red-100 text-red-800
                                    @endif
                                ">
                                    @if($criterio['cumple']) ✓ @else ✗ @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm font-bold text-gray-900">
                                {{ $criterio['puntuacion'] }}/25
                            </td>
                        </tr>
                        @endforeach
                        <tr class="bg-gray-100 font-bold">
                            <td class="px-4 py-3 text-sm">TOTAL</td>
                            <td class="px-4 py-3 text-center">-</td>
                            <td class="px-4 py-3 text-right text-sm">{{ $cumplimiento['cumplimiento_score'] }}/100</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Recomendaciones -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg mb-6">
            <h2 class="text-lg font-bold text-blue-900 mb-3">💡 Recomendaciones</h2>
            <div class="text-sm text-blue-800 space-y-2">
                @if($cumplimiento['cumplimiento_score'] >= 95)
                    <p>✓ Este certificado cumple completamente con los requisitos LGPDP.</p>
                    <p>Se recomienda mantener los procedimientos actuales y realizar revisiones periódicas para asegurar cumplimiento continuo.</p>
                @elseif($cumplimiento['cumplimiento_score'] >= 75)
                    <p>✓ Este certificado cumple adecuadamente con los requisitos LGPDP.</p>
                    <p>Se sugiere revisar los criterios con puntuaciones más bajas y realizar mejoras para alcanzar el máximo cumplimiento.</p>
                @elseif($cumplimiento['cumplimiento_score'] >= 50)
                    <p>⚠ Este certificado requiere mejoras en ciertos criterios de LGPDP.</p>
                    <p>Se recomienda una auditoría complementaria y la implementación de acciones correctivas en los criterios identificados.</p>
                @else
                    <p>✗ Este certificado necesita mejoras significativas en cumplimiento LGPDP.</p>
                    <p>Se requiere acción correctiva urgente. Revise inmediatamente los criterios no cumplidos y implemente medidas correctivas.</p>
                @endif
            </div>
        </div>

        <!-- Información LGPDP -->
        <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg mb-6">
            <h2 class="text-lg font-bold text-green-900 mb-3">🔐 Principios LGPDP Evaluados</h2>
            <ul class="text-sm text-green-800 space-y-2">
                <li>✓ <strong>Integridad de Datos:</strong> Verificación SHA-256 de datos almacenados</li>
                <li>✓ <strong>Trazabilidad:</strong> Registro completo de eventos y cambios</li>
                <li>✓ <strong>Información Completa:</strong> Datos del beneficiario registrados correctamente</li>
                <li>✓ <strong>Propósito Legítimo:</strong> Desembolso vinculado a programa de apoyo válido</li>
            </ul>
        </div>

        <!-- Botones de Descarga -->
        <div class="flex gap-3">
            <a href="{{ route('certificacion.verificacion.descargar-cumplimiento', $desembolso->id_historico) }}" 
                class="inline-block bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600 font-medium">
                📄 Descargar PDF
            </a>
            <a href="{{ route('certificacion.verificacion.formulario', $desembolso->id_historico) }}" 
                class="inline-block bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500 font-medium">
                ← Volver
            </a>
        </div>
    </div>
</div>
@endsection
