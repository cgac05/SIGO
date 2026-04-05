@extends('layouts.app')

@section('title', 'Detalle de Factura - SIGO')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-4xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <div class="flex justify-between items-start">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900">Factura {{ $factura->numero_factura }}</h1>
                    <p class="text-gray-600 mt-2">Detalles completos de la factura</p>
                </div>
                <span class="inline-flex px-4 py-2 text-sm font-semibold rounded-full
                    @if($factura->estado === 'Pendiente')
                        bg-yellow-100 text-yellow-800
                    @elseif($factura->estado === 'Recibida')
                        bg-green-100 text-green-800
                    @elseif($factura->estado === 'Cancelada')
                        bg-gray-100 text-gray-800
                    @endif
                ">
                    {{ $factura->estado }}
                </span>
            </div>
        </div>

        <!-- Información General -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Información General</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="text-sm font-medium text-gray-500">Número de Factura</label>
                    <p class="text-lg font-semibold text-gray-900 mt-1">{{ $factura->numero_factura }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Proveedor</label>
                    <p class="text-lg font-semibold text-gray-900 mt-1">{{ $factura->nombre_proveedor }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Fecha de Factura</label>
                    <p class="text-lg font-semibold text-gray-900 mt-1">{{ $factura->fecha_compra->format('d/m/Y') }}</p>
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-500">Estado</label>
                    <p class="text-lg font-semibold text-gray-900 mt-1">{{ $factura->estado }}</p>
                </div>
                @if($factura->archivo_factura)
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-500">Comprobante</label>
                        <div class="mt-2">
                            <a href="{{ Storage::url($factura->archivo_factura) }}" 
                               target="_blank" 
                               class="inline-flex items-center text-indigo-600 hover:text-indigo-900">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                Descargar: {{ basename($factura->archivo_factura) }}
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Información de Auditoría -->
        <div class="bg-blue-50 rounded-lg border border-blue-200 p-4 mb-6">
            <h3 class="font-semibold text-blue-900 mb-3">Información de Auditoría</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-600">Registrado por:</span>
                    <p class="font-medium text-gray-900">{{ $factura->registradoPor->nombre ?? 'Sistema' }}</p>
                </div>
                <div>
                    <span class="text-gray-600">Fecha de registro:</span>
                    <p class="font-medium text-gray-900">{{ $factura->created_at->format('d/m/Y H:i') }}</p>
                </div>
                @if($factura->updated_at !== $factura->created_at)
                    <div>
                        <span class="text-gray-600">Última actualización:</span>
                        <p class="font-medium text-gray-900">{{ $factura->updated_at->format('d/m/Y H:i') }}</p>
                    </div>
                    <div>
                        <span class="text-gray-600">Actualizado por:</span>
                        <p class="font-medium text-gray-900">{{ $factura->actualizadoPor->nombre ?? 'Sistema' }}</p>
                    </div>
                @endif
            </div>
        </div>

        <!-- Detalles de Factura -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-6">Líneas de Detalle</h2>
            
            @if($factura->detalles->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Inventario</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700" style="width: 100px;">Cantidad</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700" style="width: 120px;">Precio Unit.</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700" style="width: 120px;">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($factura->detalles as $detalle)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div>
                                            <p class="font-medium text-gray-900">{{ $detalle->inventario->nombre }}</p>
                                            <p class="text-xs text-gray-500">ID: {{ $detalle->inventario_id }}</p>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900">
                                        {{ $detalle->cantidad }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-gray-900">
                                        ${{ number_format($detalle->precio_unitario, 2) }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-gray-900">
                                        ${{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totales -->
                <div class="mt-6 border-t border-gray-200 pt-4 flex justify-end">
                    <div class="w-full md:w-64">
                        <div class="flex justify-between py-3 text-lg font-bold border-t border-gray-200">
                            <span>Monto Total:</span>
                            <span class="text-indigo-600">${{ number_format($factura->monto_total, 2) }}</span>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-8">
                    <p class="text-gray-600">No hay líneas de detalle registradas</p>
                </div>
            @endif
        </div>

        <!-- Movimientos de Inventario Asociados -->
        @if($factura->movimientos->count() > 0)
            <div class="bg-white rounded-lg shadow p-6 mb-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Movimientos de Inventario</h2>
                
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Inventario</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Tipo</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700">Cantidad</th>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Fecha</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            @foreach($factura->movimientos as $movimiento)
                                <tr>
                                    <td class="px-4 py-3 text-gray-900">
                                        {{ $movimiento->inventario->nombre }}
                                    </td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded
                                            @if($movimiento->tipo === 'ENTRADA')
                                                bg-green-100 text-green-800
                                            @else
                                                bg-red-100 text-red-800
                                            @endif
                                        ">
                                            {{ $movimiento->tipo }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-right font-medium text-gray-900">
                                        {{ $movimiento->cantidad }}
                                    </td>
                                    <td class="px-4 py-3 text-gray-700">
                                        {{ $movimiento->created_at->format('d/m/Y H:i') }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Botones -->
        <div class="flex justify-between mb-8">
            <a href="{{ route('admin.facturas.index') }}" 
               class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver
            </a>
            
            <div class="flex gap-3">
                @if($factura->estado !== 'Cancelada')
                    <a href="{{ route('admin.facturas.edit', $factura) }}" 
                       class="inline-flex items-center px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>

                    <form method="POST" action="{{ route('admin.facturas.destroy', $factura) }}" 
                          class="inline" 
                          onsubmit="return confirm('¿Estás seguro de que deseas eliminar esta factura? Se revertirán los movimientos de inventario.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="inline-flex items-center px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </button>
                    </form>
                @else
                    <div class="inline-flex items-center px-6 py-2 bg-gray-300 text-gray-700 rounded-lg cursor-not-allowed">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        Factura Cancelada
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
