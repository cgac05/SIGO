@extends('layouts.app')

@section('title', 'Crear Ciclo Presupuestario')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('admin.ciclos.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-4 inline-block">
                ← Volver a Ciclos
            </a>
            <h1 class="text-4xl font-bold text-gray-900">➕ Crear Nuevo Ciclo Presupuestario</h1>
            <p class="text-gray-600 mt-2">Define el año fiscal y presupuesto total del ciclo</p>
        </div>

        <!-- Alert Messages - JavaScript Rendered -->
        <div id="messagesContainer" class="mb-6">
            {{-- Errores de validación --}}
            <div id="errorsAlert" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-red-900">Por favor, corrija los siguientes errores:</h3>
                        <ul id="errorsList" class="mt-2 space-y-1 list-disc list-inside text-sm text-red-700"></ul>
                    </div>
                </div>
            </div>

            {{-- Mensaje de éxito --}}
            <div id="successAlert" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                <div class="flex items-start gap-3">
                    <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="font-semibold text-green-900" id="successMessage">✅ Ciclo creado exitosamente</h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        @if($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-600 rounded-lg p-4">
                <p class="text-red-800 font-semibold mb-2">⚠️ Errores encontrados:</p>
                <ul class="list-disc list-inside space-y-1 text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h2 class="text-white text-xl font-semibold">📋 Información del Ciclo</h2>
            </div>

            <form id="formCiclo" action="{{ route('admin.ciclos.store') }}" method="POST" class="p-8" novalidate>
                @csrf

                <!-- Año Fiscal -->
                <div class="mb-6">
                    <label for="ano_fiscal" class="block text-sm font-semibold text-gray-700 mb-2">
                        Año Fiscal <span class="text-red-500">*</span>
                    </label>
                    <input type="number" id="ano_fiscal" name="ano_fiscal" 
                           value="{{ old('ano_fiscal', $proximoAño) }}"
                           min="2020" max="2099"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('ano_fiscal') border-red-500 @enderror"
                           placeholder="2026">
                    @error('ano_fiscal')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-xs mt-1">El año debe estar entre 2020 y 2099</p>
                </div>

                <!-- Presupuesto Total -->
                <div class="mb-6">
                    <label for="presupuesto_total" class="block text-sm font-semibold text-gray-700 mb-2">
                        💰 Presupuesto Total <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-2 text-gray-500 text-lg">$</span>
                        <input type="number" id="presupuesto_total" name="presupuesto_total" 
                               value="{{ old('presupuesto_total') }}"
                               min="0.01" step="0.01"
                               required
                               class="w-full px-4 py-2 pl-8 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 @error('presupuesto_total') border-red-500 @enderror"
                               placeholder="10,000,000.00">
                    </div>
                    @error('presupuesto_total')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-xs mt-1">Tope máximo del presupuesto para este año fiscal</p>
                </div>

                <!-- Fecha de Inicio -->
                <div class="mb-6">
                    <label for="fecha_inicio" class="block text-sm font-semibold text-gray-700 mb-2">
                        📅 Fecha de Inicio
                    </label>
                    <input type="date" id="fecha_inicio" name="fecha_inicio" 
                           value="{{ old('fecha_inicio', now()->format('Y-m-d')) }}"
                           required
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('fecha_inicio')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-xs mt-1">Fecha de inicio del ciclo presupuestario</p>
                </div>

                <!-- Fecha Cierre -->
                <div class="mb-6">
                    <label for="fecha_cierre" class="block text-sm font-semibold text-gray-700 mb-2">
                        📅 Fecha de Cierre
                    </label>
                    <input type="date" id="fecha_cierre" name="fecha_cierre" 
                           value="{{ old('fecha_cierre') }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           min="{{ now()->format('Y-m-d') }}">
                    @error('fecha_cierre')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                    <p class="text-gray-500 text-xs mt-1">Fecha estimada de cierre (opcional)</p>
                </div>

                <!-- Botones -->
                <div class="flex gap-4 pt-6 border-t">
                    <a href="{{ route('admin.ciclos.index') }}" 
                       class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition text-center">
                        Cancelar
                    </a>
                    <button id="btnGuardar" type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition flex items-center justify-center gap-2">
                        <span id="btnLabel">✅ Crear Ciclo</span>
                        <span id="btnSpinner" style="display: none;">
                            <svg class="animate-spin h-4 w-4 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>
        </div>

        <!-- Info Box -->
        <div class="mt-8 bg-blue-50 border-l-4 border-blue-600 rounded-lg p-4">
            <p class="text-blue-800 text-sm">
                💡 <strong>Próximo Paso:</strong> Después de crear el ciclo, podrás agregar categorías presupuestarias y asignar montos específicos para cada una.
            </p>
        </div>
    </div>
</div>

<script>
(function() {
    const formCiclo = document.getElementById('formCiclo');
    const btnGuardar = document.getElementById('btnGuardar');
    const btnLabel = document.getElementById('btnLabel');
    const btnSpinner = document.getElementById('btnSpinner');
    const errorsAlert = document.getElementById('errorsAlert');
    const errorsList = document.getElementById('errorsList');
    const successAlert = document.getElementById('successAlert');
    const successMessage = document.getElementById('successMessage');

    formCiclo.addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('✅ Submit interceptado');

        // Ocultar mensajes anteriores
        errorsAlert.classList.add('hidden');
        successAlert.classList.add('hidden');
        errorsList.innerHTML = '';

        // Mostrar estado cargando
        btnGuardar.disabled = true;
        btnLabel.style.display = 'none';
        btnSpinner.style.display = 'inline';

        try {
            const formData = new FormData(this);
            
            const response = await fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });

            console.log('📤 Respuesta recibida:', response.status, response.statusText);

            const data = await response.json().catch(() => ({}));
            console.log('📋 Datos de respuesta:', data);

            // Manejar errores de validación (422)
            if (response.status === 422) {
                console.log('❌ Errores de validación:', data.errors);
                
                // Limpiar lista anterior
                errorsList.innerHTML = '';

                // Agregar cada error a la lista
                if (data.errors && typeof data.errors === 'object') {
                    Object.entries(data.errors).forEach(([field, messages]) => {
                        if (Array.isArray(messages)) {
                            messages.forEach(message => {
                                const li = document.createElement('li');
                                li.textContent = message;
                                errorsList.appendChild(li);
                                
                                // Marcar el campo con error
                                const fieldElement = document.querySelector(`[name="${field}"]`);
                                if (fieldElement) {
                                    fieldElement.classList.add('border-red-500', 'bg-red-50');
                                    fieldElement.addEventListener('change', () => {
                                        fieldElement.classList.remove('border-red-500', 'bg-red-50');
                                    }, { once: true });
                                }
                            });
                        }
                    });
                }

                // Mostrar alerta de errores
                errorsAlert.classList.remove('hidden');
                
                // Scroll al inicio
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // Manejar otros errores HTTP
            if (!response.ok) {
                console.log('❌ Error del servidor:', response.status);
                
                errorsList.innerHTML = '';
                const li = document.createElement('li');
                li.textContent = data.message || `Error del servidor (${response.status})`;
                errorsList.appendChild(li);
                
                errorsAlert.classList.remove('hidden');
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }

            // Si fue exitoso
            if (data.success) {
                console.log('✅ Ciclo guardado exitosamente');
                
                // Mostrar mensaje de éxito
                if (data.message) {
                    successMessage.textContent = '✅ ' + data.message;
                }
                
                successAlert.classList.remove('hidden');
                
                // Scroll al inicio
                window.scrollTo({ top: 0, behavior: 'smooth' });
                
                // Redirigir después de 2 segundos
                setTimeout(() => {
                    window.location.href = '{{ route("admin.ciclos.index") }}';
                }, 2000);
            }

        } catch (error) {
            console.error('🔥 Error en fetch:', error);
            
            errorsList.innerHTML = '';
            const li = document.createElement('li');
            li.textContent = 'Error de conexión: ' + error.message;
            errorsList.appendChild(li);
            
            errorsAlert.classList.remove('hidden');
            window.scrollTo({ top: 0, behavior: 'smooth' });
        } finally {
            btnGuardar.disabled = false;
            btnLabel.style.display = 'inline';
            btnSpinner.style.display = 'none';
        }
    });
})();
</script>
@endsection
