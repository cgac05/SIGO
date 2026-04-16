@extends('layouts.app')

@section('title', 'Detalle Ciclo Presupuestario')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="container mx-auto px-4">
        <!-- Header -->
        <div class="mb-8 flex justify-between items-start">
            <div>
                <a href="{{ route('admin.ciclos.index') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mb-4 inline-block">
                    ← Volver a Ciclos
                </a>
                <h1 class="text-4xl font-bold text-gray-900">📊 Ciclo Presupuestario {{ $ciclo->ano_fiscal }}</h1>
                <p class="text-gray-600 mt-2">Gestión de categorías y presupuestos asignados</p>
            </div>
            <div class="flex gap-2">
                @if($ciclo->isAbierto())
                    <a href="{{ route('admin.ciclos.edit', $ciclo->id_ciclo) }}" 
                       class="bg-amber-600 hover:bg-amber-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        ✏️ Editar
                    </a>
                    <form action="{{ route('admin.ciclos.cerrar', $ciclo->id_ciclo) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" onclick="return confirm('¿Cerrar ciclo {{ $ciclo->ano_fiscal }}? No se podrán hacer cambios después.')"
                                class="bg-red-600 hover:bg-red-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            🔒 Cerrar Ciclo
                        </button>
                    </form>
                @else
                    <form action="{{ route('admin.ciclos.reabrir', $ciclo->id_ciclo) }}" method="POST" class="inline">
                        @csrf
                        @method('PATCH')
                        <button type="submit" onclick="return confirm('¿Reabrir ciclo {{ $ciclo->ano_fiscal }}?')"
                                class="bg-green-600 hover:bg-green-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                            🔓 Reabrir
                        </button>
                    </form>
                @endif
            </div>
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

        <!-- KPI Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Presupuesto -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <p class="text-gray-500 text-sm font-medium">💰 Presupuesto Total</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">${{ number_format($ciclo->presupuesto_total_aprobado, 0) }}</p>
            </div>

            <!-- Total Categorías -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-purple-500">
                <p class="text-gray-500 text-sm font-medium">📁 Categorías</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $categorias->count() }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $categorias->where('activo', true)->count() }} activas</p>
            </div>

            <!-- Total Asignado -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                <p class="text-gray-500 text-sm font-medium">✅ Asignado</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">${{ number_format($totalPresupuestoAsignado, 0) }}</p>
            </div>

            <!-- Porcentaje Ejecución -->
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-orange-500">
                <p class="text-gray-500 text-sm font-medium">📊 Ejecución</p>
                <p class="text-2xl font-bold text-gray-900 mt-2">{{ $porcentajeEjecucion }}%</p>
                <div class="w-full bg-gray-200 rounded-full h-2 mt-2">
                    <div class="bg-orange-500 h-2 rounded-full" style="width: {{ $porcentajeEjecucion }}%"></div>
                </div>
            </div>
        </div>

        <!-- Estado del Ciclo -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 mb-4">📋 Información del Ciclo</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-sm text-gray-500">Estado</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">
                        @if($ciclo->isAbierto())
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-green-100 text-green-800">🟢 ABIERTO</span>
                        @else
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-red-100 text-red-800">🔴 CERRADO</span>
                        @endif
                    </p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Fecha Apertura</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">{{ $ciclo->fecha_inicio?->format('d/m/Y') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-sm text-gray-500">Cierre Programado</p>
                    <p class="text-lg font-semibold text-gray-900 mt-1">
                        {{ $ciclo->fecha_cierre?->format('d/m/Y') ?? '—' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabla de Categorías -->
        <div class="bg-white rounded-lg shadow overflow-hidden mb-8">
            <div class="px-6 py-4 bg-gray-50 border-b flex justify-between items-center">
                <h2 class="text-lg font-bold text-gray-900">📂 Categorías de Presupuesto</h2>
                @if($ciclo->isAbierto())
                    <button type="button" onclick="document.getElementById('agregarCategoriaModal').classList.toggle('hidden')"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-1 px-4 rounded text-sm transition">
                        ➕ Agregar
                    </button>
                @endif
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-100 border-b">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700">Categoría</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Presupuesto Anual</th>
                            <th class="px-6 py-3 text-right text-xs font-semibold text-gray-700">Disponible</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700">% Utilización</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700">Estado</th>
                            <th class="px-6 py-3 text-center text-xs font-semibold text-gray-700">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        @forelse($categorias as $cat)
                            @php
                                $utilizacion = ($cat->presupuesto_anual - $cat->disponible) / $cat->presupuesto_anual * 100;
                                $estadoColor = $utilizacion >= 85 ? 'red' : ($utilizacion >= 70 ? 'yellow' : 'green');
                                $icon = $utilizacion >= 85 ? '🔴' : ($utilizacion >= 70 ? '🟡' : '🟢');
                            @endphp
                            <tr class="hover:bg-gray-50 transition">
                                <td class="px-6 py-4">
                                    <div>
                                        <p class="font-semibold text-gray-900">{{ $cat->nombre }}</p>
                                        @if($cat->descripcion)
                                            <p class="text-xs text-gray-500">{{ $cat->descripcion }}</p>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-gray-900 font-semibold">${{ number_format($cat->presupuesto_anual, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <span class="text-green-600 font-semibold">${{ number_format($cat->disponible, 0) }}</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <span>{{ number_format($utilizacion, 1) }}%</span>
                                        <div class="w-24 bg-gray-200 rounded-full h-2">
                                            <div class="h-2 rounded-full" style="width: {{ $utilizacion }}%; background-color: {{ $estadoColor === 'red' ? '#ef4444' : ($estadoColor === 'yellow' ? '#f59e0b' : '#10b981') }}"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($cat->activo)
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                            ✅ ACTIVA
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                            ⊘ INACTIVA
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    @if($ciclo->isAbierto() && $cat->activo)
                                        <div class="flex justify-center gap-2">
                                            <button type="button" 
                                                    onclick="editarCategoria({{ $cat->id_categoria }}, '{{ addslashes($cat->nombre) }}', '{{ addslashes($cat->descripcion ?? '') }}', {{ $cat->presupuesto_anual }})"
                                                    class="text-amber-600 hover:text-amber-800 font-medium text-sm">
                                                ✏️
                                            </button>
                                            <form action="{{ route('admin.ciclos.deleteCategoria', $cat->id_categoria) }}" method="POST" class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" onclick="return confirm('¿Desactivar categoría?')"
                                                        class="text-red-600 hover:text-red-800 font-medium text-sm">
                                                    🗑️
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-xs">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                    📭 No hay categorías. @if($ciclo->isAbierto())<button type="button" onclick="document.getElementById('agregarCategoriaModal').classList.toggle('hidden')" class="text-blue-600 hover:underline">Agregar una</button>@endif
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Agregar/Editar Categoría -->
    <div id="agregarCategoriaModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg shadow-lg max-w-2xl w-full mx-4">
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                <h3 class="text-white text-xl font-semibold">➕ Agregar Nueva Categoría</h3>
            </div>

            <form id="formCategoria" method="POST" class="p-6">
                @csrf
                
                <input type="hidden" id="categoriaId" name="categoria_id">

                <div class="mb-4">
                    <label for="categoria_nombre" class="block text-sm font-semibold text-gray-700 mb-2">
                        Nombre <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="categoria_nombre" name="nombre" 
                           required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                           placeholder="Ej: Becas Educativas">
                </div>

                <div class="mb-4">
                    <label for="categoria_descripcion" class="block text-sm font-semibold text-gray-700 mb-2">
                        Descripción
                    </label>
                    <textarea id="categoria_descripcion" name="descripcion" rows="2"
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"
                              placeholder="Descripción de la categoría..."></textarea>
                </div>

                <div class="mb-6">
                    <label for="categoria_presupuesto" class="block text-sm font-semibold text-gray-700 mb-2">
                        Presupuesto Anual <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-4 top-2 text-gray-500">$</span>
                        <input type="number" id="categoria_presupuesto" name="presupuesto_anual" 
                               min="0.01" step="0.01" required
                               class="w-full px-4 py-2 pl-8 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                               placeholder="0.00">
                    </div>
                </div>

                <div class="flex gap-4 pt-4 border-t">
                    <button type="button" onclick="document.getElementById('agregarCategoriaModal').classList.add('hidden')"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold py-2 px-4 rounded-lg transition">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition">
                        ✅ Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editarCategoria(id, nombre, descripcion, presupuesto) {
    document.getElementById('categoriaId').value = id;
    document.getElementById('categoria_nombre').value = nombre;
    document.getElementById('categoria_descripcion').value = descripcion;
    document.getElementById('categoria_presupuesto').value = presupuesto;
    
    const form = document.getElementById('formCategoria');
    const actionUrl = "{{ url('admin/ciclos/categorias') }}/" + id;
    form.action = actionUrl;
    form.innerHTML = form.innerHTML.replace('@csrf', '@csrf\n@method("PUT")');
    
    document.getElementById('agregarCategoriaModal').classList.remove('hidden');
}

document.getElementById('formCategoria').addEventListener('submit', function(e) {
    const categoriaId = document.getElementById('categoriaId').value;
    if (categoriaId) {
        // Editar
        this.action = "{{ url('admin/ciclos/categorias') }}/" + categoriaId;
        // Asegurar que tenga PUT
        let putInput = this.querySelector('input[name="_method"]');
        if (!putInput) {
            putInput = document.createElement('input');
            putInput.type = 'hidden';
            putInput.name = '_method';
            putInput.value = 'PUT';
            this.appendChild(putInput);
        }
    } else {
        // Crear
        this.action = "{{ route('admin.ciclos.storeCategoria', $ciclo->id_ciclo) }}";
        let putInput = this.querySelector('input[name="_method"]');
        if (putInput) putInput.remove();
    }
});
</script>

@endsection
