@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Reconciliación Presupuestaria</h1>
            <p class="text-gray-600 mt-1">Validación de ejecución presupuestaria y auditoría</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reconciliacion.descargar') }}" 
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-download"></i> Descargar Reporte
            </a>
        </div>
    </div>

    <!-- Ejecución Global -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-2xl font-bold text-gray-900 mb-4">
            <i class="fas fa-chart-bar text-blue-600"></i> Ejecución Presupuestaria Global
        </h2>

        <div class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <!-- Total Asignado -->
            <div class="bg-gradient-to-br from-blue-50 to-blue-100 rounded-lg p-4 border border-blue-200">
                <p class="text-xs text-gray-600 uppercase tracking-wide">Total Asignado</p>
                <p class="text-2xl font-bold text-blue-600 mt-2">
                    ${{ number_format($ejecucionGlobal['total_asignado'], 2) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Presupuesto disponible</p>
            </div>

            <!-- Total Ejercido -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg p-4 border border-green-200">
                <p class="text-xs text-gray-600 uppercase tracking-wide">Total Ejercido</p>
                <p class="text-2xl font-bold text-green-600 mt-2">
                    ${{ number_format($ejecucionGlobal['total_ejercido'], 2) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Presupuesto utilizado</p>
            </div>

            <!-- Total Entregado -->
            <div class="bg-gradient-to-br from-purple-50 to-purple-100 rounded-lg p-4 border border-purple-200">
                <p class="text-xs text-gray-600 uppercase tracking-wide">Total Entregado</p>
                <p class="text-2xl font-bold text-purple-600 mt-2">
                    ${{ number_format($ejecucionGlobal['total_entregado'], 2) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">A beneficiarios</p>
            </div>

            <!-- Diferencia -->
            <div class="bg-gradient-to-br from-orange-50 to-orange-100 rounded-lg p-4 border border-orange-200">
                <p class="text-xs text-gray-600 uppercase tracking-wide">Diferencia</p>
                <p class="text-2xl font-bold text-orange-600 mt-2">
                    ${{ number_format($ejecucionGlobal['diferencia'], 2) }}
                </p>
                <p class="text-xs text-gray-500 mt-1">Disponible aún</p>
            </div>

            <!-- Porcentaje -->
            <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-lg p-4 border border-gray-200">
                <p class="text-xs text-gray-600 uppercase tracking-wide">% Ejecución</p>
                <p class="text-2xl font-bold text-gray-600 mt-2">{{ $ejecucionGlobal['porcentaje_ejecucion'] }}%</p>
                <div class="w-full bg-gray-300 rounded-full h-2 mt-2">
                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $ejecucionGlobal['porcentaje_ejecucion'] }}%"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas Críticas -->
    @if(count($discrepancias) > 0)
        <div class="bg-red-50 border-l-4 border-red-600 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-red-800 mb-3">
                <i class="fas fa-exclamation-triangle"></i> Alertas Críticas - Discrepancias Detectadas
            </h3>
            <div class="space-y-2">
                @foreach($discrepancias as $disc)
                    <div class="bg-white rounded p-3 text-sm">
                        <div class="flex justify-between items-start">
                            <div>
                                <p class="font-semibold text-gray-900">{{ $disc['tipo'] }}</p>
                                <p class="text-gray-600">{{ $disc['nombre_apoyo'] ?? $disc['nombre'] ?? 'Apoyo sin nombre' }}</p>
                            </div>
                            <div class="text-right">
                                <p class="font-bold text-red-600">Diferencia: ${{ number_format($disc['diferencia'], 2) }}</p>
                                <p class="text-xs text-gray-500">BD_Finanzas: ${{ number_format($disc['bd_finanzas'] ?? $disc['monto_ejercido'] ?? 0, 2) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Alertas por Umbral -->
    @if(count($alertas) > 0)
        <div class="bg-yellow-50 border-l-4 border-yellow-600 rounded-lg p-6 mb-6">
            <h3 class="text-lg font-bold text-yellow-800 mb-3">
                <i class="fas fa-bell"></i> Alertas de Presupuesto - Por Umbral de Ejecución
            </h3>
            <div class="space-y-2">
                @foreach($alertas as $alerta)
                    <div class="bg-white rounded p-3 text-sm">
                        <div class="flex justify-between items-center">
                            <p class="font-semibold text-gray-900">{{ $alerta['nombre'] }}</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                @if($alerta['severidad'] === 'CRITICA')
                                    bg-red-100 text-red-800
                                @else
                                    bg-yellow-100 text-yellow-800
                                @endif">
                                {{ $alerta['porcentaje'] }}% - {{ $alerta['severidad'] }}
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Quick Links -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <a href="{{ route('reconciliacion.categorias') }}" 
            class="bg-white hover:shadow-lg rounded-lg p-6 text-center transition border-l-4 border-blue-600">
            <i class="fas fa-list text-blue-600 text-3xl mb-2"></i>
            <h3 class="font-bold text-gray-900">Categorías</h3>
            <p class="text-sm text-gray-600 mt-1">Ejecución por categoría presupuestaria</p>
        </a>

        <a href="{{ route('reconciliacion.apoyos') }}" 
            class="bg-white hover:shadow-lg rounded-lg p-6 text-center transition border-l-4 border-green-600">
            <i class="fas fa-gift text-green-600 text-3xl mb-2"></i>
            <h3 class="font-bold text-gray-900">Apoyos</h3>
            <p class="text-sm text-gray-600 mt-1">Ejecución por programa de apoyo</p>
        </a>

        <a href="{{ route('reconciliacion.alertas') }}" 
            class="bg-white hover:shadow-lg rounded-lg p-6 text-center transition border-l-4 border-red-600">
            <i class="fas fa-exclamation-circle text-red-600 text-3xl mb-2"></i>
            <h3 class="font-bold text-gray-900">Alertas</h3>
            <p class="text-sm text-gray-600 mt-1">Discrepancias y advertencias detectadas</p>
        </a>
    </div>

    <!-- Resumen de Categorías -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-6">
        <div class="p-6 border-b-2 border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">
                <i class="fas fa-layer-group text-blue-600"></i> Resumen por Categoría
            </h2>
        </div>

        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Categoría</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Asignado</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Ejercido</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Disponible</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">% Ejecución</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($ejecucionPorCategoria as $cat)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $cat['nombre'] }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($cat['presupuesto_asignado'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($cat['monto_ejercido'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($cat['disponible'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full" style="width: {{ $cat['porcentaje_ejecucion'] }}%"></div>
                                </div>
                                <span class="ml-2 text-xs font-semibold text-gray-700">{{ $cat['porcentaje_ejecucion'] }}%</span>
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
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No hay categorías presupuestarias activas
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Resumen de Apoyos -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="p-6 border-b-2 border-gray-200">
            <h2 class="text-xl font-bold text-gray-900">
                <i class="fas fa-gift text-green-600"></i> Resumen por Apoyo
            </h2>
        </div>

        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Apoyo</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Asignado</th>
                    <th class="px-6 py-3 text-right text-sm font-semibold text-gray-700">Ejercido</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Beneficiarios</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">% Ejecución</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Estado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($ejecucionPorApoyo as $apoyo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm font-medium text-gray-900">{{ $apoyo['nombre_apoyo'] }}</td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($apoyo['presupuesto_asignado'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-right text-gray-700">
                            ${{ number_format($apoyo['monto_ejercido'], 2) }}
                        </td>
                        <td class="px-6 py-4 text-center text-sm font-medium text-gray-900">
                            {{ $apoyo['beneficiarios_pagados'] }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <div class="flex items-center justify-center">
                                <div class="w-16 bg-gray-200 rounded-full h-2">
                                    <div class="bg-green-600 h-2 rounded-full" style="width: {{ $apoyo['porcentaje_ejecucion'] }}%"></div>
                                </div>
                                <span class="ml-2 text-xs font-semibold text-gray-700">{{ $apoyo['porcentaje_ejecucion'] }}%</span>
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
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            No hay apoyos registrados
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
