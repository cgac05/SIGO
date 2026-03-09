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
                        {{--<x-primary-button id="btn-reload">Recargar</x-primary-button>--}}
                        <div id="apoyo-modal" x-data="{tipo:'Económico'}">
                            <x-primary-button @click.prevent="window.dispatchEvent(new CustomEvent('open-modal',{detail:'apoyoModal'}))">Nuevo Apoyo</x-primary-button>

                                                {{--
                                                        Componente modal reutilizable (`resources/views/components/modal.blade.php`).
                                                        - `name="apoyoModal"` permite abrir/cerrar el modal mediante eventos
                                                            `open-modal` / `close-modal` (se usan en el script y en el botón Nuevo Apoyo).
                                                        - El modal contiene el formulario para crear un nuevo Apoyo. El envío
                                                            se intercepta por JavaScript y se envía por Fetch/AJAX.
                                                --}}
                                                <x-modal name="apoyoModal" :show="false">
                                                        <div class="p-4">
                                                                {{--
                                                                        Formulario de creación de Apoyo.
                                                                        - `id="apoyo-form"` es usado por el script para interceptar el submit
                                                                            y enviarlo por Fetch a la ruta `apoyos.store`.
                                                                        - El campo `activo` se envía siempre (hidden+checkbox) para evitar
                                                                            problemas con checkboxes desmarcados.
                                                                --}}
                                                                <form id="apoyo-form" method="POST" action="{{ route('apoyos.store') }}">
                                    @csrf
                                    <div class="space-y-4">
                                        <div>
                                            <x-input-label for="nombre_apoyo" :value="__('Nombre del Apoyo')" />
                                            <x-text-input id="nombre_apoyo" name="nombre_apoyo" class="mt-1 block w-full" required />
                                            <x-input-error :messages="$errors->get('nombre_apoyo')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="tipo_apoyo" :value="__('Tipo de Apoyo')" />
                                            <select id="tipo_apoyo" name="tipo_apoyo" x-model="tipo" class="mt-1 block w-full border-gray-300 rounded-md">
                                                <option>Económico</option>
                                                <option>Especie</option>
                                            </select>
                                            <x-input-error :messages="$errors->get('tipo_apoyo')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="monto_maximo" :value="__('Monto Máximo')" />
                                            <x-text-input id="monto_maximo" name="monto_maximo" type="number" step="0.01" class="mt-1 block w-full" />
                                            <x-input-error :messages="$errors->get('monto_maximo')" class="mt-2" />
                                        </div>

                                        <div x-show="tipo=='Económico'" x-cloak>
                                            <x-input-label for="monto_inicial_asignado" :value="__('Monto Inicial Asignado')" />
                                            <x-text-input id="monto_inicial_asignado" name="monto_inicial_asignado" type="number" step="0.01" class="mt-1 block w-full" />
                                            <x-input-error :messages="$errors->get('monto_inicial_asignado')" class="mt-2" />
                                        </div>

                                        <div x-show="tipo=='Especie'" x-cloak>
                                            <x-input-label for="stock_inicial" :value="__('Stock Inicial')" />
                                            <x-text-input id="stock_inicial" name="stock_inicial" type="number" class="mt-1 block w-full" />
                                            <x-input-error :messages="$errors->get('stock_inicial')" class="mt-2" />
                                        </div>

                                        {{--
                                            Envío fiable del checkbox `activo`:
                                            - hidden input con value=0 asegura que siempre llegue algo
                                            - checkbox con value=1 sobrescribirá el 0 si está marcado
                                        --}}
                                        <div class="flex items-center">
                                            <input type="hidden" name="activo" value="0">
                                            <input id="activo" name="activo" value="1" type="checkbox" checked class="mr-2">
                                            <label for="activo">Activo</label>
                                        </div>

                                        <div class="flex justify-end gap-2">
                                            <x-secondary-button id="apoyo-cancel" @click.prevent="window.dispatchEvent(new CustomEvent('close-modal',{detail:'apoyoModal'}))">Cancelar</x-secondary-button>
                                            <x-primary-button type="submit">Guardar Apoyo</x-primary-button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </x-modal>
                    </div>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="border-b">
                                <th class="p-2">ID</th>
                                <th class="p-2">Nombre del Apoyo</th>
                                <th class="p-2">Tipo</th>
                                <th class="p-2">Monto Máximo</th>
                                <th class="p-2">Estado</th>
                            </tr>
                        </thead>
                        <tbody id="apoyos-tbody">
                            @forelse($apoyos as $a)
                                <tr class="border-b hover:bg-gray-50">
                                    <td class="p-2">{{ $a->id_apoyo }}</td>
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

    {{--
        Script integrado para:
        - Recargar la lista de apoyos vía AJAX (ruta `apoyos.list`).
        - Interceptar el envío del formulario y hacerlo por Fetch a `apoyos.store`.

        Notas importantes:
        - Las respuestas JSON del servidor deben seguir la forma { success: boolean, message: string, apoyo?: object }
        - En caso de éxito, el script cierra el modal mediante el evento `close-modal` y recarga la tabla.
    --}}
    <script>
        (function(){
            const listUrl = '{{ route('apoyos.list') }}';
            const storeUrl = '{{ route('apoyos.store') }}';
            const csrf = '{{ csrf_token() }}';

            function formatCurrency(v){
                if (v === null || v === undefined) return '-';
                let n = parseFloat(v);
                if (isNaN(n)) return '-';
                return '$' + n.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            /**
             * Realiza una petición GET a `listUrl` y renderiza las filas de la tabla.
             * Se usa por el botón "Recargar" y después de un insert exitoso.
             */
            async function reloadApoyos(){
                try{
                    const res = await fetch(listUrl, {headers: {'Accept': 'application/json'}});
                    if (!res.ok) throw new Error('Error al cargar apoyos');
                    const data = await res.json();
                    const tbody = document.getElementById('apoyos-tbody');
                    if (!tbody) return;
                    if (!data || data.length === 0){
                        tbody.innerHTML = '<tr><td colspan="5" class="p-4">No hay apoyos registrados.</td></tr>';
                        return;
                    }
                    let html = '';
                    data.forEach(a => {
                        html += `<tr class="border-b hover:bg-gray-50">
                            <td class="p-2">${a.id_apoyo}</td>
                            <td class="p-2">${a.nombre_apoyo}</td>
                            <td class="p-2">${a.tipo_apoyo}</td>
                            <td class="p-2">${formatCurrency(a.monto_maximo)}</td>
                            <td class="p-2">${a.activo ? 'Activo' : 'Inactivo'}</td>
                        </tr>`;
                    });
                    tbody.innerHTML = html;
                }catch(err){
                    console.error(err);
                    alert('No se pudo recargar la lista de apoyos.');
                }
            }

            // Conectar el botón de recarga con la función reloadApoyos
            const btnReload = document.getElementById('btn-reload');
            if (btnReload) btnReload.addEventListener('click', e => { e.preventDefault(); reloadApoyos(); });

            // Interceptar el submit del formulario para enviarlo por AJAX
            const form = document.getElementById('apoyo-form');
            if (form){
                form.addEventListener('submit', async function(ev){
                    ev.preventDefault();
                    const formData = new FormData(form);
                    try{
                        const res = await fetch(storeUrl, {
                            method: 'POST',
                            headers: {'X-CSRF-TOKEN': csrf, 'Accept': 'application/json'},
                            body: formData
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success){
                            const msg = data && data.message ? data.message : 'Error al guardar el apoyo.';
                            alert(msg);
                            return;
                        }

                        // Success
                        alert(data.message || 'Apoyo registrado correctamente.');
                        form.reset();
                        // Close modal (via event)
                        window.dispatchEvent(new CustomEvent('close-modal', { detail: 'apoyoModal' }));
                        // Refresh table
                        await reloadApoyos();
                    }catch(err){
                        console.error(err);
                        alert('Error al enviar el formulario.');
                    }
                });
            }
        })();
    </script>
</x-app-layout>
