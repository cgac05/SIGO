@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Ejecución Presupuestaria por Apoyo</h1>
            <p class="text-gray-600 mt-1">Desglose de presupuesto por programa de apoyo</p>
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

        <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">Beneficiarios Pagados</p>
            <p class="text-2xl font-bold text-indigo-600 mt-2">{{ $totales['total_beneficiarios'] }}</p>
        </div>

        <div class="bg-purple-50 rounded-lg p-4 border border-purple-200">
            <p class="text-xs text-gray-600 uppercase tracking-wide">% Global</p>
            <p class="text-2xl font-bold text-purple-600 mt-2">{{ $totales['porcentaje_global'] }}%</p>
        </div>
    </div>

    <!-- Tabla de Apoyos -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Apoyo</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Presupuesto</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Ejercido</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Disponible</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Beneficiarios</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">% Ejecución</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($apoyos as $apoyo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $apoyo['nombre_apoyo'] }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700 font-semibold">
                            ${{ number_format($apoyo['presupuesto_asignado'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($apoyo['monto_ejercido'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($apoyo['disponible'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-indigo-100 text-indigo-800">
                                {{ $apoyo['beneficiarios_pagados'] }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-20 bg-gray-200 rounded-full h-2.5">
                                    @php
                                        $color = match($apoyo['estado']) {
                                            'PENDIENTE' => 'bg-gray-400',
                                            'EN_EJECUCION' => 'bg-blue-500',
                                            'EN_EJECUCION_AVANZADA' => 'bg-cyan-500',
                                            'ALTO' => 'bg-orange-500',
                                            'CRITICO' => 'bg-red-500',
                                            'COMPLETADO' => 'bg-green-500',
                                            default => 'bg-gray-400'
                                        };
                                    @endphp
                                    <div class="{{ $color }} h-2.5 rounded-full" style="width: {{ $apoyo['porcentaje_ejecucion'] }}%"></div>
                                </div>
                                <span class="ml-2 text-xs font-bold text-gray-800">{{ $apoyo['porcentaje_ejecucion'] }}%</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium
                                @if($apoyo['estado'] === 'PENDIENTE')
                                    bg-gray-100 text-gray-800
                                @elseif($apoyo['estado'] === 'EN_EJECUCION')
                                    bg-blue-100 text-blue-800
                                @elseif($apoyo['estado'] === 'EN_EJECUCION_AVANZADA')
                                    bg-cyan-100 text-cyan-800
                                @elseif($apoyo['estado'] === 'ALTO')
                                    bg-orange-100 text-orange-800
                                @elseif($apoyo['estado'] === 'CRITICO')
                                    bg-red-100 text-red-800
                                @else
                                    bg-green-100 text-green-800
                                @endif">
                                {{ $apoyo['estado'] }}
                            </span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-8 text-center text-gray-500">
                            No hay apoyos registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
