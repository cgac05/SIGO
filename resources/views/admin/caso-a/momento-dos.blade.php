@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-green-50 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 flex items-center">
                <svg class="w-8 h-8 mr-3 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7-4a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                Momento 2: Escaneo Asincrónico de Documentos
            </h1>
            <p class="text-gray-600 mt-2">Admin: Cargar documentos físicos escaneados después</p>
        </div>
        <!-- Info Alert -->
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-600 rounded-lg">
            <p class="text-green-800 font-semibold">✨ Arquitectura Fusionada: </p>
            <p class="text-green-700 text-sm mt-1">
                Después de escanear aquí, la solicitud entra al <strong>verificador ordinario</strong> (no hay interfaz separada).
                El admin verifica documentos usando la misma interfaz que para beneficiarios normales.
            </p>
        </div>
        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                <p class="text-sm text-gray-600">Pendientes de Escaneo</p>
                <p class="text-2xl font-bold text-yellow-600">{{ $estadisticas['pendientes'] ?? 0 }}</p>
            </div>
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-sm text-gray-600">Documentos Completados</p>
                <p class="text-2xl font-bold text-green-600">{{ $estadisticas['completados'] ?? 0 }}</p>
            </div>
            <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                <p class="text-sm text-gray-600">Con Error</p>
                <p class="text-2xl font-bold text-red-600">{{ $estadisticas['conError'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Área de Carga de Documentos -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-6">Cargar Documento Escaneado</h2>

            <form id="formCargarDocumento" enctype="multipart/form-data" class="space-y-6">
                @csrf

                <!-- Entrada de Folio -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Folio (o escanear QR)
                        </label>
                        <input type="text" 
                               id="folioInput" 
                               name="folio"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               placeholder="Ej: 001-2026-TEP"
                               required>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Tipo de Documento
                        </label>
                        <select name="tipo_documento" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" required>
                            <option value="">-- Seleccionar --</option>
                            <option value="cedula">Cédula de Identidad</option>
                            <option value="comprobante_domicilio">Comprobante de Domicilio</option>
                            <option value="rfc">RFC</option>
                            <option value="certificado_estudios">Certificado de Estudios</option>
                            <option value="constancia_ingresos">Constancia de Ingresos</option>
                            <option value="otro">Otro</option>
                        </select>
                    </div>
                </div>

                <!-- Área de Drag-Drop -->
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Documento (PDF, JPG, PNG - máx 5MB)
                    </label>
                    <div id="dropArea" class="border-2 border-dashed border-green-300 bg-green-50 rounded-lg p-8 text-center cursor-pointer transition hover:border-green-500 hover:bg-green-100">
                        <svg class="w-12 h-12 mx-auto text-green-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <p class="text-gray-700 font-semibold">Arrastra archivo aquí o haz clic</p>
                        <p class="text-sm text-gray-600 mt-1">PDF, JPG, PNG (máximo 5 MB)</p>
                        <input type="file" name="documento" id="fileInput" class="hidden" accept=".pdf,.jpg,.jpeg,.png" required>
                    </div>
                    <div id="preview" class="mt-4 hidden">
                        <img id="previewImg" src="" alt="Preview" class="max-h-48 mx-auto rounded">
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="button" onclick="document.getElementById('fileInput').click()" 
                            class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Seleccionar Archivo
                    </button>
                    <button type="submit" 
                            class="px-8 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-semibold flex items-center ml-auto">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Cargar Documento
                    </button>
                </div>
            </form>
        </div>

        <!-- Historial de Documentos Cargados Hoy -->
        @if($documentosHoy->count() > 0)
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-4">
                    Documentos cargados hoy por {{ $adminNombre }}
                </h2>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 border-b">
                            <tr>
                                <th class="text-left px-4 py-2">Folio</th>
                                <th class="text-left px-4 py-2">Tipo</th>
                                <th class="text-left px-4 py-2">Beneficiario</th>
                                <th class="text-left px-4 py-2">Hora</th>
                                <th class="text-left px-4 py-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($documentosHoy as $doc)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="px-4 py-2 font-mono text-blue-600">{{ $doc->solicitud?->folio_institucional ?? 'N/A' }}</td>
                                    <td class="px-4 py-2 capitalize">{{ str_replace('_', ' ', $doc->tipo_documento) }}</td>
                                    <td class="px-4 py-2">{{ $doc->solicitud?->beneficiario?->nombre ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">{{ $doc->fecha_carga?->format('H:i') ?? 'N/A' }}</td>
                                    <td class="px-4 py-2">
                                        @if($doc->estado_verificacion === 'VERIFICADO')
                                            <span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs font-semibold">✓ Verificado</span>
                                        @elseif($doc->estado_verificacion === 'RECHAZADO')
                                            <span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs font-semibold">✗ Rechazado</span>
                                        @else
                                            <span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs font-semibold">⏱ Pendiente</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
    // Drag-drop
    const dropArea = document.getElementById('dropArea');
    const fileInput = document.getElementById('fileInput');

    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropArea.classList.add('border-green-500', 'bg-green-100');
    });

    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('border-green-500', 'bg-green-100');
    });

    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.classList.remove('border-green-500', 'bg-green-100');
        fileInput.files = e.dataTransfer.files;
        previewFile();
    });

    dropArea.addEventListener('click', () => fileInput.click());

    fileInput.addEventListener('change', previewFile);

    function previewFile() {
        const file = fileInput.files[0];
        if (!file) return;

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = (e) => {
                document.getElementById('previewImg').src = e.target.result;
                document.getElementById('preview').classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        }
    }

    // Envío del formulario
    document.getElementById('formCargarDocumento').addEventListener('submit', async (e) => {
        e.preventDefault();

        const formData = new FormData(this);

        try {
            const response = await fetch('{{ route("caso-a.cargar-documento-momento-dos") }}', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                alert('✓ Documento cargado exitosamente');
                document.getElementById('formCargarDocumento').reset();
                document.getElementById('preview').classList.add('hidden');
                location.reload();
            } else {
                alert('✗ Error: ' + result.error);
            }
        } catch (error) {
            alert('Error al cargar documento: ' + error.message);
        }
    });
</script>
@endsection
