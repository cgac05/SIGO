@extends('layouts.app')

@section('title', 'Dashboard Económico')

@section('content')
<div class="min-h-screen bg-gray-100 p-6">
    <div class="max-w-7xl mx-auto">
        
        <h1 class="text-4xl font-bold text-gray-900 mb-2">📊 Dashboard Económico</h1>
        <p class="text-gray-600 mb-8">{{ now()->format('d \d\e F \d\e Y') }}</p>

        <!-- ALERTAS DE INVENTARIO -->
        @if($alertasInventario->count() > 0)
        <div class="bg-orange-50 border-l-4 border-orange-500 p-4 rounded-lg mb-8">
            <h3 class="font-semibold text-orange-800 mb-2">⚠️ Items con Stock Bajo</h3>
            <ul class="space-y-1">
                @foreach($alertasInventario as $item)
                    <li class="text-sm text-orange-700"><strong>{{ $item->nombre_apoyo }}</strong> - Stock: {{ $item->stock_actual }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <!-- KPI CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-medium">💰 Presupuesto Total</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($presupuestoTotal, 0) }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-medium">✅ Presupuesto Confirmado</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">${{ number_format($presupuestoAsignado, 0) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $porcentajeUtilizacion }}% confirmado</p>
            </div>
            <div class="bg-white rounded-lg shadow p-6">
                <p class="text-gray-600 text-sm font-medium">📦 Stock Inventario</p>
                <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($totalInventario) }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $movimientosEsteMes }} mvtos este mes</p>
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
            <div class="px-6 py-4 bg-gray-50 border-b">
                <h3 class="text-lg font-semibold">📋 Categorías de Presupuesto</h3>
            </div>
            <table class="w-full text-sm">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="px-4 py-2 text-left font-semibold">Categoría</th>
                        <th class="px-4 py-2 text-right font-semibold">Presupuesto Anual</th>
                        <th class="px-4 py-2 text-center font-semibold">Estado</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($presupuestoPorCategoria as $cat)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-medium">{{ $cat->nombre }}</td>
                            <td class="px-4 py-2 text-right">${{ number_format($cat->presupuesto_anual, 0) }}</td>
                            <td class="px-4 py-2 text-center">
                                <span class="inline-block px-3 py-1 text-xs font-bold rounded bg-green-100 text-green-800">✅ Activo</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-center text-gray-600">No hay categorías</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
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
@endsection
