@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.verificacion.formulario-lote') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver a Búsqueda
            </a>
            <h1 class="text-3xl font-bold text-gray-900">📊 Resultados de Validación</h1>
            <p class="text-gray-600 mt-1">{{ $certificados->total() }} certificado(s) encontrado(s)</p>
        </div>

        <!-- Estadísticas Rápidas -->
        @php
            $validos = collect($resultados_validacion)->where(0, '==', 'VÁLIDO')->count();
            $con_alertas = $certificados->total() - $validos;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Total Certificados</p>
                <p class="text-3xl font-bold text-blue-600">{{ $certificados->total() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Válidos</p>
                <p class="text-3xl font-bold text-green-600">{{ $validos }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Con Alertas</p>
                <p class="text-3xl font-bold text-yellow-600">{{ $con_alertas }}</p>
            </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-x-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">📋 Resultado de Validaciones</h2>
            </div>

            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Folio</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Monto</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Beneficiario</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Fecha Entrega</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Resultado</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody divide-y divide-gray-200">
                    @forelse($certificados as $cert)
                    @php
                        $resultado = $resultados_validacion[$cert->id_historico] ?? 'DESCONOCIDO';
                        $es_valido = $resultado === 'VÁLIDO';
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-sm text-blue-600 font-bold">
                            {{ $cert->fk_folio }}
                        </td>
                        <td class="px-6 py-3 text-sm text-green-600 font-medium">
                            ${{ number_format($cert->monto_entregado, 2) }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-700">
                            {{ $cert->solicitud->beneficiario->display_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-600">
                            {{ $cert->fecha_entrega->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-3 text-sm">
                            @if($es_valido)
                                <span class="inline-block px-3 py-1 bg-green-100 text-green-800 rounded-full font-medium text-xs">
                                    ✓ VÁLIDO
                                </span>
                            @else
                                <span class="inline-block px-3 py-1 bg-yellow-100 text-yellow-800 rounded-full font-medium text-xs">
                                    ⚠ {{ $resultado }}
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-3 text-sm">
                            <a href="{{ route('certificacion.verificacion.formulario', $cert->id_historico) }}" 
                                class="text-blue-600 hover:text-blue-800">
                                Ver Detalles
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Sin certificados para mostrar
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <!-- Paginación -->
            @if($certificados->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $certificados->links() }}
            </div>
            @endif
        </div>

        <!-- Acciones de Descarga -->
        <div class="bg-white rounded-lg shadow p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📥 Descargar Reportes</h2>
            <p class="text-gray-600 mb-4">Selecciona los certificados cuyos reportes deseas descargar en ZIP</p>

            <form id="formDescargarLote" method="POST" action="{{ route('certificacion.verificacion.descargar-lote') }}">
                @csrf
                <div class="max-h-96 overflow-y-auto border border-gray-300 rounded-lg p-4 mb-4">
                    @forelse($certificados as $cert)
                    <div class="flex items-center mb-3">
                        <input type="checkbox" name="ids[]" value="{{ $cert->id_historico }}" 
                            class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
                            id="cert_{{ $cert->id_historico }}">
                        <label for="cert_{{ $cert->id_historico }}" class="ml-3 cursor-pointer flex-1">
                            <span class="text-blue-600 font-bold">{{ $cert->fk_folio }}</span>
                            <span class="text-gray-600 ml-2">${{ number_format($cert->monto_entregado, 2) }}</span>
                        </label>
                    </div>
                    @empty
                    <p class="text-gray-500">Sin certificados para descargar</p>
                    @endforelse
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="seleccionarTodos()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">
                        Seleccionar Todos
                    </button>
                    <button type="submit" class="px-4 py-2 bg-green-500 text-white rounded hover:bg-green-600">
                        📦 Descargar Reportes (ZIP)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function seleccionarTodos() {
    document.querySelectorAll('input[name="ids[]"]').forEach(checkbox => {
        checkbox.checked = true;
    });
}
</script>
@endsection
