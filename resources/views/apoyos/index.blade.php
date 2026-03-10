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
    </script>
</x-app-layout>

<!-- Modal de éxito global (Alpine) -->
<div x-data="{ mostrarModal:false, mensaje:'' }" @open-success-modal.window="mensaje = $event.detail; mostrarModal = true">
    <div x-show="mostrarModal" style="display: none;" class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div x-show="mostrarModal" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-900 bg-opacity-75 transition-opacity backdrop-blur-sm" 
                 aria-hidden="true"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div x-show="mostrarModal" 
                 @click.away="mostrarModal = false"
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 text-center">
                    <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-green-100 mb-4">
                        <svg class="h-10 w-10 text-green-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-center">
                        <h3 class="text-2xl leading-6 font-bold text-gray-900 mb-2" id="modal-title">
                            ¡Éxito!
                        </h3>
                        <div class="mt-2">
                            <p class="text-md text-gray-600" x-text="mensaje">
                                <!-- mensaje dinámico -->
                            </p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button @click="mostrarModal = false" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-green-600 text-white font-medium hover:bg-green-700 sm:ml-3 sm:w-auto sm:text-sm">Aceptar</button>
                </div>
            </div>
        </div>
    </div>
</div>
