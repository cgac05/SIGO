@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">🔍 Verificación Digital de Certificados</h1>
            <p class="mt-2 text-gray-600">Validación de integridad, auditoría y cumplimiento LGPDP</p>
        </div>

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <!-- Total Certificados -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Total Certificados</p>
                        <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['total_certificados'] }}</p>
                    </div>
                    <div class="text-4xl text-blue-200">📄</div>
                </div>
            </div>

            <!-- Certificados Validados -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Validados</p>
                        <p class="text-3xl font-bold text-green-600">{{ $estadisticas['certificados_validados'] }}</p>
                    </div>
                    <div class="text-4xl text-green-200">✓</div>
                </div>
            </div>

            <!-- % Validación -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">% Validación</p>
                        <p class="text-3xl font-bold text-purple-600">{{ $estadisticas['porcentaje_validacion'] }}%</p>
                    </div>
                    <div class="text-4xl text-purple-200">📊</div>
                </div>
            </div>

            <!-- Verificaciones (Mes) -->
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="flex-1">
                        <p class="text-gray-500 text-sm font-medium">Este Mes</p>
                        <p class="text-3xl font-bold text-orange-600">{{ $estadisticas['verificaciones_este_mes'] }}</p>
                    </div>
                    <div class="text-4xl text-orange-200">🔐</div>
                </div>
            </div>
        </div>

        <!-- Tarjetas de Acciones -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <!-- Verificación Individual -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <h2 class="text-xl font-bold text-gray-900 mb-3">📋 Verificación Individual</h2>
                <p class="text-gray-600 mb-4">Validar integridad de certificados individuales</p>
                <a href="#" class="inline-block bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Verificar Certificado
                </a>
            </div>

            <!-- Auditoría en Lote -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                <h2 class="text-xl font-bold text-gray-900 mb-3">🔗 Validación en Lote</h2>
                <p class="text-gray-600 mb-4">Validar múltiples certificados según filtros</p>
                <a href="{{ route('certificacion.verificacion.formulario-lote') }}" class="inline-block bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Validación en Lote
                </a>
            </div>
        </div>

        <!-- Últimas Validaciones -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">📅 Últimas Validaciones Realizadas</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Folio</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Tipo Verificación</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Usuario</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Fecha</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody divide-y divide-gray-200">
                        @forelse($ultimas_validaciones as $validacion)
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-blue-600 font-medium">
                                {{ $validacion->historico->fk_folio ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                <span class="inline-block px-2 py-1 bg-blue-100 text-blue-800 rounded text-xs font-medium">
                                    {{ $validacion->tipo_verificacion }}
                                </span>
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ $validacion->usuario->email ?? 'Sistema' }}
                            </td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                {{ $validacion->created_at->format('d/m/Y H:i:s') }}
                            </td>
                            <td class="px-6 py-3 text-sm">
                                @if($validacion->historico)
                                <a href="{{ route('certificacion.verificacion.formulario', $validacion->id_historico) }}" class="text-blue-600 hover:text-blue-800">
                                    Ver Detalles
                                </a>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500">Sin validaciones registradas</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Auditorías por Tipo -->
        @if($auditorias_por_tipo->count() > 0)
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h3 class="text-lg font-bold text-gray-900">📈 Auditorías por Tipo (Últimos 30 Días)</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 border-b border-gray-200">
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Tipo de Verificación</th>
                            <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($auditorias_por_tipo as $tipo => $stats)
                        <tr class="border-b border-gray-200 hover:bg-gray-50">
                            <td class="px-6 py-3 text-sm text-gray-700 font-medium">{{ $tipo }}</td>
                            <td class="px-6 py-3 text-sm text-gray-700">
                                <span class="inline-block bg-gray-100 text-gray-800 px-3 py-1 rounded-full">
                                    {{ $stats->total }}
                                </span>
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
