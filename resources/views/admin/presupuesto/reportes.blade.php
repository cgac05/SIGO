@extends('layouts.app')

@section('title', 'Reportes de Presupuestación')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <!-- Breadcrumb & Header -->
        <div class="mb-8">
            <div class="flex items-center gap-2 text-sm text-gray-500 mb-4">
                <a href="{{ route('dashboard') }}" class="hover:text-gray-700">Inicio</a>
                <span>/</span>
                <a href="{{ route('admin.presupuesto.dashboard') }}" class="hover:text-gray-700">Presupuestación</a>
                <span>/</span>
                <span class="text-gray-900 font-semibold">Reportes</span>
            </div>
            
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 flex items-center gap-3">
                        <svg class="w-10 h-10 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M19 4a2 2 0 00-2-2h-3.5l-1.41-1.41A2 2 0 0010 0H4a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V4z"></path>
                        </svg>
                        Reportes de Presupuestación
                    </h1>
                    <p class="text-gray-600 mt-2">Análisis detallado de ejecución presupuestaria</p>
                </div>
                <a href="{{ route('admin.presupuesto.dashboard') }}" class="inline-flex items-center gap-2 bg-gray-900 hover:bg-gray-800 text-white font-semibold py-2 px-4 rounded-lg transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                    </svg>
                    Dashboard
                </a>
            </div>
        </div>

        @if (!$ciclo)
            <!-- Sin ciclo disponible -->
            <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                <div class="flex items-center gap-3 mb-2">
                    <svg class="w-6 h-6 text-yellow-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                    </svg>
                    <div>
                        <h3 class="font-semibold text-yellow-900">No hay ciclo fiscal disponible</h3>
                        <p class="text-yellow-700 text-sm">No se encontró un ciclo fiscal para el año {{ $año }}. Por favor, contacte al administrador del sistema.</p>
                    </div>
                </div>
            </div>
        @else
            <!-- Selector de Año Fiscal -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                <form method="GET" action="{{ route('admin.presupuesto.reportes') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <label for="año" class="block text-sm font-semibold text-gray-700 mb-2">
                            Seleccionar Año Fiscal
                        </label>
                        <input
                            type="number"
                            id="año"
                            name="año"
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            value="{{ $año }}"
                            min="{{ now()->year - 5 }}"
                            max="{{ now()->year + 5 }}">
                    </div>
                    <button type="submit" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-6 rounded-lg transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                        Buscar
                    </button>
                </form>
            </div>

            <!-- Tarjetas de Resumen -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <!-- Presupuesto Total -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Presupuesto Total</h3>
                        <svg class="w-8 h-8 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8.16 2.75a.75.75 0 00-1.32 0l-3.5 8.75H3a.75.75 0 000 1.5h14a.75.75 0 000-1.5h-.34L8.16 2.75z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">${{ number_format($resumen['presupuesto_total'] / 1000000, 2) }}M</p>
                    <p class="text-xs text-gray-500 mt-1">Total disponible para el ciclo</p>
                </div>

                <!-- Disponible -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Disponible</h3>
                        <svg class="w-8 h-8 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">${{ number_format($resumen['disponible_total'] / 1000000, 2) }}M</p>
                    <p class="text-xs text-gray-500 mt-1">Sin asignar aún</p>
                </div>

                <!-- Gastado -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Gastado</h3>
                        <svg class="w-8 h-8 text-orange-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M8 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0zM15 16.5a1.5 1.5 0 11-3 0 1.5 1.5 0 013 0z"></path>
                            <path d="M3 4a1 1 0 00-1 1v10a1 1 0 001 1h1.05a2.5 2.5 0 014.9 0H10a1 1 0 001-1V5a1 1 0 00-1-1H3zM14 7a1 1 0 00-1 1v6.05A2.5 2.5 0 0015.95 16H17a1 1 0 001-1v-5a1 1 0 00-.293-.707l-2-2A1 1 0 0015 7h-1z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">${{ number_format($resumen['gastado_total'] / 1000000, 2) }}M</p>
                    <p class="text-xs text-gray-500 mt-1">Solicitudes aprobadas</p>
                </div>

                <!-- % Utilización -->
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 hover:shadow-lg transition">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-semibold text-gray-600 uppercase tracking-wide">Utilización</h3>
                        <svg class="w-8 h-8 text-indigo-500" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 11a1 1 0 011-1h2a1 1 0 011 1v5a1 1 0 01-1 1H3a1 1 0 01-1-1v-5zM8 7a1 1 0 011-1h2a1 1 0 011 1v9a1 1 0 01-1 1H9a1 1 0 01-1-1V7zM14 4a1 1 0 011-1h2a1 1 0 011 1v12a1 1 0 01-1 1h-2a1 1 0 01-1-1V4z"></path>
                        </svg>
                    </div>
                    <p class="text-3xl font-bold text-gray-900">{{ $resumen['porcentaje_general'] }}%</p>
                    <p class="text-xs text-gray-500 mt-1">Del presupuesto total</p>
                </div>
            </div>

            <!-- Barra de Progreso General -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-8">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-bold text-gray-900">Utilización General del Presupuesto</h2>
                    <span class="text-sm font-semibold text-indigo-600">{{ $resumen['porcentaje_general'] }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4 overflow-hidden">
                    <div
                        class="h-full bg-gradient-to-r from-indigo-500 to-indigo-600 transition-all duration-500"
                        style="width: {{ min($resumen['porcentaje_general'], 100) }}%">
                    </div>
                </div>
                <div class="flex justify-between mt-3 text-xs text-gray-600">
                    <span>Gastado: ${{ number_format($resumen['gastado_total'] / 1000000, 2) }}M</span>
                    <span>Disponible: ${{ number_format($resumen['disponible_total'] / 1000000, 2) }}M</span>
                </div>
            </div>

            <!-- Tabla de Categorías -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden mb-8">
                <div class="bg-gradient-to-r from-indigo-600 to-indigo-700 px-6 py-4">
                    <h2 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Desglose por Categoría
                    </h2>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Categoría</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Presupuesto</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Gastado</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Disponible</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Utilización</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700 uppercase">Apoyos</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @forelse($categorias as $categoria)
                                <tr class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4">
                                        <a href="{{ route('admin.presupuesto.categoria', $categoria['id']) }}" class="font-semibold text-gray-900 hover:text-indigo-600 transition">
                                            {{ $categoria['nombre'] }}
                                        </a>
                                    </td>
                                    <td class="px-6 py-4 text-right text-gray-900 font-semibold">
                                        ${{ number_format($categoria['presupuesto'] / 1000000, 2) }}M
                                    </td>
                                    <td class="px-6 py-4 text-right text-orange-600 font-semibold">
                                        ${{ number_format($categoria['gastado'] / 1000000, 2) }}M
                                    </td>
                                    <td class="px-6 py-4 text-right text-green-600 font-semibold">
                                        ${{ number_format($categoria['disponible'] / 1000000, 2) }}M
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <div class="flex items-center justify-center gap-2">
                                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold
                                                @if($categoria['porcentaje'] >= 85) bg-red-100 text-red-800
                                                @elseif($categoria['porcentaje'] >= 70) bg-yellow-100 text-yellow-800
                                                @else bg-green-100 text-green-800
                                                @endif">
                                                {{ $categoria['porcentaje'] }}%
                                            </span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                                            {{ $categoria['num_apoyos'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center">
                                        <div class="flex flex-col items-center gap-2">
                                            <svg class="w-12 h-12 text-gray-300" fill="currentColor" viewBox="0 0 20 20">
                                                <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z"></path>
                                            </svg>
                                            <p class="text-gray-500 text-sm">No hay categorías disponibles</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-gray-200 font-bold">
                            <tr>
                                <td class="px-6 py-4">TOTAL</td>
                                <td class="px-6 py-4 text-right text-gray-900">
                                    ${{ number_format($resumen['presupuesto_total'] / 1000000, 2) }}M
                                </td>
                                <td class="px-6 py-4 text-right text-orange-600">
                                    ${{ number_format($resumen['gastado_total'] / 1000000, 2) }}M
                                </td>
                                <td class="px-6 py-4 text-right text-green-600">
                                    ${{ number_format($resumen['disponible_total'] / 1000000, 2) }}M
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold bg-indigo-100 text-indigo-800">
                                        {{ $resumen['porcentaje_general'] }}%
                                    </span>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <!-- Información del Ciclo Fiscal -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v2a1 1 0 001 1h14a1 1 0 001-1V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                    </svg>
                    Información del Ciclo Fiscal {{ $ciclo->año_fiscal }}
                </h2>
                
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Estado</p>
                        <p class="text-sm">
                            <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-sm font-semibold
                                @if($ciclo->estado === 'ABIERTO') bg-green-100 text-green-800
                                @else bg-red-100 text-red-800
                                @endif">
                                {{ $ciclo->estado }}
                            </span>
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Fecha de Apertura</p>
                        <p class="text-sm text-gray-900">
                            @if ($ciclo->fecha_apertura)
                                {{ $ciclo->fecha_apertura->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Cierre Programado</p>
                        <p class="text-sm text-gray-900">
                            @if ($ciclo->fecha_cierre_programado)
                                {{ $ciclo->fecha_cierre_programado->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Última Actualización</p>
                        <p class="text-sm text-gray-900">
                            @if ($ciclo->updated_at)
                                {{ $ciclo->updated_at->format('d/m/Y H:i') }}
                            @else
                                —
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        @endif

    </div>
</div>
@endsection
