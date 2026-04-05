@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Ejecución Presupuestaria por Categoría</h1>
            <p class="text-gray-600 mt-1">Desglose detallado de presupuesto asignado vs ejercido</p>
        </div>
        <a href="{{ route('reconciliacion.index') }}" class="text-blue-600 hover:text-blue-800">
            <i class="fas fa-arrow-left"></i> Volver
        </a>
    </div>

    <!-- Totales -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-blue-50 rounded-lg p-4 border border-blue-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Total Presupuesto</p>
            <p class="text-2xl font-bold text-blue-600 mt-2">${{ number_format($totales['total_presupuesto'], 2) }}</p>
        </div>

        <div class="bg-green-50 rounded-lg p-4 border border-green-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Total Ejercido</p>
            <p class="text-2xl font-bold text-green-600 mt-2">${{ number_format($totales['total_ejercido'], 2) }}</p>
        </div>

        <div class="bg-orange-50 rounded-lg p-4 border border-orange-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Total Disponible</p>
            <p class="text-2xl font-bold text-orange-600 mt-2">${{ number_format($totales['total_disponible'], 2) }}</p>
        </div>

        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">% Global</p>
            <p class="text-2xl font-bold text-purple-600 mt-2">{{ $totales['porcentaje_global'] }}%</p>
        </div>
    </div>

    <!-- Tabla de Categorías -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Categoría</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Presupuesto Asignado</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Monto Ejercido</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Disponible</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Sobreejercicio</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">% Ejecución</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($categorias as $cat)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $cat['nombre'] }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700 font-semibold">
                            ${{ number_format($cat['presupuesto_asignado'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($cat['monto_ejercido'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($cat['disponible'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right font-semibold {{ $cat['sobreejercicio'] > 0 ? 'text-red-600' : 'text-gray-700' }}">
                            @if($cat['sobreejercicio'] > 0)
                                <i class="fas fa-exclamation-triangle"></i> -${{ number_format($cat['sobreejercicio'], 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-20 bg-gray-200 rounded-full h-2.5">
                                    @php
                                        $color = match($cat['estado']) {
                                            'PENDIENTE' => 'bg-gray-400',
                                            'EN_EJECUCION' => 'bg-blue-500',
                                            'EN_EJECUCION_AVANZADA' => 'bg-cyan-500',
                                            'ALTO' => 'bg-orange-500',
                                            'CRITICO' => 'bg-red-500',
                                            'COMPLETADO' => 'bg-green-500',
                                            default => 'bg-gray-400'
                                        };
                                    @endphp
                                    <div class="{{ $color }} h-2.5 rounded-full" style="width: {{ $cat['porcentaje_ejecucion'] }}%"></div>
                                </div>
                                <span class="ml-2 text-xs font-bold text-gray-800">{{ $cat['porcentaje_ejecucion'] }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if($cat['estado'] === 'PENDIENTE')
                                    bg-gray-100 text-gray-800
                                @elseif($cat['estado'] === 'EN_EJECUCION')
                                    bg-blue-100 text-blue-800
                                @elseif($cat['estado'] === 'EN_EJECUCION_AVANZADA')
                                    bg-cyan-100 text-cyan-800
                                @elseif($cat['estado'] === 'ALTO')
                                    bg-orange-100 text-orange-800
                                @elseif($cat['estado'] === 'CRITICO')
                                    bg-red-100 text-red-800
                                @else
                                    bg-green-100 text-green-800
                                @endif">
                                {{ $cat['estado'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No hay categorías presupuestarias registradas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Leyenda -->
    <div class="mt-6 bg-gray-50 rounded-lg p-4 text-sm text-gray-600">
        <p class="font-semibold text-gray-900 mb-2">Leyenda de Estados:</p>
        <div class="grid grid-cols-2 md:grid-cols-6 gap-3 text-xs">
            <div><span class="inline-block w-3 h-3 bg-gray-400 rounded-full mr-2"></span>Pendiente (0%)</div>
            <div><span class="inline-block w-3 h-3 bg-blue-500 rounded-full mr-2"></span>En Ejecución (&lt;50%)</div>
            <div><span class="inline-block w-3 h-3 bg-cyan-500 rounded-full mr-2"></span>Avanzada (50-85%)</div>
            <div><span class="inline-block w-3 h-3 bg-orange-500 rounded-full mr-2"></span>Alto (85-95%)</div>
            <div><span class="inline-block w-3 h-3 bg-red-500 rounded-full mr-2"></span>Crítico (95-100%)</div>
            <div><span class="inline-block w-3 h-3 bg-green-500 rounded-full mr-2"></span>Completado (100%+)</div>
        </div>
    </div>
</div>
@endsection
