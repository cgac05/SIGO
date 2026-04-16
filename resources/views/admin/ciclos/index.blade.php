@extends('layouts.app')

@section('title', 'Gestión de Ciclos Presupuestarios')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-4xl font-bold text-gray-900">📅 Ciclos Presupuestarios</h1>
                <p class="text-gray-600 mt-2">Gestión de años fiscales y presupuestos globales</p>
            </div>
            <a href="{{ route('admin.ciclos.create') }}" 
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-6 rounded-lg shadow transition">
                ➕ Nuevo Ciclo
            </a>
        </div>

        <!-- Alert Messages -->
        @if($message = session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-600 rounded-lg p-4">
                <p class="text-green-800 font-medium">{{ $message }}</p>
            </div>
        @endif

        @if($message = session('error'))
            <div class="mb-6 bg-red-50 border-l-4 border-red-600 rounded-lg p-4">
                <p class="text-red-800 font-medium">{{ $message }}</p>
            </div>
        @endif

        <!-- Table de Ciclos -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-4 text-left text-sm font-semibold text-gray-700">Año Fiscal</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Presupuesto Total</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Estado</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Categorías</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Fecha Apertura</th>
                        <th class="px-6 py-4 text-center text-sm font-semibold text-gray-700">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($ciclos as $ciclo)
                        <tr class="hover:bg-gray-50 transition">
                            <td class="px-6 py-4">
                                <span class="text-lg font-bold text-gray-900">{{ $ciclo->ano_fiscal }}</span>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <span class="text-gray-900 font-semibold">${{ number_format($ciclo->presupuesto_total_aprobado, 0) }}</span>
                            </td>
                            <td class="px-6 py-4 text-center">
                                @if($ciclo->isAbierto())
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                        🟢 ABIERTO
                                    </span>
                                @else
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                        🔴 CERRADO
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-center">
                                <span class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-blue-100 text-blue-800 font-semibold">
                                    {{ $ciclo->categorias->count() }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-600">
                                {{ $ciclo->fecha_inicio?->format('d/m/Y') ?? 'N/A' }}
                            </td>
                            <td class="px-6 py-4 text-center">
                                <div class="flex justify-center gap-2">
                                    <a href="{{ route('admin.ciclos.show', $ciclo->id_ciclo) }}" 
                                       class="text-blue-600 hover:text-blue-800 font-medium text-sm">
                                        👁️ Ver
                                    </a>
                                    @if($ciclo->isAbierto())
                                        <a href="{{ route('admin.ciclos.edit', $ciclo->id_ciclo) }}" 
                                           class="text-amber-600 hover:text-amber-800 font-medium text-sm">
                                            ✏️ Editar
                                        </a>
                                        <form action="{{ route('admin.ciclos.cerrar', $ciclo->id_ciclo) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('¿Cerrar ciclo {{ $ciclo->ano_fiscal }}?')"
                                                    class="text-red-600 hover:text-red-800 font-medium text-sm">
                                                🔒 Cerrar
                                            </button>
                                        </form>
                                    @else
                                        <form action="{{ route('admin.ciclos.reabrir', $ciclo->id_ciclo) }}" method="POST" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" onclick="return confirm('¿Reabrir ciclo {{ $ciclo->ano_fiscal }}?')"
                                                    class="text-green-600 hover:text-green-800 font-medium text-sm">
                                                🔓 Reabrir
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                <p>📭 No hay ciclos presupuestarios. <a href="{{ route('admin.ciclos.create') }}" class="text-blue-600 hover:underline">Crear uno</a></p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
