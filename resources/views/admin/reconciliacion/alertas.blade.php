@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Alertas y Discrepancias</h1>
            <p class="text-gray-600 mt-1">Validación de inconsistencias presupuestarias</p>
        </div>
        <a href="{{ route('reconciliacion.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Resumen de Alertas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-red-50 rounded-lg p-4 border border-red-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Alertas Críticas</p>
            <p class="text-3xl font-bold text-red-600 mt-2">{{ $totales['total_criticas'] }}</p>
        </div>

        <div class="bg-yellow-50 rounded-lg p-4 border border-yellow-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Advertencias</p>
            <p class="text-3xl font-bold text-yellow-600 mt-2">{{ $totales['total_advertencias'] }}</p>
        </div>

        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Discrepancias</p>
            <p class="text-3xl font-bold text-orange-600 mt-2">{{ $totales['total_discrepancias'] }}</p>
        </div>

        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Total Alertas</p>
            <p class="text-3xl font-bold text-blue-600 mt-2">{{ $totales['total_alertas'] }}</p>
        </div>
    </div>

    <!-- Alertas Críticas -->
    @if(count($criticas) > 0)
        <div class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-red-800 mb-4">
                <i class="fas fa-exclamation-triangle"></i> Alertas Críticas ({{ count($criticas) }})
            </h2>

            <div class="space-y-3">
                @foreach($criticas as $alerta)
                    <div class="bg-white rounded-lg p-4 border-l-4 border-red-600">
                        <div class="flex justify-between items-start">
                            <div>
                                @if($alerta['tipo'] === 'MONTO_APOYO')
                                    <p class="font-bold text-gray-900">Discrepancia de Monto - Apoyo</p>
                                    <p class="text-sm text-gray-600">{{ $alerta['nombre_apoyo'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        BD_Finanzas: ${{ number_format($alerta['bd_finanzas'], 2) }} vs 
                                        Histórico Cierre: ${{ number_format($alerta['historico_cierre'], 2) }}
                                    </p>
                                @elseif($alerta['tipo'] === 'SOBREEJERCICIO')
                                    <p class="font-bold text-gray-900">Presupuesto Sobre-Ejercido</p>
                                    <p class="text-sm text-gray-600">{{ $alerta['nombre_apoyo'] }}</p>
                                    <p class="text-xs text-gray-500 mt-1">
                                        Asignado: ${{ number_format($alerta['monto_asignado'], 2) }} 
                                        Ejercido: ${{ number_format($alerta['monto_ejercido'], 2) }}
                                    </p>
                                @else
                                    <p class="font-bold text-gray-900">{{ $alerta['nombre'] }}</p>
                                    <p class="text-sm text-gray-600">Ejecución: {{ $alerta['porcentaje'] }}%</p>
                                @endif
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-red-600">
                                    @if(isset($alerta['diferencia']))
                                        ±${{ number_format(abs($alerta['diferencia']), 2) }}
                                    @elseif(isset($alerta['porcentaje']))
                                        {{ $alerta['porcentaje'] }}%
                                    @endif
                                </p>
                                <span class="inline-block mt-1 px-2 py-1 bg-red-100 text-red-800 text-xs font-bold rounded">
                                    {{ $alerta['severidad'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Advertencias -->
    @if(count($advertencias) > 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-600 rounded-lg p-6 mb-6">
            <h2 class="text-xl font-bold text-yellow-800 mb-4">
                <i class="fas fa-bell"></i> Advertencias ({{ count($advertencias) }})
            </h2>

            <div class="space-y-3">
                @foreach($advertencias as $alerta)
                    <div class="bg-white rounded-lg p-4 border-l-4 border-yellow-600">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-bold text-gray-900">Presupuesto por Ejecutar</p>
                                <p class="text-sm text-gray-600">{{ $alerta['nombre'] }}</p>
                                <p class="text-xs text-gray-500 mt-1">
                                    Ejecución: {{ $alerta['porcentaje'] }}% - Umbrales: 85-95%
                                </p>
                            </div>
                            <div class="text-right">
                                <p class="text-lg font-bold text-yellow-600">{{ $alerta['porcentaje'] }}%</p>
                                <span class="inline-block mt-1 px-2 py-1 bg-yellow-100 text-yellow-800 text-xs font-bold rounded">
                                    {{ $alerta['severidad'] }}
                                </span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Sin Alertas -->
    @if(count($criticas) === 0 && count($advertencias) === 0)
        <div class="bg-green-50 border-l-4 border-green-600 rounded-lg p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-600 text-3xl"></i>
                </div>
                <div class="ml-4">
                    <h3 class="text-lg font-bold text-green-800">Presupuesto en Buen Estado</h3>
                    <p class="text-sm text-green-700 mt-1">
                        No se han detectado discrepancias críticas o advertencias. Todos los presupuestos se encuentran dentro de rangos normales.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <!-- Detalles de Discrepancias -->
    @if(count($discrepancias) > 0)
        <div class="mt-6 bg-white rounded-lg shadow overflow-hidden">
            <div class="p-6 border-b-2 border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-search"></i> Discrepancias Detectadas
                </h3>
            </div>

            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                        <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Entidad</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Valor 1</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Valor 2</th>
                        <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Diferencia</th>
                        <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Severidad</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($discrepancias as $disc)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-sm font-medium text-gray-900">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                    @if($disc['tipo'] === 'MONTO_APOYO')
                                        bg-blue-100 text-blue-800
                                    @elseif($disc['tipo'] === 'SOBREEJERCICIO')
                                        bg-red-100 text-red-800
                                    @endif">
                                    {{ $disc['tipo'] }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">{{ $disc['nombre_apoyo'] ?? $disc['nombre'] ?? '—' }}</td>
                            <td class="px-6 py-4 text-sm text-right text-gray-700 font-mono">
                                @if(isset($disc['bd_finanzas']))
                                    ${{ number_format($disc['bd_finanzas'], 2) }}
                                @elseif(isset($disc['monto_asignado']))
                                    ${{ number_format($disc['monto_asignado'], 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right text-gray-700 font-mono">
                                @if(isset($disc['historico_cierre']))
                                    ${{ number_format($disc['historico_cierre'], 2) }}
                                @elseif(isset($disc['monto_ejercido']))
                                    ${{ number_format($disc['monto_ejercido'], 2) }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-6 py-4 text-sm text-right font-bold {{ $disc['diferencia'] < 0 ? 'text-red-600' : 'text-orange-600' }}">
                                @if($disc['diferencia'] < 0)
                                    -${{ number_format(abs($disc['diferencia']), 2) }}
                                @else
                                    +${{ number_format($disc['diferencia'], 2) }}
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                    @if($disc['severidad'] === 'CRITICA')
                                        bg-red-100 text-red-800
                                    @else
                                        bg-yellow-100 text-yellow-800
                                    @endif">
                                    {{ $disc['severidad'] }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
@endsection
