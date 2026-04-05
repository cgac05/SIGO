@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900">Registrar Nuevo Desembolso</h1>
        <p class="text-gray-600 mt-1">Complete el formulario para registrar un pago a beneficiario</p>
    </div>

    <!-- Back Button -->
    <a href="{{ route('desembolsos.index') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-block">
        <i class="fas fa-arrow-left"></i> Volver a Desembolsos
    </a>

    <!-- Form -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <form action="{{ route('desembolsos.store') }}" method="POST" class="p-6">
            @csrf

            <!-- Info Alert -->
            <div class="bg-blue-50 border-l-4 border-blue-600 p-4 mb-6">
                <div class="flex items-start">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-600 text-lg"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm text-blue-700">
                            <strong>Instrucciones:</strong> Seleccione la solicitud, ingrese el monto a desembolsar 
                            y confirme el pago. El sistema validará automáticamente el presupuesto disponible.
                        </p>
                    </div>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Solicitud Selection -->
                <div>
                    <label for="folio" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-alt"></i> Solicitud de Beneficiario *
                    </label>
                    <select name="folio" id="folio" required 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('folio') border-red-500 @enderror"
                        onchange="actualizarPresupuesto()">
                        <option value="">-- Seleccione una solicitud --</option>
                        @foreach($solicitudes_pendientes as $solicitud)
                            <option value="{{ $solicitud->folio }}" 
                                data-presupuesto="{{ $solicitud->presupuesto_asignado }}"
                                data-entregado="{{ $solicitud->monto_entregado }}"
                                @if(old('folio') == $solicitud->folio) selected @endif>
                                {{ $solicitud->folio }} - {{ $solicitud->beneficiario->nombre ?? 'Sin nombre' }} 
                                (Presupuesto: ${{ number_format($solicitud->presupuesto_asignado ?? 0, 2) }})
                            </option>
                        @endforeach
                    </select>
                    @error('folio')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Budget Info -->
                <div id="budget-info" class="hidden space-y-3 bg-gray-50 p-4 rounded-lg">
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <p class="text-xs text-gray-600">Presupuesto Asignado</p>
                            <p class="text-lg font-semibold text-gray-900" id="presupuesto-asignado">$0.00</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Ya Entregado</p>
                            <p class="text-lg font-semibold text-blue-600" id="ya-entregado">$0.00</p>
                        </div>
                        <div>
                            <p class="text-xs text-gray-600">Disponible</p>
                            <p class="text-lg font-semibold text-green-600" id="disponible">$0.00</p>
                        </div>
                    </div>
                </div>

                <!-- Monto -->
                <div>
                    <label for="monto" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-money-bill"></i> Monto a Desembolsar *
                    </label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-500">$</span>
                        <input type="number" name="monto" id="monto" step="0.01" min="0" required
                            placeholder="0.00"
                            class="w-full pl-8 pr-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('monto') border-red-500 @enderror"
                            value="{{ old('monto') }}"
                            onchange="validarMonto()"
                            oninput="validarMonto()">
                    </div>
                    <p class="mt-2 text-sm text-gray-600">
                        <i class="fas fa-exclamation-circle text-yellow-600"></i> 
                        El monto no debe exceder el presupuesto disponible
                    </p>
                    @error('monto')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div id="monto-error" class="mt-2 text-sm text-red-600 hidden"></div>
                </div>

                <!-- Ruta PDF -->
                <div>
                    <label for="ruta_pdf" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-file-pdf"></i> Ruta del Comprobante (PDF)
                    </label>
                    <input type="text" name="ruta_pdf" id="ruta_pdf"
                        placeholder="ej: /documentos/comprobante_2024_001.pdf"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                        value="{{ old('ruta_pdf') }}">
                    <p class="mt-1 text-xs text-gray-500">Ruta al archivo PDF del comprobante de pago</p>
                </div>

                <!-- Descripción -->
                <div>
                    <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                        <i class="fas fa-sticky-note"></i> Descripción (Opcional)
                    </label>
                    <textarea name="descripcion" id="descripcion" rows="4"
                        placeholder="Ingrese notas o detalles adicionales del pago..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">{{ old('descripcion') }}</textarea>
                    <p class="mt-1 text-xs text-gray-500">Campo opcional para referencias adicionales</p>
                </div>

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-600 p-4">
                        <div class="flex items-start">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-600"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Errores en el formulario:</h3>
                                <ul class="mt-2 list-disc list-inside text-sm text-red-700">
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Buttons -->
            <div class="flex gap-4 mt-8 border-t pt-6">
                <a href="{{ route('desembolsos.index') }}" 
                    class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-6 py-2 rounded-lg text-center">
                    <i class="fas fa-times"></i> Cancelar
                </a>
                <button type="submit" 
                    class="flex-1 bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg">
                    <i class="fas fa-check"></i> Registrar Desembolso
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function actualizarPresupuesto() {
    const select = document.getElementById('folio');
    const option = select.options[select.selectedIndex];
    
    if (option.value) {
        const presupuesto = parseFloat(option.dataset.presupuesto || 0);
        const entregado = parseFloat(option.dataset.entregado || 0);
        const disponible = presupuesto - entregado;
        
        document.getElementById('presupuesto-asignado').textContent = 
            '$' + presupuesto.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        document.getElementById('ya-entregado').textContent = 
            '$' + entregado.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        document.getElementById('disponible').textContent = 
            '$' + disponible.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
        
        document.getElementById('budget-info').classList.remove('hidden');
    } else {
        document.getElementById('budget-info').classList.add('hidden');
    }
}

function validarMonto() {
    const select = document.getElementById('folio');
    const monto = parseFloat(document.getElementById('monto').value || 0);
    const option = select.options[select.selectedIndex];
    
    const presupuesto = parseFloat(option.dataset.presupuesto || 0);
    const entregado = parseFloat(option.dataset.entregado || 0);
    const disponible = presupuesto - entregado;
    
    const errorDiv = document.getElementById('monto-error');
    
    if (monto > disponible) {
        errorDiv.textContent = `El monto excede el presupuesto disponible ($${disponible.toFixed(2)})`;
        errorDiv.classList.remove('hidden');
    } else if (monto > 0) {
        errorDiv.classList.add('hidden');
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    const folio = document.getElementById('folio').value;
    if (folio) {
        actualizarPresupuesto();
    }
});
</script>
@endsection
