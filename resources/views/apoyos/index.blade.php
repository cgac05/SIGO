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
<<<<<<< HEAD
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
                                                                                                {{--
                                                                                                        Modal de creación de Apoyo.
                                                                                                        - El `name="apoyoModal"` permite controlarlo desde JS global
                                                                                                            mediante `open-modal`/`close-modal`.
                                                                                                        - El formulario tiene `id="apoyo-form"` y es enviado por AJAX
                                                                                                            (ver script abajo). Priorizar la validación en el servidor.
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
                                                                {{-- Formulario multipart; enviado por Fetch como FormData. --}}
                                                                <form id="apoyo-form" method="POST" action="{{ route('apoyos.store') }}" enctype="multipart/form-data">
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

                                        <div class="flex gap-4">
                                            <div class="flex-1">
                                                <x-input-label for="monto_maximo" :value="__('Monto Máximo')" />
                                                <x-text-input id="monto_maximo" name="monto_maximo" type="number" step="0.01" class="mt-1 block w-full" />
                                                <x-input-error :messages="$errors->get('monto_maximo')" class="mt-2" />
                                            </div>

                                            <div class="flex-1" x-show="tipo=='Económico'" x-cloak>
                                                <x-input-label for="monto_inicial_asignado" :value="__('Monto Inicial Asignado')" />
                                                <x-text-input id="monto_inicial_asignado" name="monto_inicial_asignado" type="number" step="0.01" class="mt-1 block w-full" />
                                                <x-input-error :messages="$errors->get('monto_inicial_asignado')" class="mt-2" />
                                            </div>
                                        </div>

                                        <div x-show="tipo=='Especie'" x-cloak>
                                            <x-input-label for="stock_inicial" :value="__('Stock Inicial')" />
                                            <x-text-input id="stock_inicial" name="stock_inicial" type="number" class="mt-1 block w-full" />
                                            <x-input-error :messages="$errors->get('stock_inicial')" class="mt-2" />
                                        </div>

                                        <div class="flex gap-4">
                                            <div class="flex-1">
                                                <x-input-label for="fechaInicio" :value="__('Fecha de Inicio')" />
                                                <input id="fechaInicio" name="fechaInicio" type="date" class="mt-1 block w-full border-gray-300 rounded-md" required />
                                                <x-input-error :messages="$errors->get('fechaInicio')" class="mt-2" />
                                            </div>

                                            <div class="flex-1">
                                                <x-input-label for="fechafin" :value="__('Fecha de Fin')" />
                                                <input id="fechafin" name="fechafin" type="date" class="mt-1 block w-full border-gray-300 rounded-md" required />
                                                <x-input-error :messages="$errors->get('fechafin')" class="mt-2" />
                                            </div>
                                        </div>

                                        <div>
                                            <x-input-label for="foto_ruta" :value="__('Foto del Apoyo')" />
                                            <input id="foto_ruta" name="foto_ruta" type="file" accept="image/*" class="mt-1 block w-full" />
                                            <x-input-error :messages="$errors->get('foto_ruta')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label for="descripcion" :value="__('Descripción del Apoyo')" />
                                            <textarea id="descripcion" name="descripcion" rows="4" class="mt-1 block w-full border-gray-300 rounded-md" required>{{ old('descripcion') }}</textarea>
                                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                                        </div>

                                        <div>
                                            <x-input-label :value="__('Documentos Requeridos')" />
                                            <div class="mt-2 grid grid-cols-2 gap-2">
                                                @if(isset($tiposDocumentos) && $tiposDocumentos->count())
                                                    @foreach($tiposDocumentos as $td)
                                                        <label class="flex items-center gap-2">
                                                            <input type="checkbox" name="documentos_requeridos[]" value="{{ $td->id_tipo_doc }}" class="form-checkbox">
                                                            <span class="text-sm">{{ $td->nombre_documento }}</span>
                                                        </label>
                                                    @endforeach
                                                @else
                                                    <div class="text-sm text-gray-500">No hay tipos de documento configurados.</div>
                                                @endif
                                            </div>
                                            <x-input-error :messages="$errors->get('documentos_requeridos')" class="mt-2" />
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
=======
                        <a href="{{ route('apoyos.create') }}">
                            <x-primary-button>+ Nuevo Apoyo</x-primary-button>
                        </a>
>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
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
<<<<<<< HEAD
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
                    data.forEach((a, i) => {
                        const activoVal = parseInt(a.activo, 10);
                        html += `<tr class="border-b hover:bg-gray-50">
                            <td class="p-2">${i + 1}</td>
                            <td class="p-2">${a.nombre_apoyo}</td>
                            <td class="p-2">${a.tipo_apoyo}</td>
                            <td class="p-2">${formatCurrency(a.monto_maximo)}</td>
                            <td class="p-2">${activoVal === 1 ? 'Activo' : 'Inactivo'}</td>
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
            // Interceptamos el envío para usar Fetch; el servidor devuelve JSON.
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
                            // Mostrar error al usuario. En producción considerar mostrar errores
                            // más amigables / localizados según `data.message`.
                            alert(msg);
                            return;
                        }

                            // Success: abrir modal de éxito en lugar de alert
                            window.dispatchEvent(new CustomEvent('open-success-modal', { detail: data.message || 'Apoyo registrado correctamente.' }));
                            form.reset();
                            // Close create modal
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
=======
        document.addEventListener('DOMContentLoaded', () => {
            const msg = @json(session('created'));
            const toast = document.createElement('div');
            toast.className = 'fixed bottom-6 right-6 z-50 bg-green-600 text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium';
            toast.textContent = msg;
            document.body.appendChild(toast);
            setTimeout(() => toast.remove(), 4000);
        });
>>>>>>> 6da04ff4c21ec2e3298b12384bdb1b9c1fb7472c
    </script>
    @endif
</x-app-layout>
