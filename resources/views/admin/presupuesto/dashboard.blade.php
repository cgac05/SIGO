@extends('layouts.app')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8 py-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex justify-between items-start mb-6">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">
                    <i class="fas fa-chart-pie text-blue-600 mr-3"></i>
                    Dashboard de Presupuestación
                </h1>
                <p class="text-gray-600">
                    <i class="fas fa-calendar-alt mr-2"></i>Ciclo Fiscal {{ $ciclo->año_fiscal }}
                </p>
            </div>
            <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-bold {{ $ciclo->estado === 'ABIERTO' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                <i class="fas fa-circle-notch mr-2"></i>{{ $ciclo->estado }}
            </span>
        </div>
    </div>

    <!-- ROW 1: Cards de Resumen -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Presupuesto Total -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500 hover:shadow-lg transition">
            <div class="flex items-center mb-4">
                <div class="bg-blue-100 rounded-full p-3 mr-4">
                    <i class="fas fa-money-bill-wave text-blue-600 text-xl"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase">Presupuesto Total</p>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">${{ number_format($resumen['presupuesto_total'], 0) }}</h3>
            <p class="text-xs text-gray-600">
                <i class="fas fa-check-circle text-green-500 mr-1"></i>
                Presupuesto para el año fiscal
            </p>
        </div>

        <!-- Gastado -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500 hover:shadow-lg transition">
            <div class="flex items-center mb-4">
                <div class="bg-orange-100 rounded-full p-3 mr-4">
                    <i class="fas fa-credit-card text-orange-600 text-xl"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase">Gastado</p>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">${{ number_format($resumen['gastado_total'], 0) }}</h3>
            <p class="text-xs text-gray-600">
                <i class="fas fa-arrow-down text-orange-500 mr-1"></i>
                Repartido entre categorías
            </p>
        </div>

        <!-- Disponible -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500 hover:shadow-lg transition">
            <div class="flex items-center mb-4">
                <div class="bg-green-100 rounded-full p-3 mr-4">
                    <i class="fas fa-wallet text-green-600 text-xl"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase">Disponible</p>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">${{ number_format($resumen['disponible_total'], 0) }}</h3>
            <p class="text-xs text-gray-600">
                <i class="fas fa-arrow-up text-green-500 mr-1"></i>
                Por distribuir
            </p>
        </div>

        <!-- Porcentaje Utilizado -->
        <div class="bg-white rounded-lg shadow p-6 border-l-4 border-cyan-500 hover:shadow-lg transition">
            <div class="flex items-center mb-4">
                <div class="bg-cyan-100 rounded-full p-3 mr-4">
                    <i class="fas fa-chart-area text-cyan-600 text-xl"></i>
                </div>
                <p class="text-xs font-bold text-gray-500 uppercase">% Utilizado</p>
            </div>
            <h3 class="text-2xl font-bold text-gray-900 mb-1">{{ $resumen['porcentaje_general'] }}%</h3>
            <p class="text-xs text-gray-600">
                <i class="fas fa-percent text-cyan-500 mr-1"></i>
                Del presupuesto total
            </p>
        </div>
    </div>

    <!-- ROW 2: Gráficos principales -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Gráfico Circular Doughnut -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-circle-notch text-blue-600 mr-2"></i>
                    Distribución del Presupuesto
                </h3>
                <p class="text-sm text-gray-600 mt-1">Presupuesto Total vs Disponible</p>
            </div>
            <div class="p-6 flex items-center justify-center" style="min-height: 350px;">
                @if($resumen['presupuesto_total'] > 0)
                    <canvas id="chartPresupuesto"></canvas>
                @else
                    <div class="text-center text-gray-500 py-12">
                        <i class="fas fa-chart-pie text-4xl opacity-30 mb-3 block"></i>
                        <p>Sin datos disponibles</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Gráfico Barras Horizontal -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-bar-chart text-green-600 mr-2"></i>
                    Ejecución Presupuestaria
                </h3>
                <p class="text-sm text-gray-600 mt-1">Porcentaje de utilización por categoría</p>
            </div>
            <div class="p-6 flex items-center justify-center" style="min-height: 350px;">
                @if($categorias->count() > 0)
                    <canvas id="chartEjecucion"></canvas>
                @else
                    <div class="text-center text-gray-500 py-12">
                        <i class="fas fa-chart-bar text-4xl opacity-30 mb-3 block"></i>
                        <p>Sin categorías configuradas</p>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- ROW 3: Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
        <!-- Categorías -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-folder text-cyan-600 mr-2"></i>
                    Categorías Configuradas
                </h3>
                <p class="text-sm text-gray-600 mt-1">Total de categorías presupuestarias</p>
            </div>
            <div class="p-6 text-center">
                <h2 class="text-4xl font-bold text-cyan-600 mb-2">{{ $resumen['num_categorias'] }}</h2>
                @if($resumen['num_categorias'] === 0)
                    <p class="text-gray-600">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                        Sin categorías configuradas
                    </p>
                @else
                    <p class="text-gray-600">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>
                        Categorías disponibles
                    </p>
                @endif
            </div>
        </div>

        <!-- Apoyos -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="border-b border-gray-200 px-6 py-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-handshake text-green-600 mr-2"></i>
                    Apoyos Aprobados
                </h3>
                <p class="text-sm text-gray-600 mt-1">Total de apoyos en este ciclo</p>
            </div>
            <div class="p-6 text-center">
                <h2 class="text-4xl font-bold text-green-600 mb-2">{{ $resumen['num_apoyos_aprobados'] }}</h2>
                @if($resumen['num_apoyos_aprobados'] === 0)
                    <p class="text-gray-600">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Sin apoyos aprobados aún
                    </p>
                @else
                    <p class="text-gray-600">
                        <i class="fas fa-chart-line text-green-500 mr-2"></i>
                        Apoyos en ejecución
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- ROW 4: Tabla de Categorías -->
    @if($categorias->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">
                    <i class="fas fa-cubes mr-2"></i>
                    Desglose Detallado por Categoría
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-gray-700">#</th>
                            <th class="px-6 py-3 text-left font-bold text-gray-700">Categoría</th>
                            <th class="px-6 py-3 text-right font-bold text-gray-700">Presupuesto Total</th>
                            <th class="px-6 py-3 text-right font-bold text-gray-700">Disponible</th>
                            <th class="px-6 py-3 text-right font-bold text-gray-700">Gastado</th>
                            <th class="px-6 py-3 text-center font-bold text-gray-700">% Utilizado</th>
                            <th class="px-6 py-3 text-center font-bold text-gray-700">Estado</th>
                            <th class="px-6 py-3 text-center font-bold text-gray-700">Apoyos</th>
                            <th class="px-6 py-3 text-center font-bold text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categorias as $index => $categoria)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                <td class="px-6 py-4 text-gray-700">{{ $index + 1 }}</td>
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ $categoria['nombre'] }}</td>
                                <td class="px-6 py-4 text-right text-gray-700">${{ number_format($categoria['presupuesto_total'], 0) }}</td>
                                <td class="px-6 py-4 text-right text-green-600 font-semibold">${{ number_format($categoria['disponible'], 0) }}</td>
                                <td class="px-6 py-4 text-right text-orange-600 font-semibold">${{ number_format($categoria['gastado'], 0) }}</td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center">
                                        <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                            @php
                                                $bgColor = match($categoria['badge_color']) {
                                                    'success' => 'bg-green-500',
                                                    'warning' => 'bg-orange-500',
                                                    'danger' => 'bg-red-500',
                                                    'info' => 'bg-cyan-500',
                                                    default => 'bg-blue-500'
                                                };
                                            @endphp
                                            <div class="{{ $bgColor }} h-2 rounded-full" style="width: {{ $categoria['porcentaje_utilizado'] }}%"></div>
                                        </div>
                                        <span class="font-bold text-gray-700 text-xs">{{ $categoria['porcentaje_utilizado'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @php
                                        $badgeClass = match($categoria['badge_color']) {
                                            'success' => 'bg-green-100 text-green-800',
                                            'warning' => 'bg-orange-100 text-orange-800',
                                            'danger' => 'bg-red-100 text-red-800',
                                            'info' => 'bg-cyan-100 text-cyan-800',
                                            default => 'bg-blue-100 text-blue-800'
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $badgeClass }}">
                                        {{ $categoria['estado_visual'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-cyan-100 text-cyan-800">
                                        {{ $categoria['apoyos_aprobados'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <a href="{{ route('presupuesto.categorias.show', $categoria['id_categoria']) }}" 
                                       class="inline-flex items-center px-3 py-2 text-xs font-bold text-blue-600 bg-blue-50 rounded hover:bg-blue-100 transition">
                                        <i class="fas fa-eye mr-1"></i>Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-6 py-8 text-center text-gray-500">
                                    <i class="fas fa-inbox text-3xl opacity-30 mb-2 block"></i>
                                    No hay categorías configuradas
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded-lg">
            <div class="flex items-start">
                <i class="fas fa-info-circle text-blue-600 mr-3 mt-1"></i>
                <div>
                    <h3 class="font-bold text-blue-900 mb-1">Sin categorías configuradas</h3>
                    <p class="text-sm text-blue-700">El administrador debe configurar las categorías presupuestarias para este ciclo fiscal.</p>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Datos comunes
    const presupuestoTotal = {{ $resumen['presupuesto_total'] }};
    const disponibleTotal = {{ $resumen['disponible_total'] }};
    const gastadoTotal = {{ $resumen['gastado_total'] }};

    // Gráfico Circular (Doughnut)
    @if($resumen['presupuesto_total'] > 0)
        const ctx1 = document.getElementById('chartPresupuesto');
        if (ctx1) {
            new Chart(ctx1, {
                type: 'doughnut',
                data: {
                    labels: ['Gastado', 'Disponible'],
                    datasets: [{
                        data: [gastadoTotal, disponibleTotal],
                        backgroundColor: ['#ff7675', '#74b9ff'],
                        borderColor: ['#d63031', '#0984e3'],
                        borderWidth: 2,
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
                                font: { size: 13, weight: 'bold' },
                                padding: 20,
                                usePointStyle: true
                            }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = '$' + context.parsed;
                                    const percentage = ((context.parsed / presupuestoTotal) * 100).toFixed(1);
                                    return label + ': ' + value + ' (' + percentage + '%)';
                                }
                            }
                        }
                    }
                },
                plugins: [{
                    id: 'textCenter',
                    beforeDatasetsDraw(chart) {
                        const {ctx, chartArea: {left, top, width, height}} = chart;
                        ctx.save();
                        
                        ctx.font = 'bold 20px Arial';
                        ctx.fillStyle = '#374151';
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.fillText('$' + presupuestoTotal.toLocaleString('es-MX', {maximumFractionDigits: 0}), left + width / 2, top + height / 2 - 10);
                        
                        ctx.font = '12px Arial';
                        ctx.fillStyle = '#6b7280';
                        ctx.fillText('Presupuesto Total', left + width / 2, top + height / 2 + 15);
                        
                        ctx.restore();
                    }
                }]
            });
        }
    @endif

    // Gráfico de Barras Horizontal
    @if($categorias->count() > 0)
        const categoriasData = @json($categorias);
        const ctx2 = document.getElementById('chartEjecucion');
        if (ctx2) {
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: categoriasData.map(function(c) { return c.nombre; }),
                    datasets: [{
                        label: '% Utilizado',
                        data: categoriasData.map(function(c) { return c.porcentaje_utilizado; }),
                        backgroundColor: categoriasData.map(function(c) {
                            const colors = {
                                'success': '#10b981',
                                'warning': '#f59e0b',
                                'danger': '#ef4444',
                                'info': '#06b6d4'
                            };
                            return colors[c.badge_color] || '#3b82f6';
                        }),
                        borderRadius: 6,
                        borderSkipped: false
                    }]
                },
                options: {
                    indexAxis: 'y',
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: {
                            display: false
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.parsed.x.toFixed(2) + '%';
                                }
                            }
                        }
                    },
                    scales: {
                        x: {
                            beginAtZero: true,
                            max: 100,
                            ticks: {
                                callback: function(value) {
                                    return value + '%';
                                }
                            }
                        }
                    }
                }
            });
        }
    @endif
</script>

@endsection
