@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">📦 Gestión de Archivado y Backup</h1>
            <p class="mt-2 text-gray-600">Archivamiento seguro, versionado y recuperación de certificados digitales</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <!-- Archivos Activos -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Archivos Activos</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['estadisticas']['total_archivos_activos'] ?? 0 }}</p>
                    </div>
                    <div class="text-4xl text-blue-200">📦</div>
                </div>
            </div>

            <!-- Tamaño Total -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Tamaño Total</p>
                        <p class="text-3xl font-bold text-green-600">{{ $estadisticas['estadisticas']['tamanio_total_mb'] ?? 0 }} MB</p>
                    </div>
                    <div class="text-4xl text-green-200">💾</div>
                </div>
            </div>

            <!-- Versiones -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Versiones</p>
                        <p class="text-3xl font-bold text-purple-600">{{ $estadisticas['estadisticas']['total_versiones'] ?? 0 }}</p>
                    </div>
                    <div class="text-4xl text-purple-200">📋</div>
                </div>
            </div>

            <!-- Este Mes -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Este Mes</p>
                        <p class="text-3xl font-bold text-orange-600">{{ $estadisticas['estadisticas']['archivos_este_mes'] ?? 0 }}</p>
                    </div>
                    <div class="text-4xl text-orange-200">📅</div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Acciones -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <!-- Archivamiento Individual -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <h2 class="text-xl font-bold text-gray-900 mb-3">📦 Gestor de Archivos</h2>
                <p class="text-gray-600 mb-4">Ver y gestionar todos los archivos activos</p>
                <a href="{{ route('certificacion.archivado.gestor') }}" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Ir al Gestor
                </a>
            </div>

            <!-- Archivamiento Masivo -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                <h2 class="text-xl font-bold text-gray-900 mb-3">🔄 Archivamiento Masivo</h2>
                <p class="text-gray-600 mb-4">Archivar múltiples certificados según filtros</p>
                <a href="{{ route('certificacion.archivado.formulario-masivo') }}" class="inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Archivamiento Masivo
                </a>
            </div>

            <!-- Backup -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                <h2 class="text-xl font-bold text-gray-900 mb-3">💾 Backup Masivo</h2>
                <p class="text-gray-600 mb-4">Generar backup comprimido de certificados</p>
                <form action="{{ route('certificacion.archivado.descargar-backup') }}" method="GET" class="inline">
                    <button type="submit" class="inline-block bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                        Descargar Backup
                    </button>
                </form>
            </div>
        </div>

        <!-- Últimos Archivos Creados -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">📅 Últimos Archivos Creados</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Folio</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">UUID</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Tamaño</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Usuario</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Fecha</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($ultimos_archivos as $archivo)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-blue-600 font-bold">
                                {{ $archivo->historico->fk_folio ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700 font-mono text-xs">
                                {{ substr($archivo->uuid_archivo, 0, 12) }}...
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ round($archivo->tamanio_bytes / 1024, 2) }} KB
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ $archivo->usuarioArchivador->email ?? 'Sistema' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $archivo->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-6 py-3 text-sm space-x-2">
                                <a href="{{ route('certificacion.archivado.ver', $archivo->id_archivo) }}" class="text-blue-600 hover:text-blue-800">
                                    Ver
                                </a>
                                <a href="{{ route('certificacion.archivado.descargar', $archivo->id_archivo) }}" class="text-green-600 hover:text-green-800">
                                    Descargar
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="px-6 py-4 text-center text-gray-500">Sin archivos registrados</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Cambios Recientes -->
        @if($cambios_recientes->count() > 0)
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">🔔 Cambios Recientes</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Tipo de Cambio</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Descripción</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Usuario</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Fecha</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @foreach($cambios_recientes as $cambio)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm">
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                    {{ $cambio->tipo_cambio }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ $cambio->descripcion ?? 'Sin descripción' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ $cambio->usuario->email ?? 'Sistema' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-600">
                                {{ $cambio->created_at->format('d/m/Y H:i:s') }}
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
@endsection
