@extends('layouts.app')

@section('title', 'Dashboard Económico')

@section('content')
<div class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto">
        
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-900 mb-2">📊 Dashboard Económico</h1>
                <p class="text-gray-600">{{ now()->format('d \d\e F \d\e Y') }}</p>
            </div>
            
            <!-- SELECTOR DE CICLO PRESUPUESTARIO -->
            <div class="flex items-center gap-3">
                <label class="text-sm font-semibold text-gray-700">Ciclo Presupuestario:</label>
                <select id="cicloSelector" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-900 font-medium hover:border-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @forelse($ciclosDisponibles as $ciclo)
                        <option value="{{ $ciclo->id_ciclo }}" {{ $ciclo->id_ciclo == $cicloActivo?->id_ciclo ? 'selected' : '' }}>
                            {{ $ciclo->ano_fiscal }} 
                            @if($ciclo->estado === 'ABIERTO')
                                <span class="text-green-600">(Abierto)</span>
                            @else
                                <span class="text-gray-500">(Cerrado)</span>
                            @endif
                        </option>
                    @empty
                        <option disabled selected>No hay ciclos disponibles</option>
                    @endforelse
                </select>
            </div>
        </div>

        <!-- INFORMACIÓN DEL CICLO ACTIVO -->
        @if($cicloActivo)
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-blue-800 text-sm font-medium">Ciclo Fiscal</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $cicloActivo->ano_fiscal }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-800 text-sm font-medium">Estado del Ciclo</p>
                <p class="text-2xl font-bold mt-1">
                    <span class="inline-block px-3 py-1 rounded text-white text-sm {{ $cicloActivo->estado === 'ABIERTO' ? 'bg-green-600' : 'bg-gray-600' }}">
                        {{ $cicloActivo->estado === 'ABIERTO' ? '✅ Abierto' : '🔒 Cerrado' }}
                    </span>
                </p>
            </div>
            <div class="bg-purple-50 border border-purple-200 rounded-lg p-4">
                <p class="text-purple-800 text-sm font-medium">Presupuesto Total Ciclo</p>
                <p class="text-2xl font-bold text-purple-600 mt-1">${{ number_format($cicloActivo->presupuesto_total, 0) }}</p>
            </div>
            <div class="bg-orange-50 border border-orange-200 rounded-lg p-4">
                <p class="text-orange-800 text-sm font-medium">Acciones</p>
                <a href="{{ route('admin.ciclos.show', $cicloActivo->id_ciclo) }}" class="text-orange-600 hover:text-orange-800 font-semibold text-sm mt-1">Ver Ciclo →</a>
            </div>
        </div>
        @endif

        <!-- ALERTAS DE INVENTARIO -->
        @php
            $hayAlertasPresupuesto = $presupuestoPorCategoria->filter(fn($c) => $c->porcentaje >= 85)->count() > 0;
            $hayAlertasInventario = $alertasInventario->count() > 0;
        @endphp

        @if($hayAlertasPresupuesto || $hayAlertasInventario)
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-8">
            @if($hayAlertasPresupuesto)
            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="font-semibold text-red-800 text-lg">⚠️ Alertas de Presupuesto</h3>
                </div>
                <ul class="space-y-1">
                    @foreach($presupuestoPorCategoria->filter(fn($c) => $c->porcentaje >= 85) as $item)
                        <li class="text-sm text-red-700">
                            <strong>{{ $item->nombre }}</strong> - {{ $item->porcentaje }}% utilizado
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            @if($hayAlertasInventario)
            <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg">
                <div class="flex items-center gap-2 mb-2">
                    <h3 class="font-semibold text-orange-800 text-lg">⚠️ Stock Bajo</h3>
                </div>
                <ul class="space-y-1">
                    @foreach($alertasInventario as $item)
                        <li class="text-sm text-orange-700"><strong>{{ $item->nombre_apoyo }}</strong> - Stock: {{ $item->stock_actual }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
        </div>
        @endif

        <!-- ACCIONES RÁPIDAS -->
        @if($cicloActivo)
        <div class="bg-gradient-to-r from-indigo-50 to-blue-50 border border-indigo-200 rounded-lg p-6 mb-8">
            <h3 class="font-semibold text-indigo-900 mb-4 text-lg">⚡ Acciones Rápidas</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-3">
                <a href="{{ route('admin.ciclos.show', $cicloActivo->id_ciclo) }}" 
                   class="p-4 bg-white rounded-lg border border-indigo-200 hover:shadow-md transition text-center">
                    <div class="text-2xl mb-2">📋</div>
                    <p class="text-sm font-semibold text-gray-900">Ver Ciclo</p>
                    <p class="text-xs text-gray-500">{{ $cicloActivo->ano_fiscal }}</p>
                </a>
                
                @if($cicloActivo->estado === 'ABIERTO')
                <a href="{{ route('admin.ciclos.edit', $cicloActivo->id_ciclo) }}" 
                   class="p-4 bg-white rounded-lg border border-indigo-200 hover:shadow-md transition text-center">
                    <div class="text-2xl mb-2">✏️</div>
                    <p class="text-sm font-semibold text-gray-900">Editar Ciclo</p>
                </a>
                @endif

                <a href="{{ route('admin.ciclos.index') }}" 
                   class="p-4 bg-white rounded-lg border border-indigo-200 hover:shadow-md transition text-center">
                    <div class="text-2xl mb-2">➕</div>
                    <p class="text-sm font-semibold text-gray-900">Nuevo Ciclo</p>
                </a>

                <a href="{{ route('admin.presupuesto.dashboard') }}" 
                   class="p-4 bg-white rounded-lg border border-indigo-200 hover:shadow-md transition text-center">
                    <div class="text-2xl mb-2">📊</div>
                    <p class="text-sm font-semibold text-gray-900">Dashboard</p>
                </a>
            </div>
        </div>
        @endif

        <!-- KPI CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <p class="text-gray-600 text-sm font-medium">💰 Presupuesto Total</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($presupuestoTotal, 0) }}</p>
                <p class="text-xs text-gray-500 mt-1">Ciclo {{ $cicloActivo->ano_fiscal ?? 'N/A' }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                <p class="text-gray-600 text-sm font-medium">📊 Asignado</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($totalCategoriaAsignado, 0) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ round($totalCategoriaAsignado / $presupuestoTotal * 100, 1) }}% del total</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                <p class="text-gray-600 text-sm font-medium">✅ Disponible</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($totalCategoriaDisponible, 0) }}</p>
                <p class="text-xs text-gray-500 mt-1">Sin asignar</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                <p class="text-gray-600 text-sm font-medium">📦 Stock Total</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalInventario) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $movimientosEsteMes }} mvtos/mes</p>
            </div>
        </div>

        <!-- MOVIMIENTOS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-800 text-sm font-medium">📥 ENTRADA</p>
                <p class="text-2xl font-bold text-green-600 mt-2">{{ number_format($movimientosEntrada) }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-red-800 text-sm font-medium">📤 SALIDA</p>
                <p class="text-2xl font-bold text-red-600 mt-2">{{ number_format($movimientosSalida) }}</p>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <p class="text-blue-800 text-sm font-medium">🔧 AJUSTE</p>
                <p class="text-2xl font-bold text-blue-600 mt-2">{{ number_format($movimientosAjuste ?? 0) }}</p>
            </div>
        </div>

        <!-- CATEGORÍAS -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="px-6 py-4 bg-gradient-to-r from-blue-600 to-blue-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white">📋 Categorías de Presupuesto</h3>
                @if($cicloActivo && $cicloActivo->estado === 'ABIERTO')
                    <a href="{{ route('admin.ciclos.show', $cicloActivo->id_ciclo) }}" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-sm font-medium">
                        + Agregar Categoría
                    </a>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left font-semibold text-gray-700">Categoría</th>
                            <th class="px-6 py-3 text-right font-semibold text-gray-700">Presupuesto</th>
                            <th class="px-6 py-3 text-right font-semibold text-gray-700">Asignado</th>
                            <th class="px-6 py-3 text-right font-semibold text-gray-700">Disponible</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Utilización</th>
                            <th class="px-6 py-3 text-center font-semibold text-gray-700">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($presupuestoPorCategoria as $cat)
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4 font-medium text-gray-900">{{ $cat->nombre }}</td>
                                <td class="px-6 py-4 text-right text-gray-900 font-semibold">${{ number_format($cat->monto_presupuestado, 0) }}</td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-gray-700">${{ number_format($cat->monto_asignado, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right font-medium">
                                    <span class="text-green-600">${{ number_format($cat->monto_disponible, 0) }}</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <!-- Barra de progreso -->
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full transition-all" 
                                                style="width: {{ min($cat->porcentaje, 100) }}%;
                                                background-color: {{ $cat->estado_alerta === 'danger' ? '#dc2626' : ($cat->estado_alerta === 'warning' ? '#f59e0b' : '#10b981') }};">
                                            </div>
                                        </div>
                                        <span class="text-xs font-bold text-gray-700">{{ $cat->porcentaje }}%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="inline-block px-3 py-1 text-xs font-bold rounded
                                        @if($cat->estado_alerta === 'danger') bg-red-100 text-red-800
                                        @elseif($cat->estado_alerta === 'warning') bg-yellow-100 text-yellow-800
                                        @else bg-green-100 text-green-800
                                        @endif">
                                        {{ $cat->estado_badge }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-gray-600">No hay categorías para este ciclo</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- INVENTARIO -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold">🏪 Stock por Apoyo</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">Apoyo</th>
                        <th class="px-4 py-2 text-left font-semibold">Tipo</th>
                        <th class="px-4 py-2 text-center font-semibold">Stock</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($inventarioPorApoyo as $inv)
                        <tr class="hover:bg-gray-50 {{ $inv->stock_actual < 10 ? 'bg-orange-50' : '' }}">
                            <td class="px-4 py-2 font-medium">{{ $inv->nombre_apoyo }}</td>
                            <td class="px-4 py-2">{{ $inv->tipo_apoyo }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block px-3 py-1 text-xs font-bold rounded {{ $inv->stock_actual < 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ number_format($inv->stock_actual) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-center text-gray-600">No hay inventario</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ÚLTIMAS FACTURAS -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold">📄 Últimas Facturas</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">Factura</th>
                        <th class="px-4 py-2 text-left font-semibold">Proveedor</th>
                        <th class="px-4 py-2 text-right font-semibold">Monto</th>
                        <th class="px-4 py-2 text-center font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($ultimasFacturas as $factura)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.facturas.show', $factura->id_factura) }}" class="text-blue-600 hover:underline font-medium">
                                    {{ $factura->numero_factura }}
                                </a>
                            </td>
                            <td class="px-4 py-2">{{ $factura->nombre_proveedor }}</td>
                            <td class="px-4 py-2 text-right font-medium">${{ number_format($factura->monto_total, 0) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $factura->estado === 'Cancelada' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $factura->estado }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center text-gray-600">No hay facturas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-3 bg-gray-50 border-t">
                <a href="{{ route('admin.facturas.index') }}" class="text-blue-600 hover:underline text-sm font-semibold">Ver todas →</a>
            </div>
        </div>

    </div>
</div>

<script>
    // Selector de ciclo presupuestario con cambio automático
    document.getElementById('cicloSelector').addEventListener('change', function(e) {
        const cicloId = e.target.value;
        if (cicloId) {
            window.location.href = '{{ route("admin.dashboard.economico") }}?ciclo=' + cicloId;
        }
    });
</script>
@endsection
                </thead>
                <tbody class="divide-y">
                    @forelse($inventarioPorApoyo as $inv)
                        <tr class="hover:bg-gray-50 {{ $inv->stock_actual < 10 ? 'bg-orange-50' : '' }}">
                            <td class="px-4 py-2 font-medium">{{ $inv->nombre_apoyo }}</td>
                            <td class="px-4 py-2">{{ $inv->tipo_apoyo }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block px-3 py-1 text-xs font-bold rounded {{ $inv->stock_actual < 10 ? 'bg-red-100 text-red-800' : 'bg-green-100 text-green-800' }}">
                                    {{ number_format($inv->stock_actual) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-center text-gray-600">No hay inventario</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- ÚLTIMAS FACTURAS -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold">📄 Últimas Facturas</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">Factura</th>
                        <th class="px-4 py-2 text-left font-semibold">Proveedor</th>
                        <th class="px-4 py-2 text-right font-semibold">Monto</th>
                        <th class="px-4 py-2 text-center font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($ultimasFacturas as $factura)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2">
                                <a href="{{ route('admin.facturas.show', $factura->id_factura) }}" class="text-blue-600 hover:underline font-medium">
                                    {{ $factura->numero_factura }}
                                </a>
                            </td>
                            <td class="px-4 py-2">{{ $factura->nombre_proveedor }}</td>
                            <td class="px-4 py-2 text-right font-medium">${{ number_format($factura->monto_total, 0) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block px-2 py-1 text-xs font-semibold rounded {{ $factura->estado === 'Cancelada' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800' }}">
                                    {{ $factura->estado }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-2 text-center text-gray-600">No hay facturas</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            <div class="px-6 py-3 bg-gray-50 border-t">
                <a href="{{ route('admin.facturas.index') }}" class="text-blue-600 hover:underline text-sm font-semibold">Ver todas →</a>
            </div>
        </div>

    </div>
</div>
@endsection
