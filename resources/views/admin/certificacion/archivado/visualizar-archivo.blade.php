@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-5xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.archivado.gestor') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver al Gestor
            </a>
            <h1 class="text-3xl font-bold text-gray-900">📦 Visualizador de Archivo</h1>
        </div>

        <!-- Información del Archivo -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-gray-900 mb-4">📋 Detalles del Archivo</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600 font-medium">Folio</p>
                    <p class="text-lg font-bold text-gray-900">{{ $archivo->historico->fk_folio ?? 'N/A' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">UUID</p>
                    <p class="text-sm font-mono text-gray-700">{{ $archivo->uuid_archivo }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Tamaño</p>
                    <p class="text-lg font-bold text-green-600">{{ round($archivo->tamanio_bytes / 1024, 2) }} KB</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Tipo Compresión</p>
                    <p class="text-lg font-bold text-blue-600">{{ $archivo->tipo_compresion }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Archivado por</p>
                    <p class="text-lg text-gray-900">{{ $archivo->usuarioArchivador->email ?? 'Sistema' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600 font-medium">Fecha</p>
                    <p class="text-lg text-gray-900">{{ $archivo->created_at->format('d/m/Y H:i:s') }}</p>
                </div>
            </div>
        </div>

        <!-- Hash de Integridad -->
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">🔒 Hash de Integridad</h2>
            <div class="bg-gray-100 p-4 rounded border border-gray-300 font-mono text-xs break-all">
                {{ $archivo->hash_integridad }}
            </div>
            <p class="text-sm text-gray-600 mt-2">SHA-256 para verificación de integridad del archivo</p>
        </div>

        <!-- Motivo de Archivado -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-4 rounded mb-6">
            <p class="text-sm text-blue-700">
                <strong>Motivo:</strong> {{ $archivo->motivo_archivado }}
            </p>
        </div>

        <!-- Historial de Versiones -->
        @if($resultado['exito'] && $resultado['versiones']->count() > 0)
        <div class="bg-white rounded-lg shadow p-6 mb-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📈 Historial de Cambios ({{ $resultado['total_versiones'] }} versiones)</h2>
            <div class="space-y-3">
                @foreach($resultado['versiones']->take(10) as $version)
                <div class="border-l-4 border-purple-500 pl-4 py-2 bg-gray-50 p-3 rounded">
                    <div class="flex justify-between items-start">
                        <div>
                            <p class="font-bold text-gray-900">v{{ $version['numero'] }} - {{ $version['tipo_cambio'] }}</p>
                            <p class="text-sm text-gray-600">{{ $version['descripcion'] }}</p>
                        </div>
                        <span class="text-xs text-gray-500">{{ $version['fecha'] }}</span>
                    </div>
                </div>
                @endforeach
            </div>
            @if($resultado['total_versiones'] > 10)
            <a href="{{ route('certificacion.archivado.historial-versiones', $archivo->id_historico) }}" 
                class="mt-4 inline-block text-blue-600 hover:text-blue-800">
                Ver todas las versiones ({{ $resultado['total_versiones'] }})
            </a>
            @endif
        </div>
        @endif

        <!-- Acciones -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">⚙️ Acciones</h2>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('certificacion.archivado.descargar', $archivo->id_archivo) }}" 
                    class="inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    ⬇️ Descargar ZIP
                </a>
                <button onclick="restaurarCertificado({{ $archivo->id_archivo }})" 
                    class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    ↩️ Restaurar Certificado
                </button>
                <a href="{{ route('certificacion.archivado.gestor') }}" 
                    class="inline-block bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500">
                    ← Volver
                </a>
            </div>
        </div>
    </div>
</div>

<script>
function restaurarCertificado(idArchivo) {
    if (!confirm('¿Restaurar los datos de este certificado desde el archivo?')) {
        return;
    }
    
    fetch('{{ route("certificacion.archivado.restaurar", "") }}/' + idArchivo, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.exito) {
            alert('Certificado restaurado exitosamente');
            location.reload();
        } else {
            alert('Error: ' + data.mensaje);
        }
    });
}
</script>
@endsection
