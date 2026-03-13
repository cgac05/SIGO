@php
use Carbon\Carbon;
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <a href="{{ route('apoyos.index') }}"
                   class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-200 shadow-sm hover:bg-gray-50 transition">
                    <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h2 class="font-extrabold text-xl text-gray-900 leading-tight">Editar Apoyo</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Modifica los detalles del programa de apoyo</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span id="badge-tipo"
                      class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $apoyo->tipo_apoyo === 'Económico' ? 'bg-amber-100 text-amber-800' : 'bg-green-100 text-green-800' }} ring-1 {{ $apoyo->tipo_apoyo === 'Económico' ? 'ring-amber-200' : 'ring-green-200' }}">
                    {{ $apoyo->tipo_apoyo }}
                </span>
            </div>
        </div>
    </x-slot>

    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">

    <style>
        :root {
            --navy: #0f2044;
            --blue: #1a4a8a;
            --light: #eef3fb;
        }
        .panel {
            background: #fff;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            box-shadow: 0 2px 12px rgba(15,32,68,.06);
        }
        .panel-title {
            font-size: .7rem;
            font-weight: 800;
            letter-spacing: .08em;
            text-transform: uppercase;
            color: var(--navy);
            padding: 1rem 1.25rem .75rem;
            border-bottom: 1.5px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: .5rem;
        }
        .panel-body { padding: 1.25rem; }
        .field-label {
            display: block;
            font-size: .75rem;
            font-weight: 700;
            color: #374151;
            margin-bottom: .35rem;
        }
        .field-label .req { color: #ef4444; margin-left: 2px; }
        .field-input {
            width: 100%;
            border: 1.5px solid #d1d5db;
            border-radius: 10px;
            padding: .55rem .75rem;
            font-size: .875rem;
            color: #111827;
            background: #fff;
            transition: border-color .2s, box-shadow .2s;
            outline: none;
        }
        .field-input:focus {
            border-color: var(--blue);
            box-shadow: 0 0 0 3px rgba(26,74,138,.12);
        }
        .field-input.error { border-color: #ef4444; }
        select.field-input { cursor: pointer; }
        .prefix-wrap { position: relative; }
        .prefix-wrap .prefix {
            position: absolute; left: .75rem; top: 50%; transform: translateY(-50%);
            font-size: .85rem; font-weight: 600; color: #6b7280; pointer-events: none;
        }
        .prefix-wrap .field-input { padding-left: 1.6rem; }

        #editor-container .ql-container { border-radius: 0 0 10px 10px; min-height: 140px; font-size: .875rem; }
        #editor-container .ql-toolbar { border-radius: 10px 10px 0 0; border: 1.5px solid #d1d5db; }
        #editor-container .ql-container { border: 1.5px solid #d1d5db; border-top: none; }

        #img-preview-wrap {
            width: 100%; aspect-ratio: 16/9; border: 2px dashed #d1d5db;
            border-radius: 12px; overflow: hidden; background: var(--light);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: border-color .2s;
        }
        #img-preview-wrap:hover { border-color: var(--blue); }
        #img-preview-wrap img { width: 100%; height: 100%; object-fit: cover; }
        #img-preview-wrap .placeholder { text-align: center; color: #94a3b8; pointer-events: none; }

        .submit-bar {
            position: sticky; bottom: 0;
            background: rgba(255,255,255,.92);
            backdrop-filter: blur(8px);
            border-top: 1.5px solid #e2e8f0;
            padding: 1rem 1.5rem;
            display: flex; align-items: center; justify-content: flex-end; gap: .75rem;
            z-index: 10;
        }
        .btn-primary {
            background: linear-gradient(135deg, var(--navy), var(--blue));
            color: #fff; border: none; border-radius: 10px;
            padding: .65rem 1.5rem; font-size: .875rem; font-weight: 700;
            cursor: pointer; transition: opacity .2s, transform .15s;
        }
        .btn-primary:hover { opacity: .9; transform: translateY(-1px); }
        .btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }
        .btn-secondary {
            background: #f1f5f9; color: #374151; border: 1.5px solid #d1d5db;
            border-radius: 10px; padding: .65rem 1.25rem; font-size: .875rem;
            font-weight: 600; cursor: pointer; transition: background .2s;
        }
        .btn-secondary:hover { background: #e2e8f0; }
    </style>

    <form id="formularioEditarApoyo" method="POST" action="{{ route('apoyos.update', $apoyo->id_apoyo) }}" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="descripcion" id="descripcion-hidden">

        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-6 grid grid-cols-1 xl:grid-cols-3 gap-6 pb-24">

            {{-- COLUMNA IZQUIERDA --}}
            <div class="xl:col-span-2 flex flex-col gap-6">

                {{-- Panel: Identificación --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Identificación del programa
                    </div>
                    <div class="panel-body grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div class="sm:col-span-2">
                            <label class="field-label" for="nombre_apoyo">Nombre del apoyo <span class="req">*</span></label>
                            <input id="nombre_apoyo" name="nombre_apoyo" type="text"
                                   class="field-input" placeholder="Ej. Beca de emprendimiento juvenil"
                                   required maxlength="100" value="{{ old('nombre_apoyo', $apoyo->nombre_apoyo) }}">
                        </div>

                        <div>
                            <label class="field-label" for="tipo_apoyo">Tipo de apoyo <span class="req">*</span></label>
                            <select id="tipo_apoyo" name="tipo_apoyo" class="field-input" required>
                                <option value="Económico" {{ old('tipo_apoyo', $apoyo->tipo_apoyo) === 'Económico' ? 'selected' : '' }}>💰 Económico</option>
                                <option value="Especie" {{ old('tipo_apoyo', $apoyo->tipo_apoyo) === 'Especie' ? 'selected' : '' }}>📦 Especie (material)</option>
                            </select>
                        </div>

                        <div>
                            <label class="field-label" for="anio_fiscal">Año fiscal</label>
                            <input id="anio_fiscal" name="anio_fiscal" type="number" class="field-input" disabled
                                   value="{{ $apoyo->anio_fiscal ?? date('Y') }}" min="2020" max="2099">
                            <small class="text-gray-500">No se puede cambiar</small>
                        </div>

                        <div>
                            <label class="field-label" for="fechaInicio">Fecha de inicio <span class="req">*</span></label>
                            <input id="fechaInicio" name="fechaInicio" type="text" class="field-input flatpickr"
                                   placeholder="dd/mm/aaaa" required
                                   value="{{ old('fechaInicio', $apoyo->fecha_inicio ? Carbon::parse($apoyo->fecha_inicio)->format('d/m/Y') : '') }}">
                        </div>

                        <div>
                            <label class="field-label" for="fechafin">Fecha de cierre <span class="req">*</span></label>
                            <input id="fechafin" name="fechafin" type="text" class="field-input flatpickr"
                                   placeholder="dd/mm/aaaa" required
                                   value="{{ old('fechafin', $apoyo->fecha_fin ? Carbon::parse($apoyo->fecha_fin)->format('d/m/Y') : '') }}">
                        </div>

                        <div class="sm:col-span-2 flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <input type="hidden" name="activo" value="0">
                            <input id="activo" name="activo" value="1" type="checkbox"
                                   class="w-4 h-4 accent-blue-700 cursor-pointer"
                                   {{ old('activo', $apoyo->activo) ? 'checked' : '' }}>
                            <label for="activo" class="text-sm font-semibold text-gray-700 cursor-pointer select-none">
                                Apoyo activo (publicado)
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Panel: Finanzas/Inventario --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.5 0-3 .7-3 2s1.5 2 3 2 3 .7 3 2-1.5 2-3 2m0-8v1m0 8v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="panel-fin-title">Financiamiento</span>
                    </div>
                    <div class="panel-body grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="field-label" for="monto_maximo">
                                Monto máximo por beneficiario <span class="req">*</span>
                            </label>
                            <div class="prefix-wrap">
                                <span class="prefix">$</span>
                                <input id="monto_maximo" name="monto_maximo" type="number"
                                       class="field-input" step="0.01" min="0" placeholder="0.00"
                                       value="{{ old('monto_maximo', $apoyo->monto_maximo) }}">
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            {{-- COLUMNA DERECHA --}}
            <div class="xl:col-span-1 flex flex-col gap-6">

                {{-- Panel: Imagen --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                        </svg>
                        Imagen representativa
                    </div>
                    <div class="panel-body space-y-3">
                        <div id="img-preview-wrap" onclick="document.getElementById('foto_ruta').click()">
                            @if($apoyo->foto_ruta)
                                <img id="img-preview" src="{{ asset($apoyo->foto_ruta) }}" alt="{{ $apoyo->nombre_apoyo }}" style="display:block">
                            @else
                                <img id="img-preview" src="" alt="" style="display:none">
                            @endif
                            <div class="placeholder" id="img-placeholder" style="{{ $apoyo->foto_ruta ? 'display:none' : '' }}">
                                <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.338-2.32 5.75 5.75 0 011.348 11.095H6.75z"/>
                                </svg>
                                <p class="text-xs font-semibold">Haz clic para cargar imagen</p>
                                <p class="text-xs opacity-60 mt-0.5">JPG, PNG, WebP — máx. 5 MB</p>
                            </div>
                        </div>
                        <input id="foto_ruta" name="foto_ruta" type="file"
                               accept="image/jpeg,image/png,image/webp" class="hidden">
                        <div id="img-name" class="text-xs text-gray-400 text-center hidden"></div>
                        <button type="button" id="btn-remove-img"
                                class="hidden w-full text-xs text-red-500 font-semibold hover:text-red-700 transition">
                            Remover imagen
                        </button>
                    </div>
                </div>

            </div>
        </div>

        {{-- Submit bar --}}
        <div class="submit-bar">
            <a href="{{ route('apoyos.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary">Guardar cambios</button>
        </div>
    </form>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script>
        // Flatpickr
        flatpickr('.flatpickr', {
            dateFormat: 'd/m/Y',
            locale: 'es'
        });

        // Manejo de imagen
        const inputFile = document.getElementById('foto_ruta');
        const imgPreview = document.getElementById('img-preview');
        const imgPlacer = document.getElementById('img-placeholder');
        const imgName = document.getElementById('img-name');
        const btnRemove = document.getElementById('btn-remove-img');

        inputFile.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (!file) return;

            const reader = new FileReader();
            reader.onload = (event) => {
                imgPreview.src = event.target.result;
                imgPreview.style.display = 'block';
                imgPlacer.style.display = 'none';
                imgName.textContent = file.name;
                imgName.classList.remove('hidden');
                btnRemove.classList.remove('hidden');
            };
            reader.readAsDataURL(file);
        });

        btnRemove.addEventListener('click', (e) => {
            e.preventDefault();
            inputFile.value = '';
            imgPreview.src = '';
            imgPreview.style.display = 'none';
            imgPlacer.style.display = 'flex';
            imgName.textContent = '';
            imgName.classList.add('hidden');
            btnRemove.classList.add('hidden');
        });

        // Cambio de tipo de apoyo
        const selectTipoApoyo = document.getElementById('tipo_apoyo');
        selectTipoApoyo.addEventListener('change', (e) => {
            const badge = document.getElementById('badge-tipo');
            const panelTitle = document.getElementById('panel-fin-title');
            badge.textContent = e.target.value;
            badge.className = e.target.value === 'Económico'
                ? 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 ring-1 ring-amber-200'
                : 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-green-100 text-green-800 ring-1 ring-green-200';
            panelTitle.textContent = e.target.value === 'Económico' ? 'Financiamiento' : 'Inventario';
        });

        // Submit del formulario
        document.getElementById('formularioEditarApoyo').addEventListener('submit', async (e) => {
            e.preventDefault();

            const btn = e.target.querySelector('[type="submit"]');
            btn.disabled = true;
            btn.textContent = 'Guardando...';

            const formData = new FormData(this);

            try {
                const response = await fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    const toast = document.createElement('div');
                    toast.className = 'fixed bottom-6 right-6 z-50 bg-green-600 text-white px-5 py-3 rounded-lg shadow-lg text-sm font-medium';
                    toast.textContent = data.message;
                    document.body.appendChild(toast);
                    setTimeout(() => {
                        toast.remove();
                        window.location.href = '{{ route("apoyos.index") }}';
                    }, 2000);
                } else {
                    alert('Error: ' + (data.message || 'No se pudo guardar'));
                }
            } catch (error) {
                console.error('Error:', error);
                alert('Error al procesar la solicitud');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Guardar cambios';
            }
        });
    </script>
</x-app-layout>
