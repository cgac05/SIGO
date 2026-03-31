@extends('layouts.app')

@section('title', 'Escanear Documentos - SIGO')

@section('content')
<div class="container mx-auto py-12 px-4">
    
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-900">Caso A: Momento 2</h1>
        <p class="text-lg text-gray-600 mt-2">Escanear y Cargar Documentos</p>
        <p class="text-sm text-gray-500 mt-1">Admin escanea documentos físicos de forma async</p>
    </div>

    <!-- Instrucciones -->
    <div class="bg-blue-50 border-l-4 border-blue-600 p-4 rounded-lg mb-8">
        <p class="text-sm text-gray-700">
            <strong>Flujo:</strong> 
            1️⃣ Busca el folio del expediente (generado en Momento 1)
            2️⃣ Escanea cada documento (PDF, JPG, PNG - máx 5 MB)
            3️⃣ Verifica integridad (hash SHA256)
            4️⃣ Confirma carga cuando todos documentos están listos
        </p>
    </div>

    <!-- Panel Principal -->
    <div class="grid grid-cols-3 gap-8">
        
        <!-- Columna Izq: Buscar Folio -->
        <div class="col-span-3 lg:col-span-1">
            <div class="bg-white rounded-lg shadow-lg p-6 sticky top-4">
                <h2 class="text-xl font-bold text-gray-900 mb-4">Buscar Expediente</h2>

                <div class="mb-6">
                    <label for="folio_search" class="block text-sm font-medium text-gray-700 mb-2">
                        Folio (o escanea QR)
                    </label>
                    <input 
                        type="text" 
                        id="folio_search"
                        placeholder="SIGO-2026-CASO-A-..."
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent"
                    >
                    <p class="text-xs text-gray-500 mt-2">💡 Puedes usar el lector QR o escribir manualmente</p>
                </div>

                <button 
                    type="button"
                    onclick="buscarFolio()"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 mb-4"
                >
                    🔍 Buscar
                </button>

                <!-- Expediente Seleccionado -->
                <div id="expediente_info" class="bg-gray-50 p-4 rounded-lg hidden">
                    <p class="text-xs text-gray-600">Expediente Encontrado</p>
                    <p class="text-lg font-bold text-gray-900" id="folio_display"></p>
                    <p class="text-sm text-gray-600 mt-2" id="beneficiario_display"></p>
                    <p class="text-xs text-gray-500 mt-3">
                        Estado: <span id="estado_display" class="font-semibold text-yellow-600">En Carga</span>
                    </p>
                </div>

                <!-- Estado de Documentos -->
                <div id="docs_summary" class="mt-6 hidden">
                    <h3 class="text-sm font-bold text-gray-900 mb-3">Resumen</h3>
                    <div class="space-y-2">
                        <div class="flex justify-between text-sm">
                            <span>Documentos cargados:</span>
                            <span class="font-semibold" id="docs_count">0/3</span>
                        </div>
                        <div class="w-full bg-gray-300 rounded-full h-2">
                            <div id="progress_bar" class="bg-indigo-600 h-2 rounded-full transition-all" style="width: 0%"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Columna Der: Carga de Documentos -->
        <div class="col-span-3 lg:col-span-2">
            <div class="bg-white rounded-lg shadow-lg p-8">
                <h2 class="text-xl font-bold text-gray-900 mb-6">Cargar Documentos</h2>

                <!-- Drop Zone -->
                <div 
                    id="drop_zone"
                    class="border-2 border-dashed border-indigo-300 rounded-lg p-8 text-center mb-8 cursor-pointer transition bg-indigo-50 hover:bg-indigo-100"
                    ondrop="handleDrop(event)"
                    ondragover="event.preventDefault(); event.target.closest('#drop_zone').classList.add('border-indigo-500', 'bg-indigo-100')"
                    ondragleave="event.target.closest('#drop_zone').classList.remove('border-indigo-500', 'bg-indigo-100')"
                >
                    <svg class="w-12 h-12 text-indigo-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                    </svg>
                    <p class="text-lg font-semibold text-gray-900 mb-2">Arrastra archivos aquí</p>
                    <p class="text-sm text-gray-600 mb-4">o haz click para seleccionar</p>
                    <input 
                        type="file" 
                        id="file_input"
                        multiple
                        accept=".pdf,.jpg,.jpeg,.png"
                        onchange="handleFiles(this.files)"
                        onclick="event.stopPropagation()"
                        class="hidden"
                    >
                    <p class="text-xs text-gray-500">PDF, JPG, PNG - Máx 5 MB cada archivo</p>
                </div>

                <!-- Documentos Cargados -->
                <div id="uploaded_files" class="space-y-4">
                    <!-- Se agregan dinámicamente -->
                </div>

                <!-- Selector de Tipo de Documento -->
                <div class="bg-gray-50 p-4 rounded-lg mb-6 hidden" id="tipo_doc_selector">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Tipo de Documento (últimos cargados)
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center gap-2">
                            <input type="radio" name="tipo_doc" value="CEDULA" class="text-indigo-600" checked>
                            <span class="text-sm">📋 Cédula de Identidad</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="tipo_doc" value="COMPROBANTE_DOMICILIO" class="text-indigo-600">
                            <span class="text-sm">🏠 Comprobante de Domicilio</span>
                        </label>
                        <label class="flex items-center gap-2">
                            <input type="radio" name="tipo_doc" value="COMPROBANTE_INGRESOS" class="text-indigo-600">
                            <span class="text-sm">💼 Comprobante de Ingresos</span>
                        </label>
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex gap-4">
                    <a 
                        href="{{ route('dashboard') }}"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-900 font-semibold py-3 px-4 rounded-lg transition duration-200 text-center"
                    >
                        Cancelar
                    </a>
                    <button 
                        id="confirm_btn"
                        type="button"
                        onclick="confirmarCarga()"
                        class="flex-1 bg-green-600 hover:bg-green-700 text-white font-semibold py-3 px-4 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2"
                        disabled
                    >
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Confirmar Carga
                    </button>
                </div>

                <!-- Mensaje de Éxito (oculto por defecto) -->
                <div id="success_message" class="mt-6 bg-green-50 border border-green-200 text-green-800 px-4 py-3 rounded-lg hidden">
                    <p class="font-semibold">✓ Documentos cargados exitosamente</p>
                    <p class="text-sm mt-1">Los documentos han sido verificados y guardados.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function handleDrop(e) {
    e.preventDefault();
    const files = e.dataTransfer.files;
    handleFiles(files);
}

function handleFiles(files) {
    // TODO: Implementar lógica de carga con validación
    // - Validar tipo MIME
    // - Mostrar preview
    // - Calcular SHA256
    // - Enviar a servidor
    console.log('Archivos seleccionados:', files);
}

function buscarFolio() {
    // TODO: Buscar folio en BD y cargar información
    console.log('Buscando folio...');
}

function confirmarCarga() {
    // TODO: Confirmar y enviar formulario
    console.log('Confirmando carga...');
}
</script>
@endsection
