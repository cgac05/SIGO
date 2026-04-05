@extends('layouts.app')

@section('title', 'Editar Factura - SIGO')

@section('content')
<div class="min-h-screen bg-gray-50">
    <div class="max-w-6xl mx-auto px-4 py-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Editar Factura</h1>
            <p class="text-gray-600 mt-2">Actualiza los datos de la factura</p>
        </div>

        @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                <h3 class="font-semibold text-red-800 mb-2">Errores encontrados:</h3>
                <ul class="list-disc list-inside text-red-700 space-y-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form method="POST" 
              action="{{ route('admin.facturas.update', $factura) }}"
              enctype="multipart/form-data"
              class="space-y-6">
            @csrf
            @method('PUT')

            <!-- Información General -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Información General</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Número de Factura <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="numero_factura" required
                               value="{{ old('numero_factura', $factura->numero_factura) }}"
                               placeholder="Ej: FAC-2024-001"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('numero_factura') border-red-500 @enderror">
                        @error('numero_factura')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Fecha de Factura <span class="text-red-500">*</span>
                        </label>
                        <input type="date" name="fecha_compra" required
                               value="{{ old('fecha_compra', $factura->fecha_compra->format('Y-m-d')) }}"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('fecha_compra') border-red-500 @enderror">
                        @error('fecha_compra')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select name="estado" required {{ $factura->estado === 'Cancelada' ? 'disabled' : '' }}
                                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('estado') border-red-500 @enderror"
                                {{ $factura->estado === 'Cancelada' ? 'title=Factura cancelada no puede ser editada' : '' }}>
                            <option value="Pendiente" {{ old('estado', $factura->estado) === 'Pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="Recibida" {{ old('estado', $factura->estado) === 'Recibida' ? 'selected' : '' }}>Recibida</option>
                            @if($factura->estado === 'Cancelada')
                                <option value="Cancelada" selected>Cancelada (No editable)</option>
                            @endif
                        </select>
                        @error('estado')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Proveedor <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="nombre_proveedor" required
                               value="{{ old('nombre_proveedor', $factura->nombre_proveedor) }}"
                               placeholder="Nombre del proveedor"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('nombre_proveedor') border-red-500 @enderror">
                        @error('nombre_proveedor')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Archivo de Comprobante
                        </label>
                        <input type="file" name="archivo_factura" accept=".pdf,.jpg,.jpeg,.png"
                               {{ $factura->estado === 'Cancelada' ? 'disabled' : '' }}
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 @error('archivo_factura') border-red-500 @enderror">
                        <p class="text-xs text-gray-500 mt-1">PDF, JPG o PNG. Máx 5MB</p>
                        @error('archivo_factura')
                            <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                        @enderror
                        @if($factura->archivo_factura)
                            <div class="mt-2 text-sm text-indigo-600">
                                ✓ Archivo actual: <strong>{{ basename($factura->archivo_factura) }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Información de Auditoría -->
            <div class="bg-blue-50 rounded-lg border border-blue-200 p-4">
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
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex justify-between items-center mb-6">
                    <h2 class="text-xl font-bold text-gray-900">Líneas de Detalle</h2>
                    @if($factura->estado !== 'Cancelada')
                        <button type="button" id="agregarLinea" 
                                class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar Línea
                        </button>
                    @endif
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-3 text-left font-semibold text-gray-700">Inventario</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700" style="width: 100px;">Cantidad</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700" style="width: 120px;">Precio Unit.</th>
                                <th class="px-4 py-3 text-right font-semibold text-gray-700" style="width: 120px;">Subtotal</th>
                                @if($factura->estado !== 'Cancelada')
                                    <th class="px-4 py-3 text-center font-semibold text-gray-700" style="width: 80px;">Acción</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="detallesBody">
                            @foreach($factura->detalles as $index => $detalle)
                                <tr class="linea-detalle border-b border-gray-200">
                                    <td class="px-4 py-3">
                                        <select name="detalles[{{ $index }}][inventario_id]" 
                                                class="inventario-select w-full px-2 py-1 border border-gray-300 rounded"
                                                data-index="{{ $index }}"
                                                {{ $factura->estado === 'Cancelada' ? 'disabled' : '' }}>
                                            <option value="">Selecciona inventario</option>
                                            @foreach($inventarios as $inv)
                                                <option value="{{ $inv->id }}" 
                                                    {{ $detalle->inventario_id == $inv->id ? 'selected' : '' }}
                                                    data-precio="{{ $inv->precio_unitario }}">
                                                    {{ $inv->nombre }} ({{ $inv->cantidad }} disponibles)
                                                </option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="detalles[{{ $index }}][cantidad]" min="1" 
                                               value="{{ $detalle->cantidad }}"
                                               class="cantidad-input w-full px-2 py-1 border border-gray-300 rounded text-right"
                                               {{ $factura->estado === 'Cancelada' ? 'disabled readonly' : '' }}
                                               required>
                                    </td>
                                    <td class="px-4 py-3">
                                        <input type="number" name="detalles[{{ $index }}][precio_unitario]" 
                                               step="0.01" min="0" 
                                               value="{{ $detalle->precio_unitario }}"
                                               class="precio-input w-full px-2 py-1 border border-gray-300 rounded text-right"
                                               {{ $factura->estado === 'Cancelada' ? 'disabled readonly' : '' }}
                                               required>
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold">
                                        <span class="subtotal">{{ number_format($detalle->cantidad * $detalle->precio_unitario, 2) }}</span>
                                    </td>
                                    @if($factura->estado !== 'Cancelada')
                                        <td class="px-4 py-3 text-center">
                                            <button type="button" class="eliminar-linea text-red-600 hover:text-red-900">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Totales -->
                <div class="mt-6 border-t border-gray-200 pt-4">
                    <div class="flex justify-end">
                        <div class="w-full md:w-64">
                            <div class="flex justify-between py-2 text-base font-semibold">
                                <span>Monto Total:</span>
                                <span id="montoTotal" class="text-indigo-600">${{ number_format($factura->monto_total, 2) }}</span>
                            </div>
                            <input type="hidden" name="monto_total" id="montoTotalInput" value="{{ $factura->monto_total }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex justify-between">
                <a href="{{ route('admin.facturas.index') }}" 
                   class="px-6 py-2 bg-gray-300 text-gray-800 rounded-lg hover:bg-gray-400 transition">
                    Cancelar
                </a>
                @if($factura->estado !== 'Cancelada')
                    <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                        Actualizar Factura
                    </button>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Template para nueva línea -->
<template id="lineaTemplate">
    <tr class="linea-detalle border-b border-gray-200">
        <td class="px-4 py-3">
            <select name="detalles[][inventario_id]" class="inventario-select w-full px-2 py-1 border border-gray-300 rounded">
                <option value="">Selecciona inventario</option>
                @foreach($inventarios as $inv)
                    <option value="{{ $inv->id }}" data-precio="{{ $inv->precio_unitario }}">
                        {{ $inv->nombre }} ({{ $inv->cantidad }} disponibles)
                    </option>
                @endforeach
            </select>
        </td>
        <td class="px-4 py-3">
            <input type="number" name="detalles[][cantidad]" min="1" value="1"
                   class="cantidad-input w-full px-2 py-1 border border-gray-300 rounded text-right" required>
        </td>
        <td class="px-4 py-3">
            <input type="number" name="detalles[][precio_unitario]" step="0.01" min="0" value="0"
                   class="precio-input w-full px-2 py-1 border border-gray-300 rounded text-right" required>
        </td>
        <td class="px-4 py-3 text-right font-semibold">
            <span class="subtotal">$0.00</span>
        </td>
        <td class="px-4 py-3 text-center">
            <button type="button" class="eliminar-linea text-red-600 hover:text-red-900">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </button>
        </td>
    </tr>
</template>

<script>
(function() {
    const estadoFactura = '{{ $factura->estado }}';
    const esEditableCancelada = estadoFactura === 'Cancelada';

    // Funciones utilitarias
    function formatoMoneda(valor) {
        return '$' + parseFloat(valor || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }

    function calculaSubtotal(fila) {
        const cantidad = parseFloat(fila.querySelector('.cantidad-input').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;
        const subtotal = cantidad * precio;
        fila.querySelector('.subtotal').textContent = formatoMoneda(subtotal);
        return subtotal;
    }

    function calculaTotal() {
        let total = 0;
        document.querySelectorAll('.linea-detalle').forEach(fila => {
            const cantidad = parseFloat(fila.querySelector('.cantidad-input').value) || 0;
            const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;
            total += cantidad * precio;
        });
        document.getElementById('montoTotal').textContent = formatoMoneda(total);
        document.getElementById('montoTotalInput').value = total.toFixed(2);
        return total;
    }

    // Agregar nueva línea solo si no está cancelada
    if (!esEditableCancelada) {
        const btnAgregarLinea = document.getElementById('agregarLinea');
        if (btnAgregarLinea) {
            btnAgregarLinea.addEventListener('click', function() {
                const template = document.getElementById('lineaTemplate');
                const clone = template.content.cloneNode(true);
                document.getElementById('detallesBody').appendChild(clone);
                agregarEventosLinea();
            });
        }
    }

    // Agregar eventos a una línea
    function agregarEventosLinea() {
        const filas = document.querySelectorAll('.linea-detalle');
        const fila = filas[filas.length - 1];

        // Cargar precio al seleccionar inventario
        fila.querySelector('.inventario-select').addEventListener('change', function() {
            const precio = this.options[this.selectedIndex].dataset.precio || 0;
            fila.querySelector('.precio-input').value = precio;
            calculaSubtotal(fila);
            calculaTotal();
        });

        // Actualizar subtotal al cambiar cantidad o precio
        fila.querySelector('.cantidad-input').addEventListener('input', function() {
            calculaSubtotal(fila);
            calculaTotal();
        });

        fila.querySelector('.precio-input').addEventListener('input', function() {
            calculaSubtotal(fila);
            calculaTotal();
        });

        // Eliminar línea
        const btnEliminar = fila.querySelector('.eliminar-linea');
        if (btnEliminar) {
            btnEliminar.addEventListener('click', function(e) {
                e.preventDefault();
                fila.remove();
                calculaTotal();
            });
        }
    }

    // Inicializar líneas existentes
    document.querySelectorAll('.linea-detalle').forEach((fila, index) => {
        if (!esEditableCancelada) {
            agregarEventosLinea();
        }
    });

    // Calcular total inicial si hay líneas
    if (document.querySelectorAll('.linea-detalle').length > 0) {
        calculaTotal();
    }
})();
</script>
@endsection
