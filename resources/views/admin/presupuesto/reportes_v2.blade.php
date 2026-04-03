{{-- Reportes Presupuestación Avanzados --}}
@extends('layouts.app')

@section('title', 'Reportes Presupuestación')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-gray-100 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">📊 Reportes Presupuestación</h1>
            <p class="text-gray-600 mt-2">Análisis, tendencias y alertas presupuestarias</p>
        </div>

        <!-- Tabs de Reportes -->
        <div class="bg-white rounded-lg shadow-md mb-8">
            <div class="flex border-b border-gray-200">
                <button @click="tab = 'resumen'" 
                        :class="tab === 'resumen' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-6 py-4 font-medium transition">
                    📈 Resumen Mensual
                </button>
                <button @click="tab = 'alertas'" 
                        :class="tab === 'alertas' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-6 py-4 font-medium transition">
                    ⚠️ Alertas
                </button>
                <button @click="tab = 'flujo'" 
                        :class="tab === 'flujo' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-6 py-4 font-medium transition">
                    📊 Flujo Mensual
                </button>
                <button @click="tab = 'apoyos'" 
                        :class="tab === 'apoyos' ? 'border-b-2 border-blue-600 text-blue-600' : 'text-gray-600 hover:text-gray-900'"
                        class="px-6 py-4 font-medium transition">
                    💼 Apoyos
                </button>
            </div>

            <!-- TAB 1: RESUMEN MENSUAL -->
            <div x-show="tab === 'resumen'" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                    <!-- Selector mes -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Mes</label>
                        <select x-model="mesSeleccionado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                            <option value="1">Enero</option>
                            <option value="2">Febrero</option>
                            <option value="3">Marzo</option>
                            <option value="4">Abril</option>
                            <option value="5">Mayo</option>
                            <option value="6">Junio</option>
                            <option value="7">Julio</option>
                            <option value="8">Agosto</option>
                            <option value="9">Septiembre</option>
                            <option value="10">Octubre</option>
                            <option value="11">Noviembre</option>
                            <option value="12">Diciembre</option>
                        </select>
                    </div>

                    <!-- Botones de acción -->
                    <div class="flex items-end gap-2">
                        <button @click="generarReporteMensual()" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                            🔄 Generar Reporte
                        </button>
                        <a :href="'{{ route('api.reporte.exportar.reportes-excel') }}?mes=' + mesSeleccionado + '&año=2026'" 
                           target="_blank"
                           class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-medium text-center">
                            📊 Excel
                        </a>
                        <a :href="'{{ route('api.reporte.exportar.reportes-pdf') }}?mes=' + mesSeleccionado + '&año=2026'" 
                           target="_blank"
                           class="bg-red-600 text-white px-6 py-2 rounded-lg hover:bg-red-700 font-medium text-center">
                            📄 PDF
                        </a>
                    </div>
                </div>

                <!-- Tabla resumen -->
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600">Categoría</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600">Presupuesto Anual</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600">Utilizado</th>
                                <th class="px-6 py-3 text-right text-xs font-semibold text-gray-600">Disponible</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600">% Utilizado</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold text-gray-600">Movimientos Mes</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categorias ?? [] as $cat)
                                <tr class="border-b border-gray-200 hover:bg-gray-50">
                                    <td class="px-6 py-4">{{ $cat->nombre }}</td>
                                    <td class="px-6 py-4 text-right">${{ number_format($cat->presupuesto_anual, 0) }}</td>
                                    <td class="px-6 py-4 text-right font-semibold">${{ number_format($cat->presupuesto_anual - $cat->disponible, 0) }}</td>
                                    <td class="px-6 py-4 text-right text-green-600">${{ number_format($cat->disponible, 0) }}</td>
                                    @php $pct = ($cat->presupuesto_anual - $cat->disponible) / $cat->presupuesto_anual * 100; @endphp
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-block px-3 py-1 rounded-full text-sm font-bold
                                            @if($pct >= 90) bg-red-100 text-red-800
                                            @elseif($pct >= 75) bg-yellow-100 text-yellow-800
                                            @elseif($pct >= 50) bg-blue-100 text-blue-800
                                            @else bg-green-100 text-green-800
                                            @endif">
                                            {{ number_format($pct, 1) }}%
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">-</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">Sin datos</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: ALERTAS -->
            <div x-show="tab === 'alertas'" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <p class="text-red-600 font-bold text-2xl">0</p>
                        <p class="text-red-700 text-sm">Alertas Críticas</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <p class="text-yellow-600 font-bold text-2xl">0</p>
                        <p class="text-yellow-700 text-sm">Alertas Rojas</p>
                    </div>
                    <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                        <p class="text-orange-600 font-bold text-2xl">0</p>
                        <p class="text-orange-700 text-sm">Alertas Amarillas</p>
                    </div>
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p class="text-blue-600 font-bold text-2xl">0</p>
                        <p class="text-blue-700 text-sm">Total Alertas</p>
                    </div>
                </div>

                <div class="space-y-3">
                    <div class="bg-red-100 border-l-4 border-red-500 p-4 rounded">
                        <p class="font-bold text-red-700">🔴 Categoría en estado crítico (>90% utilizado)</p>
                        <p class="text-red-600 text-sm mt-1">Requiere revisión inmediata</p>
                    </div>
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 p-4 rounded">
                        <p class="font-bold text-yellow-700">🟡 Categoría con alto uso (75-89%)</p>
                        <p class="text-yellow-600 text-sm mt-1">Monitorear próximos gastos</p>
                    </div>
                </div>
            </div>

            <!-- TAB 3: FLUJO MENSUAL -->
            <div x-show="tab === 'flujo'" class="p-6">
                <div class="h-80">
                    <p class="text-gray-600 mb-4">Gráfico de tendencia mensual en construcción</p>
                    <canvas id="chartFlujo"></canvas>
                </div>
            </div>

            <!-- TAB 4: APOYOS -->
            <div x-show="tab === 'apoyos'" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                        <p class="text-blue-600 font-bold text-2xl">0</p>
                        <p class="text-blue-700 text-sm">Total Apoyos</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                        <p class="text-green-600 font-bold text-2xl">0</p>
                        <p class="text-green-700 text-sm">Aprobados</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                        <p class="text-yellow-600 font-bold text-2xl">0</p>
                        <p class="text-yellow-700 text-sm">Pendientes</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                        <p class="text-red-600 font-bold text-2xl">0</p>
                        <p class="text-red-700 text-sm">Rechazados</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-semibold">Folio</th>
                                <th class="px-4 py-3 text-left text-xs font-semibold">Categoría</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold">Monto Solicitado</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold">Monto Aprobado</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold">Estado</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold">% Ejecución</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b hover:bg-gray-50">
                                <td colspan="6" class="px-4 py-4 text-center text-gray-500">Sin datos aún</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Botones de acción general -->
        <div class="flex gap-4 justify-end">
            <button onclick="window.print()" class="bg-gray-600 text-white px-6 py-2 rounded-lg hover:bg-gray-700 font-medium">
                🖨️ Imprimir
            </button>
            <button @click="exportarTodoExcel()" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 font-medium">
                📊 Exportar Todo Excel
            </button>
            <a href="{{ route('admin.presupuesto.index') }}" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 font-medium">
                ← Volver Dashboard
            </a>
        </div>
    </div>
</div>

<!-- Alpine.js & Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.js"></script>

<script>
function app() {
    return {
        tab: 'resumen',
        mesSeleccionado: '{{ date("n") }}',
        
        generarReporteMensual() {
            alert('Generando reporte del mes ' + this.mesSeleccionado);
            // En producción: fetch('/api/reporte/mensual?mes=' + this.mesSeleccionado)
        },
        
        exportarExcel() {
            alert('Exportando a Excel...');
            // En producción: window.location.href = '/reporte/exportar?formato=excel&mes=' + this.mesSeleccionado
        },
        
        exportarTodoExcel() {
            alert('Exportando todo a Excel...');
        }
    }
}
</script>
@endsection
