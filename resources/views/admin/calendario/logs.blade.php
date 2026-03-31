@extends('layouts.app')

@section('title', 'Logs de Sincronización - Google Calendar')

@section('content')
<div class="container mx-auto py-12 px-4">
    
    <!-- Header -->
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-4xl font-bold text-gray-900">Logs de Sincronización</h1>
            <p class="text-lg text-gray-600 mt-2">Google Calendar - Historial de cambios</p>
        </div>
        <a 
            href="{{ route('calendario.config') }}"
            class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition"
        >
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
            </svg>
            Volver
        </a>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-lg shadow p-6 mb-8">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Tipo de Cambio</label>
                <select id="filter-tipo" class="w-full p-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Todos</option>
                    <option value="EVENTO_CREADO">Evento Creado</option>
                    <option value="EVENTO_ACTUALIZADO">Evento Actualizado</option>
                    <option value="EVENTO_ELIMINADO">Evento Eliminado</option>
                    <option value="HITO_COMPLETADO">Hito Completado</option>
                    <option value="ERROR_SINCRONIZACION">Error</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Origen</label>
                <select id="filter-origen" class="w-full p-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Todos</option>
                    <option value="SIGO">SIGO</option>
                    <option value="GOOGLE">Google Calendar</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Estado</label>
                <select id="filter-estado" class="w-full p-2 border border-gray-300 rounded-lg text-sm">
                    <option value="">Todos</option>
                    <option value="1">Sincronizado</option>
                    <option value="0">Pendiente</option>
                    <option value="error">Error</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">Período</label>
                <select id="filter-periodo" class="w-full p-2 border border-gray-300 rounded-lg text-sm">
                    <option value="7">Últimos 7 días</option>
                    <option value="30">Últimos 30 días</option>
                    <option value="90">Últimos 90 días</option>
                    <option value="todas">Todos</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Resumen Estadísticas -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Total Eventos</p>
                    <p class="text-3xl font-bold text-gray-900">{{ $stats['total_eventos'] ?? 0 }}</p>
                </div>
                <div class="text-4xl text-blue-100">📅</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Sincronizados</p>
                    <p class="text-3xl font-bold text-green-600">{{ $stats['sincronizados'] ?? 0 }}</p>
                </div>
                <div class="text-4xl text-green-100">✓</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Pendientes</p>
                    <p class="text-3xl font-bold text-yellow-600">{{ $stats['pendientes'] ?? 0 }}</p>
                </div>
                <div class="text-4xl text-yellow-100">⏳</div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-600 text-sm">Errores</p>
                    <p class="text-3xl font-bold text-red-600">{{ $stats['errores'] ?? 0 }}</p>
                </div>
                <div class="text-4xl text-red-100">⚠</div>
            </div>
        </div>
    </div>

    <!-- Tabla de Logs -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Fecha/Hora</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Tipo de Cambio</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Apoyo/Hito</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Origen</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Usuario</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Estado</th>
                        <th class="px-6 py-3 text-left font-semibold text-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($logs as $log)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4 text-gray-700">
                                <div>
                                    <p class="font-semibold">{{ $log->created_at?->format('d/m/Y') }}</p>
                                    <p class="text-xs text-gray-500">{{ $log->created_at?->format('H:i:s') }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-semibold {{ $log->tipoCambioClass() }}">
                                    {{ $log->getFormatoTipoCambio() }}
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div>
                                    <p class="font-semibold text-gray-900">{{ $log->apoyo->nombre ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500">{{ $log->hito->nombre_hito ?? 'N/A' }}</p>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full {{ $log->origen === 'SIGO' ? 'bg-indigo-500' : 'bg-blue-500' }}"></span>
                                    <span>{{ $log->origen }}</span>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <p class="text-gray-700">{{ $log->usuario->nombre ?? 'Sistema' }}</p>
                            </td>
                            <td class="px-6 py-4">
                                @if($log->sincronizado === 1)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800">
                                        ✓ Sincronizado
                                    </span>
                                @elseif($log->sincronizado === 0)
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800">
                                        ⏳ Pendiente
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800">
                                        ⚠ Error
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <button 
                                    onclick="mostrarDetalle({{ $log->id_log }})"
                                    class="text-indigo-600 hover:text-indigo-800 font-semibold text-sm"
                                >
                                    Ver Detalle
                                </button>
                            </td>
                        </tr>

                        <!-- Fila de Detalle (Oculta) -->
                        <tr id="detalle-{{ $log->id_log }}" class="bg-gray-50 hidden">
                            <td colspan="7" class="px-6 py-4">
                                <div class="space-y-4">
                                    @if($log->datos_anteriores)
                                        <div>
                                            <p class="font-semibold text-gray-900 mb-2">Datos Anteriores</p>
                                            <pre class="bg-white p-3 rounded text-xs text-gray-700 overflow-x-auto border border-gray-200">{{ json_encode($log->datos_anteriores, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif

                                    @if($log->datos_nuevos)
                                        <div>
                                            <p class="font-semibold text-gray-900 mb-2">Datos Nuevos</p>
                                            <pre class="bg-white p-3 rounded text-xs text-gray-700 overflow-x-auto border border-gray-200">{{ json_encode($log->datos_nuevos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        </div>
                                    @endif

                                    @if($log->error_sincronizacion)
                                        <div>
                                            <p class="font-semibold text-red-900 mb-2">Error Capturado</p>
                                            <pre class="bg-red-50 p-3 rounded text-xs text-red-700 overflow-x-auto border border-red-200">{{ $log->error_sincronizacion }}</pre>
                                        </div>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="w-16 h-16 text-gray-300 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p class="text-gray-500 text-lg">No hay registros de sincronización</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        @if($logs instanceof \Illuminate\Pagination\Paginator)
            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    <!-- Leyenda de Estados -->
    <div class="mt-8 bg-blue-50 p-6 rounded-lg border border-blue-200">
        <p class="font-semibold text-blue-900 mb-4">ℹ️ Leyenda de Estados</p>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="text-sm">
                <span class="inline-block w-3 h-3 rounded-full bg-green-500 mr-2"></span>
                <strong>Sincronizado:</strong> El cambio se procesó correctamente
            </div>
            <div class="text-sm">
                <span class="inline-block w-3 h-3 rounded-full bg-yellow-500 mr-2"></span>
                <strong>Pendiente:</strong> El cambio está en cola de procesamiento
            </div>
            <div class="text-sm">
                <span class="inline-block w-3 h-3 rounded-full bg-red-500 mr-2"></span>
                <strong>Error:</strong> Hubo un problema al sincronizar
            </div>
        </div>
    </div>
</div>

<!-- Script para Toggle Detalle -->
<script>
function mostrarDetalle(id) {
    const row = document.getElementById('detalle-' + id);
    row.classList.toggle('hidden');
}

// Filtros (TODO: Implementar con AJAX)
document.getElementById('filter-tipo').addEventListener('change', function() {
    console.log('Filtrar por tipo:', this.value);
    // TODO: Realizar AJAX request
});

document.getElementById('filter-origen').addEventListener('change', function() {
    console.log('Filtrar por origen:', this.value);
    // TODO: Realizar AJAX request
});

document.getElementById('filter-estado').addEventListener('change', function() {
    console.log('Filtrar por estado:', this.value);
    // TODO: Realizar AJAX request
});

document.getElementById('filter-periodo').addEventListener('change', function() {
    console.log('Filtrar por período:', this.value);
    // TODO: Realizar AJAX request
});
</script>
@endsection
