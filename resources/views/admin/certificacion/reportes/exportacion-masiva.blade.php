@extends('layouts.app')

@section('title', 'Exportación Masiva de Certificados')

@section('content')
<div class="container mx-auto p-6">
    <div class="mb-8">
        <a href="{{ route('certificacion.reportes.dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">← Volver</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">📦 Exportación Masiva en ZIP</h1>
        <p class="text-gray-600 mt-2">Descarga múltiples certificados en un archivo comprimido</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
        <!-- Tabla de Certificados -->
        <div class="lg:col-span-3 bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-purple-500 to-purple-600 text-white px-6 py-4">
                <h2 class="text-xl font-bold">🔐 Certificados Disponibles</h2>
                <p class="text-purple-100">{{ $certificados->total() }} certificados encontrados</p>
            </div>

            <form id="formExportacionMasiva" action="{{ route('certificacion.exportar.zip') }}" method="POST">
                @csrf

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-100 border-b sticky top-0">
                            <tr>
                                <th class="px-6 py-3 text-left">
                                    <input type="checkbox" id="selectAll" class="w-4 h-4 rounded">
                                </th>
                                <th class="px-6 py-3 text-left text-sm font-bold">Folio</th>
                                <th class="px-6 py-3 text-right text-sm font-bold">Monto</th>
                                <th class="px-6 py-3 text-center text-sm font-bold">Beneficiario</th>
                                <th class="px-6 py-3 text-center text-sm font-bold">Fecha</th>
                                <th class="px-6 py-3 text-center text-sm font-bold">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($certificados as $cert)
                            <tr class="border-b hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <input type="checkbox" name="ids[]" value="{{ $cert->id_historico }}" class="w-4 h-4 rounded checkbox-item">
                                </td>
                                <td class="px-6 py-4 font-mono text-blue-600 font-bold">{{ $cert->fk_folio }}</td>
                                <td class="px-6 py-4 text-right font-bold text-green-600">${{ number_format($cert->monto_entregado, 2) }}</td>
                                <td class="px-6 py-4 text-center text-sm">{{ $cert->solicitud->beneficiario->display_name ?? 'N/A' }}</td>
                                <td class="px-6 py-4 text-center text-sm">{{ $cert->fecha_certificacion->format('d/m/Y') }}</td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1 rounded text-sm font-bold
                                        @if($cert->estado_certificacion === 'CERTIFICADO') bg-green-100 text-green-800
                                        @else bg-blue-100 text-blue-800 @endif">
                                        {{ $cert->estado_certificacion }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    Sin certificados disponibles
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Información de Selección -->
                <div class="px-6 py-4 bg-gray-50 border-t">
                    <p class="text-sm text-gray-600">
                        📋 <strong id="selectCount">0</strong> certificado(s) seleccionado(s)
                        <span id="totalMonto" class="ml-4">Total: $0.00</span>
                    </p>
                </div>

                <!-- Botón Descargar -->
                <div class="px-6 py-4 bg-white border-t flex gap-3">
                    <button type="submit" id="btnDescargar" disabled 
                            class="flex-1 bg-purple-500 hover:bg-purple-600 disabled:bg-gray-400 text-white font-bold py-2 px-4 rounded transition">
                        📦 Descargar ZIP
                    </button>
                    <button type="button" onclick="descargarTodos()" 
                            class="bg-blue-500 hover:bg-blue-600 text-white font-bold py-2 px-4 rounded transition">
                        ☑️ Seleccionar Todos
                    </button>
                </div>

                <!-- Paginación -->
                <div class="px-6 py-4 bg-white border-t">
                    {{ $certificados->links() }}
                </div>
            </form>
        </div>

        <!-- Sidebar: Información -->
        <div class="space-y-6">
            <!-- Card: Información -->
            <div class="bg-blue-50 rounded-lg shadow p-6 border-l-4 border-blue-500">
                <h3 class="font-bold text-blue-900 mb-3">ℹ️ Información</h3>
                <ul class="text-sm text-blue-800 space-y-2">
                    <li>✓ Selecciona certificados</li>
                    <li>✓ Se descargarán como PDF</li>
                    <li>✓ Se comprimirán en ZIP</li>
                    <li>✓ Máximo 100 archivos</li>
                </ul>
            </div>

            <!-- Card: Beneficios -->
            <div class="bg-green-50 rounded-lg shadow p-6 border-l-4 border-green-500">
                <h3 class="font-bold text-green-900 mb-3">✨ Beneficios</h3>
                <ul class="text-sm text-green-800 space-y-2">
                    <li>📥 Todos en 1 archivo</li>
                    <li>🔐 Comprimido (ZIP)</li>
                    <li>📊 Mejora rendimiento</li>
                    <li>🚀 Descarga rápida</li>
                </ul>
            </div>

            <!-- Card: Tamaño Estimado -->
            <div class="bg-purple-50 rounded-lg shadow p-6 border-l-4 border-purple-500">
                <h3 class="font-bold text-purple-900 mb-2">📦 Tamaño Estimado</h3>
                <p class="text-3xl font-bold text-purple-600 mb-1" id="estimadoTamanio">Selecciona...</p>
                <p class="text-xs text-purple-700">~200KB por PDF</p>
            </div>

            <!-- Card: Consejos -->
            <div class="bg-yellow-50 rounded-lg shadow p-6 border-l-4 border-yellow-500">
                <h3 class="font-bold text-yellow-900 mb-2">💡 Consejos</h3>
                <p class="text-sm text-yellow-800">
                    Para descargas grandes, considera seleccionar por períodos mensuales.
                </p>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
// Seleccionar/Deseleccionar todos
document.getElementById('selectAll').addEventListener('change', function() {
    document.querySelectorAll('.checkbox-item').forEach(cb => {
        cb.checked = this.checked;
    });
    actualizarConteo();
});

// Actualizar conteo cuando se selecciona un checkbox
document.querySelectorAll('.checkbox-item').forEach(cb => {
    cb.addEventListener('change', function() {
        actualizarConteo();
    });
});

function actualizarConteo() {
    const checkboxes = document.querySelectorAll('.checkbox-item:checked');
    const cantidad = checkboxes.length;
    
    document.getElementById('selectCount').textContent = cantidad;
    document.getElementById('btnDescargar').disabled = cantidad === 0;
    
    // Calcular tamaño estimado
    const tamanioEstimado = Math.ceil(cantidad * 0.2); // 200KB por PDF
    document.getElementById('estimadoTamanio').textContent = tamanioEstimado + ' MB';
}

function descargarTodos() {
    document.querySelectorAll('.checkbox-item').forEach(cb => cb.checked = true);
    document.getElementById('selectAll').checked = true;
    actualizarConteo();
}

// Submit form con AJAX para mejor UX
document.getElementById('formExportacionMasiva').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const checkboxes = Array.from(document.querySelectorAll('.checkbox-item:checked'))
        .map(cb => cb.value);
    
    if (checkboxes.length === 0) {
        alert('Selecciona al menos un certificado');
        return;
    }

    // Crear un formulario temporal para descargar
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '{{ route("certificacion.exportar.zip") }}';
    
    // CSRF token
    const csrfInput = document.createElement('input');
    csrfInput.type = 'hidden';
    csrfInput.name = '_token';
    csrfInput.value = '{{ csrf_token() }}';
    form.appendChild(csrfInput);

    // Agregar IDs
    checkboxes.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'ids[]';
        input.value = id;
        form.appendChild(input);
    });

    document.body.appendChild(form);
    form.submit();
    document.body.removeChild(form);
});
</script>
@endsection
