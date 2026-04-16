@extends('layouts.app')

@section('title', 'Editar Ciclo Presupuestario')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4 max-w-2xl">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ route('admin.ciclos.show', $ciclo->id) }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-4 inline-block">
                ← Volver al Ciclo
            </a>
            <h1 class="text-4xl font-bold text-gray-900">✏️ Editar Ciclo Presupuestario {{ $ciclo->ano_fiscal }}</h1>
            <p class="text-gray-600 mt-2">Modifica los parámetros del ciclo fiscal</p>
        </div>

        <!-- Alert Messages -->
        @if($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-600 rounded-lg p-4">
                <p class="text-red-800 font-semibold mb-2">⚠️ Errores encontrados:</p>
                <ul class="list-disc list-inside space-y-1 text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Form Card -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="bg-gradient-to-r from-amber-600 to-amber-700 px-6 py-4">
                <h2 class="text-white text-xl font-semibold">📋 Información del Ciclo</h2>
            </div>

            <form action="{{ route('admin.ciclos.update', $ciclo->id) }}" method="POST" class="p-8">
                @csrf
                @method('PUT')

                <!-- Año Fiscal (Read-only) -->
                <div class="mb-6">
                    <label for="ano_fiscal" class="block text-sm font-semibold text-gray-700 mb-2">
                        📅 Año Fiscal
                    </label>
                    <input type="number" id="ano_fiscal" 
                           value="{{ $ciclo->ano_fiscal }}"
                           disabled
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg bg-gray-100 text-gray-600">
                    <p class="text-gray-500 text-xs mt-1">No se puede cambiar una vez creado</p>
                </div>

                <!-- Estado (Read-only) -->
                <div class="mb-6">
                    <label for="estado" class="block text-sm font-semibold text-gray-700 mb-2">
                        Estado
                    </label>
                    <div class="flex items-center gap-2">
                        @if($ciclo->isAbierto())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 text-green-800">
                                🟢 ABIERTO
                            </span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                                🔴 CERRADO
                            </span>
                        @endif
                    </div>
                </div>

                <!-- Presupuesto Total -->
                <div class="mb-6">
                    <label for="presupuesto_total" class="block text-sm font-semibold text-gray-700 mb-2">
                        💰 Presupuesto Total <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-2 text-gray-500 text-lg">$</span>
                        <input type="number" id="presupuesto_total" name="presupuesto_total" 
                               value="{{ old('presupuesto_total', $ciclo->presupuesto_total_aprobado) }}"
                               min="0.01" step="0.01"
                               required
                               class="w-full px-4 py-2 pl-8 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500 @error('presupuesto_total') border-red-500 @enderror">
                    </div>
                    @error('presupuesto_total')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Fecha Cierre -->
                <div class="mb-6">
                    <label for="fecha_cierre" class="block text-sm font-semibold text-gray-700 mb-2">
                        📅 Fecha de Cierre
                    </label>
                    <input type="date" id="fecha_cierre" name="fecha_cierre" 
                           value="{{ old('fecha_cierre', $ciclo->fecha_cierre?->format('Y-m-d')) }}"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500"
                           min="{{ now()->format('Y-m-d') }}">
                    @error('fecha_cierre')
                        <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Metas Info -->
                <div class="mb-8 bg-gray-50 px-4 py-4 rounded-lg border border-gray-200">
                    <p class="text-sm text-gray-700"><strong>Creado:</strong> {{ $ciclo->created_at->format('d/m/Y H:i') }}</p>
                    <p class="text-sm text-gray-700"><strong>Última modificación:</strong> {{ $ciclo->updated_at->format('d/m/Y H:i') }}</p>
                </div>

                <!-- Botones -->
                <div class="flex gap-4 pt-6 border-t">
                    <a href="{{ route('admin.ciclos.show', $ciclo->id) }}" 
                       class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition text-center">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="flex-1 bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        ✅ Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
