{{-- Test simple para presupuesto - sin Alpine.js --}}
@extends('layouts.app')

@section('title', 'Test Presupuesto')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="container mx-auto px-4">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">📊 Test Presupuesto (Renderizado PHP)</h1>

        @php
            // Obtener datos directamente en PHP sin AJAX
            $ciclo = \App\Models\CicloPresupuestario::where('ano_fiscal', 2026)
                ->where('estado', 'ABIERTO')
                ->first();

            if ($ciclo) {
                $categorias = \App\Models\PresupuestoCategoria::where('id_ciclo', $ciclo->id)
                    ->orderBy('nombre')
                    ->get();

                $totalPresupuesto = (float) $ciclo->presupuesto_total_inicial;
                $presupuestoReservado = (float) $categorias->sum(function ($cat) {
                    return $cat->presupuesto_anual - $cat->disponible;
                });
                $presupuestoDisponible = (float) $categorias->sum('disponible');
                $porcentajeDisponible = $totalPresupuesto > 0 
                    ? round(($presupuestoDisponible / $totalPresupuesto) * 100, 1)
                    : 0;
            } else {
                $categorias = [];
                $totalPresupuesto = 0;
                $presupuestoReservado = 0;
                $presupuestoDisponible = 0;
                $porcentajeDisponible = 0;
            }
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Presupuesto -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Presupuesto Total</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            ${{ number_format($totalPresupuesto, 2, '.', ',') }}
                        </h3>
                    </div>
                    <div class="text-blue-500 text-3xl">💰</div>
                </div>
                <p class="text-gray-500 text-xs mt-4">Ciclo Fiscal 2026</p>
            </div>

            <!-- Presupuesto Disponible -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-green-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Disponible</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            ${{ number_format($presupuestoDisponible, 2, '.', ',') }}
                        </h3>
                    </div>
                    <div class="text-green-500 text-3xl">🟢</div>
                </div>
                <p class="text-gray-500 text-xs mt-4">{{ round(($presupuestoDisponible / $totalPresupuesto) * 100, 1) }}% del total</p>
            </div>

            <!-- Presupuesto Reservado -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-orange-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Reservado</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            ${{ number_format($presupuestoReservado, 2, '.', ',') }}
                        </h3>
                    </div>
                    <div class="text-orange-500 text-3xl">🔶</div>
                </div>
                <p class="text-gray-500 text-xs mt-4">Asignado a categorías</p>
            </div>

            <!-- Total Categorías -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Categorías</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            {{ count($categorias) }}
                        </h3>
                    </div>
                    <div class="text-purple-500 text-3xl">📋</div>
                </div>
                <p class="text-gray-500 text-xs mt-4">Activas en 2026</p>
            </div>
        </div>

        <!-- Tabla de Categorías -->
        @if (count($categorias) > 0)
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50">
                <h2 class="text-lg font-bold text-gray-900">Detalle de Categorías</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase">Categoría</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Anual</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Utilizado</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-700 uppercase">Disponible</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">%</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-700 uppercase">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($categorias as $cat)
                            @php
                                $utilizado = $cat->presupuesto_anual - $cat->disponible;
                                $porcentaje = $cat->presupuesto_anual > 0
                                    ? ($utilizado / $cat->presupuesto_anual) * 100
                                    : 0;
                                
                                $estadoColor = match(true) {
                                    $porcentaje >= 90 => 'bg-red-100 text-red-800',
                                    $porcentaje >= 75 => 'bg-orange-100 text-orange-800',
                                    $porcentaje >= 50 => 'bg-blue-100 text-blue-800',
                                    default => 'bg-green-100 text-green-800'
                                };

                                $estadoLabel = match(true) {
                                    $porcentaje >= 90 => 'Crítico',
                                    $porcentaje >= 75 => 'Alto',
                                    $porcentaje >= 50 => 'Moderado',
                                    default => 'Normal'
                                };
                            @endphp
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="px-6 py-4">
                                    <div class="font-medium text-gray-900">{{ $cat->nombre }}</div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="font-semibold">${{ number_format($cat->presupuesto_anual, 2, '.', ',') }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span>${{ number_format($utilizado, 2, '.', ',') }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-green-600 font-medium">${{ number_format($cat->disponible, 2, '.', ',') }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-bold">{{ number_format($porcentaje, 1) }}%</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $estadoColor }}">
                                        {{ $estadoLabel }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-yellow-800">⚠️ No hay categorías presupuestarias para 2026</p>
            </div>
        @endif

        <div class="mt-8">
            <a href="{{ route('admin.presupuesto.dashboard') }}" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                Volver al Dashboard (Alpine.js)
            </a>
        </div>
    </div>
</div>
@endsection
