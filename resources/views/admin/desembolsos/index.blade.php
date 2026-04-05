@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Gestión de Desembolsos</h1>
            <p class="text-gray-600 mt-1">Registre y administre los pagos a beneficiarios</p>
        </div>
        <a href="{{ route('desembolsos.create') }}" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg">
            <i class="fas fa-plus"></i> Nuevo Desembolso
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Total de Desembolsos</p>
                    <p class="text-2xl font-bold text-gray-900">{{ $totales['total_desembolsos'] ?? 0 }}</p>
                </div>
                <div class="bg-blue-100 p-3 rounded-full">
                    <i class="fas fa-money-bill-wave text-blue-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Monto Total Desembolsado</p>
                    <p class="text-2xl font-bold text-green-600">
                        ${{ number_format($totales['monto_total'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-green-100 p-3 rounded-full">
                    <i class="fas fa-chart-line text-green-600 text-2xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow p-6">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-gray-500 text-sm">Promedio por Desembolso</p>
                    <p class="text-2xl font-bold text-purple-600">
                        ${{ number_format($totales['promedio_desembolso'] ?? 0, 2) }}
                    </p>
                </div>
                <div class="bg-purple-100 p-3 rounded-full">
                    <i class="fas fa-calculator text-purple-600 text-2xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow p-4 mb-6">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Folio de Solicitud</label>
                <input type="text" name="folio" value="{{ request('folio') }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Buscar por folio...">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Desde</label>
                <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Hasta</label>
                <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" 
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="flex items-end gap-2">
                <button type="submit" class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg">
                    <i class="fas fa-search"></i> Buscar
                </button>
                <a href="{{ route('desembolsos.index') }}" class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg text-center">
                    <i class="fas fa-redo"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <!-- Desembolsos Table -->
    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="w-full">
            <thead class="bg-gray-100 border-b-2 border-gray-300">
                <tr>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Folio</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Fecha de Pago</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Monto</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Usuario</th>
                    <th class="px-6 py-3 text-left text-sm font-semibold text-gray-700">Estado</th>
                    <th class="px-6 py-3 text-center text-sm font-semibold text-gray-700">Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($desembolsos as $desembolso)
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4 text-sm text-gray-900 font-medium">
                            {{ $desembolso->fk_folio }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $desembolso->fecha_entrega->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-sm font-semibold text-green-600">
                            ${{ number_format($desembolso->monto_entregado, 2) }}
                        </td>
                        <td class="px-6 py-4 text-sm text-gray-700">
                            {{ $desembolso->usuario->display_name ?? 'Sin asignar' }}
                        </td>
                        <td class="px-6 py-4 text-sm">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                                @if($desembolso->estado_pago === 'COMPLETADO')
                                    bg-green-100 text-green-800
                                @elseif($desembolso->estado_pago === 'PENDIENTE')
                                    bg-yellow-100 text-yellow-800
                                @else
                                    bg-gray-100 text-gray-800
                                @endif">
                                {{ $desembolso->estado_pago ?? 'SIN ESTADO' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-center">
                            <a href="{{ route('desembolsos.show', $desembolso->id_historico) }}" 
                                class="text-blue-600 hover:text-blue-800 font-medium">
                                <i class="fas fa-eye"></i> Ver
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-3"></i>
                            <p>No se encontraron desembolsos</p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <div class="mt-6">
        {{ $desembolsos->links() }}
    </div>

    <!-- Quick Actions -->
    <div class="flex gap-4 mt-6">
        <a href="{{ route('desembolsos.reporte-periodo') }}" 
            class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-center">
            <i class="fas fa-calendar"></i> Reporte por Período
        </a>
        <a href="{{ route('desembolsos.reporte-apoyo') }}" 
            class="flex-1 bg-cyan-600 hover:bg-cyan-700 text-white px-4 py-2 rounded-lg text-center">
            <i class="fas fa-chart-pie"></i> Reporte por Apoyo
        </a>
    </div>
</div>
@endsection
