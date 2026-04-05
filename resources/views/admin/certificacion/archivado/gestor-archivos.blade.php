@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.archivado.dashboard') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver al Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">📦 Gestor de Archivos Activos</h1>
            <p class="text-gray-600 mt-1">Gestiona todos los certificados archivados</p>
        </div>

        <!-- Estadísticas -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Archivos Activos</p>
                <p class="text-2xl font-bold text-blue-600">{{ $estadisticas['estadisticas']['total_archivos_activos'] ?? 0 }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Tamaño Total</p>
                <p class="text-2xl font-bold text-green-600">{{ $estadisticas['estadisticas']['tamanio_total_mb'] ?? 0 }} MB</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Promedio</p>
                <p class="text-2xl font-bold text-purple-600">{{ $estadisticas['estadisticas']['promedio_tamanio_kb'] ?? 0 }} KB</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Este Mes</p>
                <p class="text-2xl font-bold text-orange-600">{{ $estadisticas['estadisticas']['archivos_este_mes'] ?? 0 }}</p>
            </div>
        </div>

        <!-- Tabla de Archivos -->
        <div class="bg-white rounded-lg shadow overflow-x-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">📋 Lista de Archivos</h2>
            </div>

            @if($archivos_activos->count() > 0)
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Folio</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">UUID</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Tamaño</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Compresión</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Archivado por</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Fecha</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @foreach($archivos_activos as $archivo)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-sm text-blue-600 font-bold">
                            {{ $archivo->historico->fk_folio ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-700 font-mono text-xs">
                            {{ substr($archivo->uuid_archivo, 0, 16) }}...
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-700">
                            {{ round($archivo->tamanio_bytes / 1024, 2) }} KB
                        </td>
                        <td class="px-6 py-3 text-sm">
                            <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                {{ $archivo->tipo_compresion }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-700">
                            {{ $archivo->usuarioArchivador->email ?? 'Sistema' }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-600">
                            {{ $archivo->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-3 text-sm space-x-2">
                            <a href="{{ route('certificacion.archivado.ver', $archivo->id_archivo) }}" 
                                class="text-blue-600 hover:text-blue-800" title="Ver detalles">
                                👁️
                            </a>
                            <a href="{{ route('certificacion.archivado.descargar', $archivo->id_archivo) }}" 
                                class="text-green-600 hover:text-green-800" title="Descargar">
                                ⬇️
                            </a>
                            <a href="{{ route('certificacion.archivado.historial-versiones', $archivo->id_historico) }}" 
                                class="text-purple-600 hover:text-purple-800" title="Ver versiones">
                                📋
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>

            <!-- Paginación -->
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $archivos_activos->links() }}
            </div>
            @else
            <div class="px-6 py-8 text-center text-gray-500">
                <p>No hay archivos activos. <a href="{{ route('certificacion.archivado.formulario-masivo') }}" class="text-blue-600 hover:text-blue-800">Crear archivo</a></p>
            </div>
            @endif
        </div>

        <!-- Acciones Masivas -->
        <div class="mt-8 bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">⚙️ Acciones</h2>
            <div class="flex gap-3 flex-wrap">
                <a href="{{ route('certificacion.archivado.formulario-masivo') }}" 
                    class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    📦 Archivar Masivo
                </a>
                <button onclick="limpiarArchivosAntiguos()" 
                    class="inline-block bg-orange-500 text-white px-4 py-2 rounded hover:bg-orange-600">
                    🧹 Limpiar Antiguos
                </button>
                <form action="{{ route('certificacion.archivado.descargar-backup') }}" method="GET" class="inline">
                    <button type="submit" 
                        class="inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        💾 Descargar Backup
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function limpiarArchivosAntiguos() {
    if (!confirm('¿Eliminar archivos más antiguos de 1 año? Esta acción no se puede deshacer.')) {
        return;
    }
    
    fetch('{{ route("certificacion.archivado.limpiar") }}', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
        }
    })
    .then(response => response.json())
    .then(data => {
        alert(data.mensaje);
        if (data.exito) {
            location.reload();
        }
    });
}
</script>
@endsection
