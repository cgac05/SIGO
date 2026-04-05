@extends('layouts.app')

@section('title', 'Certificación Digital de Entregas')

@section('content')
<div class="container mx-auto p-6">
    <!-- Encabezado -->
    <div class="mb-8">
        <h1 class="text-4xl font-bold text-gray-800">🔐 Certificación Digital de Entregas</h1>
        <p class="text-gray-600 mt-2">Genera y valida certificados digitales para desembolsos</p>
    </div>

    <!-- Estadísticas KPI -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <!-- Total Desembolsos -->
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-blue-100 text-sm font-medium">Total Desembolsos</p>
                    <p class="text-3xl font-bold mt-2">{{ $estadisticas['total'] ?? 0 }}</p>
                </div>
                <span class="text-blue-300 text-2xl">📊</span>
            </div>
        </div>

        <!-- Certificados -->
        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-green-100 text-sm font-medium">Certificados</p>
                    <p class="text-3xl font-bold mt-2">{{ $estadisticas['certificados'] ?? 0 }}</p>
                </div>
                <span class="text-green-300 text-2xl">✅</span>
            </div>
        </div>

        <!-- Validados -->
        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-purple-100 text-sm font-medium">Validados</p>
                    <p class="text-3xl font-bold mt-2">{{ $estadisticas['validados'] ?? 0 }}</p>
                </div>
                <span class="text-purple-300 text-2xl">🔍</span>
            </div>
        </div>

        <!-- Pendientes -->
        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-orange-100 text-sm font-medium">Pendientes</p>
                    <p class="text-3xl font-bold mt-2">{{ $estadisticas['pendientes'] ?? 0 }}</p>
                </div>
                <span class="text-orange-300 text-2xl">⏳</span>
            </div>
        </div>

        <!-- Porcentaje Certificación -->
        <div class="bg-gradient-to-br from-indigo-500 to-indigo-600 text-white rounded-lg shadow-lg p-6">
            <div class="flex justify-between items-start">
                <div>
                    <p class="text-indigo-100 text-sm font-medium">% Certificación</p>
                    <p class="text-3xl font-bold mt-2">{{ $estadisticas['porcentaje_certificacion'] ?? 0 }}%</p>
                </div>
                <span class="text-indigo-300 text-2xl">📈</span>
            </div>
        </div>
    </div>

    <!-- Acciones Rápidas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-8">
        <a href="{{ route('certificacion.listado') }}" class="bg-blue-50 border-2 border-blue-200 rounded-lg p-4 hover:bg-blue-100 transition">
            <div class="flex items-center">
                <span class="text-3xl mr-4">📋</span>
                <div>
                    <h3 class="font-bold text-blue-900">Ver Todos</h3>
                    <p class="text-sm text-blue-700">Listado de certificados</p>
                </div>
            </div>
        </a>

        <a href="{{ route('certificacion.index') }}" class="bg-green-50 border-2 border-green-200 rounded-lg p-4 hover:bg-green-100 transition">
            <div class="flex items-center">
                <span class="text-3xl mr-4">🔐</span>
                <div>
                    <h3 class="font-bold text-green-900">Generar Certificado</h3>
                    <p class="text-sm text-green-700">Nuevo certificado digital</p>
                </div>
            </div>
        </a>

        <a href="#buscar" class="bg-purple-50 border-2 border-purple-200 rounded-lg p-4 hover:bg-purple-100 transition">
            <div class="flex items-center">
                <span class="text-3xl mr-4">🔍</span>
                <div>
                    <h3 class="font-bold text-purple-900">Buscar / Validar</h3>
                    <p class="text-sm text-purple-700">Por hash o folio</p>
                </div>
            </div>
        </a>
    </div>

    <!-- Desembolsos Pendientes de Certificación -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden">
        <div class="bg-gradient-to-r from-red-500 to-red-600 text-white px-6 py-4">
            <h2 class="text-2xl font-bold">⏳ Desembolsos Pendientes de Certificación</h2>
            <p class="text-red-100">{{ $desembolsos_pendientes->total() }} desembolsos requieren certificado</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Folio</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Fecha Entrega</th>
                        <th class="px-6 py-3 text-right text-sm font-bold text-gray-700">Monto</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Beneficiario</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($desembolsos_pendientes as $desembolso)
                    <tr class="border-b hover:bg-gray-50 transition">
                        <td class="px-6 py-4 font-mono text-blue-600">{{ $desembolso->fk_folio }}</td>
                        <td class="px-6 py-4 text-sm">{{ $desembolso->fecha_entrega->format('d/m/Y H:i') }}</td>
                        <td class="px-6 py-4 text-right font-bold text-green-600">
                            ${{ number_format($desembolso->monto_entregado, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            {{ $desembolso->solicitud->beneficiario->display_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('certificacion.crear', $desembolso->id_historico) }}" 
                               class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded text-sm font-medium transition">
                                Certificar
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                            ✅ ¡Todos los desembolsos están certificados!
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación -->
        <div class="px-6 py-4 bg-gray-50 border-t">
            {{ $desembolsos_pendientes->links() }}
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Cargar estadísticas en tiempo real cada 30 segundos
    setInterval(function() {
        fetch('{{ route("api.certificacion.estadisticas") }}')
            .then(response => response.json())
            .then(data => {
                // Actualizar KPIs si es necesario
                console.log('Estadísticas actualizadas:', data);
            });
    }, 30000);
</script>
@endsection
