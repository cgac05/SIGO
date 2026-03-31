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

    <!-- CARDS RESUMEN -->
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
            <p class="text-xs text-gray-600"><i class="fas fa-check-circle text-green-500 mr-1"></i>Presupuesto para el año fiscal</p>
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
            <p class="text-xs text-gray-600"><i class="fas fa-arrow-down text-orange-500 mr-1"></i>Repartido entre categorías</p>
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
            <p class="text-xs text-gray-600"><i class="fas fa-arrow-up text-green-500 mr-1"></i>Por distribuir</p>
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
            <p class="text-xs text-gray-600"><i class="fas fa-percent text-cyan-500 mr-1"></i>Del presupuesto total</p>
        </div>
    </div>

    <!-- TABS CON GRÁFICOS ANILLO -->
    <div class="bg-white rounded-lg shadow overflow-hidden mb-8" x-data="{ tab: 'reparticion' }">
        <!-- Header de Tabs -->
        <div class="border-b border-gray-200 px-6 py-4">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-bold text-gray-900">
                    <i class="fas fa-chart-doughnut text-indigo-600 mr-2"></i>
                    Análisis Presupuestario
                </h3>
            </div>
            
            <!-- Tabs Navigation -->
            <div class="flex space-x-1">
                <button 
                    @click="tab = 'reparticion'"
                    :class="tab === 'reparticion' ? 'bg-indigo-100 text-indigo-700 border-b-2 border-indigo-600' : 'bg-gray-100 text-gray-600 border-b-2 border-transparent'"
                    class="px-6 py-2 rounded-t-lg font-semibold transition-all duration-200 text-sm"
                >
                    <i class="fas fa-pie-chart mr-2"></i>Distribución de Presupuesto
                </button>
                <button 
                    @click="tab = 'gastos'"
                    :class="tab === 'gastos' ? 'bg-indigo-100 text-indigo-700 border-b-2 border-indigo-600' : 'bg-gray-100 text-gray-600 border-b-2 border-transparent'"
                    class="px-6 py-2 rounded-t-lg font-semibold transition-all duration-200 text-sm"
                >
                    <i class="fas fa-chart-line mr-2"></i>Ejecución de Gastos
                </button>
            </div>
        </div>

        <!-- Content Area -->
        <div class="p-8">
            <!-- PESTAÑA 1: REPARTICIÓN -->
            <div x-show="tab === 'reparticion'" x-transition class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Gráfico Anillo Izquierdo -->
                <div class="flex flex-col items-center justify-center">
                    <div class="relative w-64 h-64 mb-6">
                        <canvas id="chartReparticion"></canvas>
                    </div>
                    <p class="text-center text-gray-600 text-sm max-w-xs">
                        <strong>Presupuesto Total por Categoría</strong> - Visualiza cómo se ha repartido el presupuesto de <strong>${{ number_format($resumen['presupuesto_total'], 0) }}</strong> entre las categorías.
                    </p>
                </div>

                <!-- Listado Derecho -->
                <div class="flex flex-col justify-center">
                    <h4 class="font-bold text-gray-900 mb-4 text-lg">Detalles de Repartición</h4>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @php $colores = ['#3B82F6', '#F97316', '#10B981', '#F59E0B', '#8B5CF6']; $idx = 0; @endphp
                        @foreach($datosReparticion as $dato)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center flex-1">
                                    <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $colores[$idx % 5] }};"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900">{{ $dato['nombre'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $dato['porcentaje'] }}% del total</p>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-gray-900 ml-3">${{ number_format($dato['valor'], 0) }}</p>
                            </div>
                            @php $idx++; @endphp
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- PESTAÑA 2: GASTOS -->
            <div x-show="tab === 'gastos'" x-transition class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Gráfico Anillo Izquierdo -->
                <div class="flex flex-col items-center justify-center">
                    <div class="relative w-64 h-64 mb-6">
                        <canvas id="chartGastos"></canvas>
                    </div>
                    <p class="text-center text-gray-600 text-sm max-w-xs">
                        <strong>Gastos Realizados por Categoría</strong> - Visualiza cómo se han ejecutado los gastos de <strong>${{ number_format($totalGastosDetallado, 0) }}</strong> registrados.
                    </p>
                </div>

                <!-- Listado Derecho -->
                <div class="flex flex-col justify-center">
                    <h4 class="font-bold text-gray-900 mb-4 text-lg">Movimientos por Categoría</h4>
                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @php $colores = ['#3B82F6', '#F97316', '#10B981', '#F59E0B', '#8B5CF6']; $idx = 0; @endphp
                        @foreach($gastosCategoria as $gasto)
                            <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition">
                                <div class="flex items-center flex-1">
                                    <div class="w-3 h-3 rounded-full mr-3" style="background-color: {{ $colores[$idx % 5] }};"></div>
                                    <div class="flex-1">
                                        <p class="text-sm font-semibold text-gray-900">{{ $gasto['nombre'] }}</p>
                                        <p class="text-xs text-gray-500">{{ $gasto['porcentaje'] }}% del total</p>
                                    </div>
                                </div>
                                <p class="text-sm font-bold text-gray-900 ml-3">${{ number_format($gasto['valor'], 0) }}</p>
                            </div>
                            @php $idx++; @endphp
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLA RESUMEN -->
    @if($categorias->count() > 0)
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-lg font-bold text-white">
                    <i class="fas fa-cubes mr-2"></i>Resumen de Categorías
                </h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 border-b border-gray-200">
                        <tr>
                            <th class="px-6 py-3 text-left font-bold text-gray-700">Categoría</th>
                            <th class="px-6 py-3 text-right font-bold text-gray-700">Presupuesto</th>
                            <th class="px-6 py-3 text-right font-bold text-gray-700">Disponible</th>
                            <th class="px-6 py-3 text-right font-bold text-gray-700">Gastado</th>
                            <th class="px-6 py-3 text-center font-bold text-gray-700">% Utilizado</th>
                            <th class="px-6 py-3 text-center font-bold text-gray-700">Estado</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categorias as $categoria)
                            <tr class="border-b border-gray-200 hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-semibold text-gray-900">{{ $categoria['nombre'] }}</td>
                                <td class="px-6 py-4 text-right text-gray-700">${{ number_format($categoria['presupuesto_total'], 0) }}</td>
                                <td class="px-6 py-4 text-right text-green-600 font-semibold">${{ number_format($categoria['disponible'], 0) }}</td>
                                <td class="px-6 py-4 text-right text-orange-600 font-semibold">${{ number_format($categoria['gastado'], 0) }}</td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center">
                                        <div class="w-24 bg-gray-200 rounded-full h-2 mr-3">
                                            <div class="bg-indigo-600 h-2 rounded-full" style="width: {{ $categoria['porcentaje_utilizado'] }}%"></div>
                                        </div>
                                        <span class="font-bold text-gray-700 text-xs">{{ $categoria['porcentaje_utilizado'] }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-indigo-100 text-indigo-800">
                                        {{ $categoria['estado_visual'] }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
    const datosReparticion = @json($datosReparticion);
    const datosGastos = @json($gastosCategoria);
    const coloresReparticion = ['#3B82F6', '#F97316', '#10B981', '#F59E0B', '#8B5CF6'];
    
    // Gráfico Repartición
    const ctxReparticion = document.getElementById('chartReparticion');
    if (ctxReparticion) {
        new Chart(ctxReparticion, {
            type: 'doughnut',
            data: {
                labels: datosReparticion.map(d => d.nombre),
                datasets: [{
                    data: datosReparticion.map(d => d.valor),
                    backgroundColor: coloresReparticion.slice(0, datosReparticion.length),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    borderRadius: 4,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percent = ((value / total) * 100).toFixed(1);
                                return `$${(value / 1000000).toFixed(1)}M (${percent}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
    
    // Gráfico Gastos
    const ctxGastos = document.getElementById('chartGastos');
    if (ctxGastos) {
        new Chart(ctxGastos, {
            type: 'doughnut',
            data: {
                labels: datosGastos.map(d => d.nombre),
                datasets: [{
                    data: datosGastos.map(d => d.valor),
                    backgroundColor: coloresReparticion.slice(0, datosGastos.length),
                    borderColor: '#ffffff',
                    borderWidth: 3,
                    borderRadius: 4,
                    hoverOffset: 8,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        padding: 12,
                        titleFont: { size: 14, weight: 'bold' },
                        bodyFont: { size: 13 },
                        borderColor: '#ffffff',
                        borderWidth: 1,
                        callbacks: {
                            label: function(context) {
                                const value = context.parsed;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percent = ((value / total) * 100).toFixed(1);
                                return `$${(value / 1000000).toFixed(1)}M (${percent}%)`;
                            }
                        }
                    }
                }
            }
        });
    }
</script>

@endsection
