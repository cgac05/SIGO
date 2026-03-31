@extends('layouts.app')

@section('title', 'Crear Expediente Presencial - SIGO')

@section('content')
<div class="container mx-auto py-12 px-4">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Caso A: Momento 1</h1>
        <p class="text-lg text-gray-600 mt-2">Crear Expediente Presencial</p>
        <p class="text-sm text-gray-500 mt-1">Con beneficiario presente - Generar folio y clave privada</p>
    </div>

    <!-- Instrucciones -->
    <div class="bg-blue-50 border-l-4 border-blue-600 p-4 rounded-lg mb-8">
        <p class="text-sm text-gray-700">
            <strong>Flujo:</strong> 
            1️⃣ Beneficiario presenta documentos físicos presencialmente
            2️⃣ Este formulario genera un <strong>folio único</strong> y una <strong>clave privada</strong>
            3️⃣ Se imprime para el beneficiario (guardará esta información)
            4️⃣ Admin procede a escanear documentos en Momento 2
        </p>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-lg shadow-lg p-8 max-w-2xl">
        <form action="{{ route('caso-a.momento-uno.guardar') }}" method="POST" id="formMomentoUno">
            @csrf

            <!-- Sección 1: Información del Beneficiario -->
            <div class="mb-8 pb-8 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Buscar Beneficiario
                </h2>

                <div class="mb-6">
                    <label for="beneficiario_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Beneficiario (por ID o Nombre)
                    </label>
                    <div class="relative">
                        <input 
                            type="text" 
                            id="beneficiario_search"
                            placeholder="Escribe nombre o ID del beneficiario..."
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                            autocomplete="off"
                        >
                        <div id="beneficiario_dropdown" class="absolute top-full left-0 right-0 bg-white border border-gray-300 rounded-lg mt-1 max-h-48 overflow-y-auto hidden z-10"></div>
                    </div>
                    <input type="hidden" name="beneficiario_id" id="beneficiario_id" required>
                    @error('beneficiario_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-gray-50 p-4 rounded-lg" id="beneficiario_info" style="display: none;">
                    <p class="text-sm text-gray-600">Beneficiario Seleccionado</p>
                    <p class="text-lg font-semibold text-gray-900" id="beneficiario_nombre"></p>
                    <p class="text-sm text-gray-600 mt-2" id="beneficiario_email"></p>
                </div>
            </div>

            <!-- Sección 2: Información del Apoyo -->
            <div class="mb-8 pb-8 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                    Seleccionar Apoyo
                </h2>

                <div>
                    <label for="apoyo_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Apoyo Solicitado
                    </label>
                    <select 
                        name="apoyo_id" 
                        id="apoyo_id"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        required
                    >
                        <option value="">-- Seleccionar apoyo --</option>
                        <option value="1">Kit de Útiles Escolares 2026 - En Especie</option>
                        <option value="2">Beca Económica por Desempeño Académico - Económico</option>
                        <option value="3">Capacitación en Oficios - En Especie</option>
                    </select>
                    @error('apoyo_id')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <!-- Sección 3: Verificación Presencial -->
            <div class="mb-8 pb-8 border-b border-gray-200">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Verificación Presencial
                </h2>

                <div class="mb-6">
                    <label for="documento_identidad" class="block text-sm font-medium text-gray-700 mb-2">
                        Número de Documento de Identidad (Cédula/Pasaporte)
                    </label>
                    <input 
                        type="text" 
                        name="documento_identidad" 
                        id="documento_identidad"
                        placeholder="ej. 123456789"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                        required
                    >
                    @error('documento_identidad')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <label class="flex items-center gap-3">
                        <input 
                            type="checkbox" 
                            name="confirmacion_presencia" 
                            id="confirmacion_presencia"
                            class="w-4 h-4 text-green-600 rounded focus:ring-green-500"
                            required
                        >
                        <span class="text-sm text-gray-700">
                            ✓ <strong>Confirmo</strong> que el beneficiario está presente físicamente y he verificado su identidad.
                        </span>
                    </label>
                </div>
            </div>

            <!-- Sección 4: Documentos Listados -->
            <div class="mb-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6 flex items-center gap-2">
                    <svg class="w-6 h-6 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"></path>
                    </svg>
                    Documentos que trae el beneficiario
                </h2>

                <div class="space-y-3">
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="documentos_listados[]" 
                            value="CEDULA"
                            class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                        >
                        <span class="text-gray-700">📋 Cédula de Identidad</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="documentos_listados[]" 
                            value="COMPROBANTE_DOMICILIO"
                            class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                        >
                        <span class="text-gray-700">🏠 Comprobante de Domicilio</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="documentos_listados[]" 
                            value="COMPROBANTE_INGRESOS"
                            class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                        >
                        <span class="text-gray-700">💼 Comprobante de Ingresos</span>
                    </label>
                    <label class="flex items-center gap-3 p-3 border border-gray-200 rounded-lg hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="documentos_listados[]" 
                            value="CONSTANCIA_ESCOLAR"
                            class="w-4 h-4 text-indigo-600 rounded focus:ring-indigo-500"
                        >
                        <span class="text-gray-700">🎓 Constancia Escolar</span>
                    </label>
                </div>
            </div>

            <!-- Botones -->
            <div class="flex gap-4">
                <a 
                    href="{{ route('dashboard') }}"
                    class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-900 font-semibold py-3 px-4 rounded-lg transition duration-200 text-center"
                >
                    Cancelar
                </a>
                <button 
                    type="submit"
                    class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Crear Expediente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Autocomplete Beneficiario (placeholder - conectar a AJAX después)
document.getElementById('beneficiario_search').addEventListener('input', function(e) {
    // TODO: Llamar a API para buscar beneficiarios
    // Por ahora es un placeholder vacío
});
</script>
@endsection
