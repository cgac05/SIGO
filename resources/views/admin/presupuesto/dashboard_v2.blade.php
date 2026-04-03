{{-- Admin Presupuestación Dashboard --}}
@extends('layouts.app')

@section('title', 'Dashboard Presupuestación')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">📊 Dashboard Presupuestación 2026</h1>
            <p class="text-gray-600 mt-2">Visualización en tiempo real del presupuesto por categoría</p>
        </div>

        <!-- KPI Cards Row 1 -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Presupuesto -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-blue-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Presupuesto Total</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            ${{ number_format($totalPresupuesto, 0) }}
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
                            ${{ number_format($presupuestoDisponible, 0) }}
                        </h3>
                    </div>
                    <div class="text-green-500 text-3xl">✅</div>
                </div>
                <p class="text-gray-500 text-xs mt-4">{{ $porcentajeDisponible }}% sin usar</p>
            </div>

            <!-- Presupuesto Reservado -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-yellow-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Reservado</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">
                            ${{ number_format($presupuestoReservado, 0) }}
                        </h3>
                    </div>
                    <div class="text-yellow-500 text-3xl">⏳</div>
                </div>
                <p class="text-gray-500 text-xs mt-4">En apoyos pendientes</p>
            </div>

            <!-- Categorías Activas -->
            <div class="bg-white rounded-lg shadow-md p-6 border-l-4 border-purple-500">
                <div class="flex justify-between items-start">
                    <div>
                        <p class="text-gray-500 text-sm font-medium">Categorías</p>
                        <h3 class="text-2xl font-bold text-gray-900 mt-2">{{ $totalCategorias }}</h3>
                    </div>
                    <div class="text-purple-500 text-3xl">📁</div>
                </div>
                <p class="text-gray-500 text-xs mt-4">Todas activas</p>
            </div>
        </div>

        <!-- Gráfico General - Pastel -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 mb-8">
            <div class="lg:col-span-2 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">📈 Distribución General</h2>
                <canvas id="chartDistribucion" height="150"></canvas>
            </div>

            <!-- Utilizacion por Categoría -->
            <div class="lg:col-span-3 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-lg font-bold text-gray-900 mb-4">🎯 % Utilización por Categoría</h2>
                <div class="space-y-4">
                    @forelse($categorias as $cat)
                        <div>
                            <div class="flex justify-between mb-1">
                                <span class="text-sm font-medium text-gray-700">{{ $cat->nombre }}</span>
                                <span class="text-sm font-bold text-gray-900">
                                    @php
                                        $porcentaje = ($cat->presupuesto_anual - $cat->disponible) / $cat->presupuesto_anual * 100;
                                    @endphp
                                    {{ number_format($porcentaje, 1) }}%
                                </span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 h-2.5 rounded-full" 
                                     style="width: {{ $porcentaje }}%"></div>
                            </div>
                            <div class="flex justify-between mt-1">
                                <span class="text-xs text-gray-500">
                                    ${{ number_format($cat->presupuesto_anual - $cat->disponible, 0) }}
                                </span>
                                <span class="text-xs text-gray-500">
                                    ${{ number_format($cat->presupuesto_anual, 0) }}
                                </span>
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-500 text-center py-4">Sin datos</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Tabla Detallada de Categorías -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">📋 Detalle de Categorías</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600">Categoría</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600">Presupuesto Anual</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600">Utilizado</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600">Disponible</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categorias as $cat)
                            @php
                                $utilizado = $cat->presupuesto_anual - $cat->disponible;
                                $porcentaje = ($utilizado / $cat->presupuesto_anual) * 100;
                                $estadoBadge = match(true) {
                                    $porcentaje >= 90 => ['color' => 'red', 'label' => 'Crítico', 'icon' => '🔴'],
                                    $porcentaje >= 75 => ['color' => 'yellow', 'label' => 'Alto', 'icon' => '🟡'],
                                    $porcentaje >= 50 => ['color' => 'blue', 'label' => 'Moderado', 'icon' => '🔵'],
                                    default => ['color' => 'green', 'label' => 'Normal', 'icon' => '🟢']
                                };
                            @endphp
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <span class="font-medium text-gray-900">{{ $cat->nombre }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-gray-900 font-semibold">${{ number_format($cat->presupuesto_anual, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-gray-700">${{ number_format($utilizado, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-green-600 font-semibold">${{ number_format($cat->disponible, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium 
                                        @if($estadoBadge['color'] === 'red') bg-red-100 text-red-800
                                        @elseif($estadoBadge['color'] === 'yellow') bg-yellow-100 text-yellow-800
                                        @elseif($estadoBadge['color'] === 'blue') bg-blue-100 text-blue-800
                                        @else bg-green-100 text-green-800
                                        @endif">
                                        {{ $estadoBadge['icon'] }} {{ $estadoBadge['label'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('admin.presupuesto.categoria', $cat->id_categoria) }}" 
                                       class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                        Ver detalles →
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                    Sin categorías presupuestarias
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Acciones Rápidas -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <a href="{{ route('admin.presupuesto.reportes') }}" 
               class="bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg p-4 hover:shadow-lg transition text-center font-medium">
                📊 Ver Reportes
            </a>
            <a href="{{ route('admin.presupuesto.index') }}" 
               class="bg-gradient-to-r from-green-500 to-green-600 text-white rounded-lg p-4 hover:shadow-lg transition text-center font-medium">
                ➕ Nuevo Apoyo
            </a>
            <a href="{{ route('api.reporte.exportar.dashboard-pdf') }}" 
               target="_blank"
               class="bg-gradient-to-r from-red-500 to-red-600 text-white rounded-lg p-4 hover:shadow-lg transition text-center font-medium">
                📄 Exportar PDF
            </a>
            <a href="{{ route('api.reporte.exportar.dashboard-excel') }}" 
               target="_blank"
               class="bg-gradient-to-r from-emerald-500 to-emerald-600 text-white rounded-lg p-4 hover:shadow-lg transition text-center font-medium">
                📊 Exportar Excel
            </a>
        </div>
    </div>
</div>

<!-- Alpine.js & Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Datos para gráfico de distribución
    const categoriasData = @json($categorias->map(fn($c) => [
        'nombre' => $c->nombre,
        'presupuesto' => (float)$c->presupuesto_anual,
        'color' => ['#3B82F6', '#10B981', '#F59E0B', '#EF4444', '#8B5CF6'][array_search($c->id_categoria, $categorias->pluck('id_categoria')->toArray()) ?? 0]
    ]));

    if (document.getElementById('chartDistribucion')) {
        const ctx = document.getElementById('chartDistribucion').getContext('2d');
        new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: categoriasData.map(c => c.nombre),
                datasets: [{
                    data: categoriasData.map(c => c.presupuesto),
                    backgroundColor: categoriasData.map(c => c.color),
                    borderColor: '#fff',
                    borderWidth: 3,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 15,
                            font: { size: 12, weight: 'bold' }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return '$' + context.parsed.toLocaleString('es-MX');
                            }
                        }
                    }
                }
            }
        });
    }
});

function exportarPDF() {
    alert('Funcionalidad de exportar PDF en construcción.\nEn desarrollo: jsPDF + html2canvas');
}
</script>
@endsection
