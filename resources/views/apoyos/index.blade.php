@php
    /**
     * Helper local para formatear montos como moneda.
     * No es una función global del proyecto; se usa sólo en esta vista para mostrar
     * el valor de `monto_maximo` en la tabla.
     */
    $currency = function ($v) {
        if ($v === null) return '-';
        return '$' . number_format((float)$v, 2);
    };
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administrar Recursos de Apoyo</h2>
    </x-slot>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                {{--
                    Mensaje flash opcional: si se redirige desde un POST no-AJAX, se mostrará
                    aquí el mensaje de éxito. Para el flujo AJAX el front-end muestra
                    alertas/toasts en el cliente.
                --}}
                @if(session('success'))
                    <div class="mb-4 font-medium text-sm text-green-600">{{ session('success') }}</div>
                @endif

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium">Lista de Apoyos</h3>
                    <div class="flex items-center gap-2">
                        <a href="{{ route('apoyos.create') }}">
                            <x-primary-button>+ Nuevo Apoyo</x-primary-button>
                        </a>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2">No.</th>
                                <th class="p-2">Nombre del Apoyo</th>
                                <th class="p-2">Tipo</th>
                                <th class="p-2">Monto Máximo</th>
                                <th class="p-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="apoyos-tbody">
                            @forelse($apoyos as $a)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2">{{ $loop->iteration }}</td>
                                    <td class="p-2">{{ $a->nombre_apoyo }}</td>
                                    <td class="p-2">{{ $a->tipo_apoyo }}</td>
                                    <td class="p-2">{{ $currency($a->monto_maximo) }}</td>
                                    <td class="p-2">{{ $a->activo ? 'Activo' : 'Inactivo' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="p-4">No hay apoyos registrados.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @if(session('created'))
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const msg = @json(session('created'));
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-6 right-6 z-50 bg-green-600 text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium';
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        });
    </script>
    @endif
</x-app-layout>
