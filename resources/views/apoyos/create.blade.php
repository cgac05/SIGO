<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Nuevo Apoyo - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link href="https://cdn.quilljs.com/1.3.7/quill.snow.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css" rel="stylesheet">
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <a href="{{ route('apoyos.index') }}"
                           class="inline-flex items-center justify-center w-8 h-8 rounded-lg bg-white border border-gray-200 shadow-sm hover:bg-gray-50 transition">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </a>
                        <div>
                            <h2 class="font-extrabold text-xl text-gray-900 leading-tight">Nuevo Apoyo</h2>
                            <p class="text-xs text-gray-500 mt-0.5">Completa todos los campos para registrar un programa de apoyo</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="badge-tipo"
                              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 ring-1 ring-amber-200">
                            Económico
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <main>
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

        /* Quill editor */
        #editor-container .ql-container { border-radius: 0 0 10px 10px; min-height: 140px; font-size: .875rem; }
        #editor-container .ql-toolbar { border-radius: 10px 10px 0 0; border: 1.5px solid #d1d5db; }
        #editor-container .ql-container { border: 1.5px solid #d1d5db; border-top: none; }

        /* Image preview */
        #img-preview-wrap {
            width: 100%; aspect-ratio: 16/9; border: 2px dashed #d1d5db;
            border-radius: 12px; overflow: hidden; background: var(--light);
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: border-color .2s;
        }
        #img-preview-wrap:hover { border-color: var(--blue); }
        #img-preview-wrap img { width: 100%; height: 100%; object-fit: cover; }
        #img-preview-wrap .placeholder { text-align: center; color: #94a3b8; pointer-events: none; }

        /* Checkboxes docs */
        .doc-check {
            display: flex; align-items: center; gap: .5rem;
            padding: .4rem .6rem; border-radius: 8px;
            border: 1.5px solid #e5e7eb; cursor: pointer;
            transition: border-color .2s, background .2s;
            font-size: .8rem; color: #374151;
        }
        .doc-check:has(input:checked) {
            border-color: var(--blue); background: var(--light);
        }

        /* Inventory alert banner */
        #inv-alert {
            display: none;
            border-radius: 12px;
            padding: 1rem 1.25rem;
            background: #fef3c7;
            border: 1.5px solid #fbbf24;
            margin-top: .75rem;
        }
        #inv-alert.error-state {
            background: #fef2f2; border-color: #fca5a5;
        }

        /* Director auth modal */
        #dir-modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 80;
            background: rgba(10,20,50,.6); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 1rem;
        }
        #dir-modal-overlay.open { display: flex; }
        #dir-modal-box {
            background: #fff; border-radius: 20px;
            width: 100%; max-width: 400px;
            box-shadow: 0 24px 64px rgba(10,20,50,.3);
            padding: 2rem;
        }

        /* Modal nuevo tipo de documento */
        #doc-modal-overlay {
            display: none;
            position: fixed; inset: 0; z-index: 85;
            background: rgba(10,20,50,.6); backdrop-filter: blur(4px);
            align-items: center; justify-content: center; padding: 1rem;
        }
        #doc-modal-overlay.open { display: flex; }
        #doc-modal-box {
            background: #fff; border-radius: 20px;
            width: 100%; max-width: 460px;
            box-shadow: 0 24px 64px rgba(10,20,50,.3);
            padding: 1.5rem;
        }

        /* Submit bar */
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

        /* Progress steps */
        .step-dot {
            width: 8px; height: 8px; border-radius: 50%;
            background: #d1d5db; transition: background .3s;
        }
        .step-dot.active { background: var(--blue); }

        /* Toast */
        #toast {
            position: fixed; bottom: 5rem; right: 1.5rem; z-index: 100;
            background: #1e293b; color: #fff; border-radius: 12px;
            padding: .75rem 1.25rem; font-size: .85rem; font-weight: 600;
            box-shadow: 0 8px 24px rgba(0,0,0,.2);
            transform: translateY(20px); opacity: 0;
            transition: transform .3s, opacity .3s;
            pointer-events: none; max-width: 320px;
        }
        #toast.show { transform: translateY(0); opacity: 1; }
        #toast.success { background: #166534; }
        #toast.error   { background: #991b1b; }
        #toast.warn    { background: #92400e; }
    </style>

    <form id="apoyo-form" method="POST" action="{{ route('apoyos.store') }}" enctype="multipart/form-data" novalidate>
        @csrf
        <input type="hidden" name="descripcion" id="descripcion-hidden">
        <input type="hidden" name="stock_aprobado_por" id="stock-aprobado-por">

        {{-- ═══════════════════════════════════════════════════════
             GRID PRINCIPAL: izquierda (1 col) | derecha (1 col)
        ═══════════════════════════════════════════════════════ --}}
        <div class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-6 pb-24" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem;">

            {{-- ─── COLUMNA IZQUIERDA ─── --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">

                {{-- Panel: Identificación --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Identificación del programa
                    </div>
                    <div class="panel-body space-y-6">
                        {{-- Sección 1: Información básica --}}
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="field-label" for="nombre_apoyo">Nombre del apoyo <span class="req">*</span></label>
                                <input id="nombre_apoyo" name="nombre_apoyo" type="text"
                                       class="field-input" placeholder="Ej. Beca de emprendimiento juvenil"
                                       required maxlength="100" value="{{ old('nombre_apoyo') }}">
                                <div class="text-xs text-right text-gray-400 mt-1">
                                    <span id="nombre-count">0</span>/100
                                </div>
                            </div>

                            <div>
                                <label class="field-label" for="tipo_apoyo">Tipo de apoyo <span class="req">*</span></label>
                                <select id="tipo_apoyo" name="tipo_apoyo" class="field-input" required>
                                    <option value="Económico" {{ old('tipo_apoyo') === 'Económico' ? 'selected' : '' }}>💰 Económico</option>
                                    <option value="Especie"   {{ old('tipo_apoyo') === 'Especie'   ? 'selected' : '' }}>📦 Especie (material)</option>
                                </select>
                            </div>

                            <div>
                                <label class="field-label" for="anio_fiscal">Año fiscal</label>
                                <input id="anio_fiscal" name="anio_fiscal" type="number"
                                       class="field-input" value="{{ old('anio_fiscal', date('Y')) }}"
                                       min="2020" max="2099">
                            </div>
                        </div>

                        {{-- Separador visual --}}
                        <div class="border-t border-dashed border-gray-200"></div>

                        {{-- Sección 2: Período de vigencia --}}
                        <div>
                            <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Período de vigencia</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label" for="fechaInicio">Fecha de inicio <span class="req">*</span></label>
                                    <input id="fechaInicio" name="fechaInicio" type="text"
                                           class="field-input flatpickr" placeholder="dd/mm/aaaa"
                                           required value="{{ old('fechaInicio') }}">
                                </div>

                                <div>
                                    <label class="field-label" for="fechafin">Fecha de cierre <span class="req">*</span></label>
                                    <input id="fechafin" name="fechafin" type="text"
                                           class="field-input flatpickr" placeholder="dd/mm/aaaa"
                                           required value="{{ old('fechafin') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Separador visual --}}
                        <div class="border-t border-dashed border-gray-200"></div>

                        {{-- Sección 3: Estado --}}
                        <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                            <input type="hidden" name="activo" value="0">
                            <input id="activo" name="activo" value="1" type="checkbox"
                                   class="w-4 h-4 accent-blue-700 cursor-pointer"
                                   {{ old('activo', '1') ? 'checked' : '' }}>
                            <label for="activo" class="text-sm font-semibold text-gray-700 cursor-pointer select-none">
                                Publicar apoyo inmediatamente (activo)
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Panel: Finanzas/Inventario — condicional --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.5 0-3 .7-3 2s1.5 2 3 2 3 .7 3 2-1.5 2-3 2m0-8v1m0 8v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span id="panel-fin-title">Financiamiento</span>
                    </div>
                    <div class="panel-body space-y-6">

                        {{-- Sección 1: Alcance (común a todos los tipos) --}}
                        <div>
                            <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Alcance y capacidad</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="field-label" for="monto_maximo" id="lbl-monto-maximo">
                                        Monto máximo por beneficiario <span class="req">*</span>
                                    </label>
                                    <div class="prefix-wrap">
                                        <span class="prefix">$</span>
                                        <input id="monto_maximo" name="monto_maximo" type="number"
                                               class="field-input" step="0.01" min="0" placeholder="0.00"
                                               value="{{ old('monto_maximo') }}">
                                    </div>
                                </div>

                                <div>
                                    <label class="field-label" for="cupo_limite">
                                        Cupo máximo de beneficiarios <span class="req" id="req-cupo">*</span>
                                    </label>
                                    <input id="cupo_limite" name="cupo_limite" type="number"
                                           class="field-input" min="1" step="1" placeholder="Ej. 100"
                                           value="{{ old('cupo_limite') }}">
                                </div>
                            </div>
                        </div>

                        {{-- Separador visual --}}
                        <div class="border-t border-dashed border-gray-200"></div>

                        {{-- Sección 2: Presupuesto (condicional por tipo) --}}
                        <div>
                            <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3" id="lbl-presupuesto-seccion">Presupuesto</p>
                            
                            {{-- Solo Económico --}}
                            <div id="section-economico">
                                <div>
                                    <label class="field-label" for="monto_inicial_asignado">
                                        Presupuesto total asignado <span class="req">*</span>
                                    </label>
                                    <div class="prefix-wrap">
                                        <span class="prefix">$</span>
                                        <input id="monto_inicial_asignado" name="monto_inicial_asignado" type="number"
                                               class="field-input" step="0.01" min="0" placeholder="0.00"
                                               value="{{ old('monto_inicial_asignado') }}">
                                    </div>
                                    <p id="lbl-cobertura" class="text-xs text-gray-400 mt-1">
                                        Cobertura estimada: —
                                    </p>
                                </div>
                            </div>

                            {{-- Solo Especie --}}
                            <div id="section-especie" class="hidden space-y-4">
                                <div>
                                    <label class="field-label" for="stock_inicial">
                                        Stock inicial (unidades) <span class="req">*</span>
                                    </label>
                                    <input id="stock_inicial" name="stock_inicial" type="number"
                                           class="field-input" min="0" step="1" placeholder="Ej. 200"
                                           value="{{ old('stock_inicial') }}">
                                    <p class="text-xs text-gray-400 mt-1" id="lbl-deficit-info"></p>
                                </div>

                            {{-- Alerta de inventario insuficiente --}}
                            <div id="inv-alert">
                                <div class="flex items-start gap-3">
                                    <svg class="w-5 h-5 text-amber-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                                    </svg>
                                    <div class="flex-1">
                                        <p class="text-sm font-bold text-amber-800" id="inv-alert-title">Inventario insuficiente</p>
                                        <p class="text-xs text-amber-700 mt-0.5" id="inv-alert-body"></p>
                                        <button type="button" id="btn-solicitar-aprobacion"
                                                class="mt-2 inline-flex items-center gap-1.5 text-xs font-bold text-amber-800 underline underline-offset-2 hover:text-amber-900">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                                            </svg>
                                            Solicitar aprobación de Directivo
                                        </button>
                                    </div>
                                </div>
                                <div id="inv-approved-badge" class="mt-3 items-center gap-2 text-xs font-bold text-green-800" style="display:none">
                                    <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <span id="inv-approved-text"></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Panel: Descripción (Quill) --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                        </svg>
                        Descripción del apoyo
                    </div>
                    <div class="panel-body">
                        <div id="editor-container">
                            <div id="quill-editor">{!! old('descripcion') !!}</div>
                        </div>
                        <p class="text-xs text-gray-400 mt-2">
                            Puedes usar negritas, listas y otros formatos para enriquecer la descripción visible para el beneficiario.
                        </p>
                    </div>
                </div>

                {{-- Panel: Hitos importantes --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/>
                        </svg>
                        Fechas importantes (hitos)
                    </div>
                    <div class="panel-body space-y-4">
                        <p class="text-xs text-gray-500">Configura los hitos base del apoyo y agrega hitos adicionales si lo necesitas.</p>

                        <div id="hitos-base-grid" class="space-y-3">
                            @foreach(($milestonesBase ?? []) as $i => $hito)
                                @php($isMandatoryBase = in_array($hito['slug'], ['inicio_publicacion', 'proceso_cerrado'], true))
                                @php($tienePeriodo = !empty($hito['tiene_periodo']) && $hito['tiene_periodo'] === true)
                                <div class="rounded-xl border border-slate-200 p-3 bg-slate-50 grid grid-cols-1 sm:grid-cols-2 gap-3" data-mandatory-base="{{ $isMandatoryBase ? '1' : '0' }}">
                                    <input type="hidden" name="hitos[{{ $i }}][slug]" value="{{ $hito['slug'] }}">
                                    <input type="hidden" name="hitos[{{ $i }}][es_base]" value="1">
                                    @if($isMandatoryBase)
                                        <input type="hidden" name="hitos[{{ $i }}][incluir]" value="1" class="hito-incluir-hidden">
                                    @else
                                        <input type="hidden" name="hitos[{{ $i }}][incluir]" value="1" class="hito-incluir-hidden">
                                    @endif
                                    <div class="sm:col-span-2" data-hito-content="1">
                                        <div class="flex items-center justify-between gap-3 mb-2">
                                            <label class="field-label !mb-0">Hito</label>
                                            <div class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                                                @if($isMandatoryBase)
                                                    <input type="checkbox" class="w-4 h-4 accent-blue-700" checked disabled>
                                                    <span>Obligatorio en plataforma</span>
                                                @else
                                                    <label class="inline-flex items-center gap-2 cursor-pointer">
                                                        <input type="checkbox" data-hito-incluir-cb value="1" class="hito-toggle-incluir w-4 h-4 accent-blue-700" {{ old('hitos.' . $i . '.incluir', '1') == '1' ? 'checked' : '' }}>
                                                        <span>Incluir hito</span>
                                                    </label>
                                                @endif
                                            </div>
                                        </div>
                                        <input type="text" name="hitos[{{ $i }}][titulo]" class="field-input" value="{{ old('hitos.' . $i . '.titulo', $hito['titulo']) }}" required>
                                    </div>
                                    
                                    {{-- Hito puntual (solo inicio) --}}
                                    @if(!$tienePeriodo)
                                        <div class="sm:col-span-1" data-hito-content="1">
                                            <label class="field-label">Fecha de inicio</label>
                                            <input type="date" name="hitos[{{ $i }}][fecha_inicio]" class="field-input" value="{{ old('hitos.' . $i . '.fecha_inicio') }}">
                                        </div>
                                    {{-- Hito con período (inicio y fin PARALELOS) --}}
                                    @else
                                        <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3" data-hito-content="1">
                                            <div>
                                                <label class="field-label">Fecha de inicio</label>
                                                <input type="date" name="hitos[{{ $i }}][fecha_inicio]" class="field-input hito-fecha-inicio" value="{{ old('hitos.' . $i . '.fecha_inicio') }}" required>
                                            </div>
                                            <div>
                                                <label class="field-label">Fecha de fin</label>
                                                <input type="date" name="hitos[{{ $i }}][fecha_fin]" class="field-input hito-fecha-fin" value="{{ old('hitos.' . $i . '.fecha_fin') }}" required>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="pt-2 border-t border-dashed border-slate-200">
                            <button type="button" id="btn-add-hito" class="btn-secondary !py-2 !text-xs">+ Agregar hito adicional</button>
                        </div>
                        <div id="hitos-custom-grid" class="space-y-3"></div>
                    </div>
                </div>

            </div>{{-- /columna izquierda --}}

            {{-- ─── COLUMNA DERECHA ─── --}}
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">

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
                            <img id="img-preview" src="" alt="" style="display:none">
                            <div class="placeholder" id="img-placeholder">
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
                            ✕ Quitar imagen
                        </button>
                    </div>
                </div>

                {{-- Panel: Resumen en vivo --}}
                <div class="panel" id="panel-resumen">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"/>
                        </svg>
                        Resumen
                    </div>
                    <div class="panel-body space-y-3 text-sm">
                        <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Tipo</span>
                            <span id="res-tipo" class="font-bold text-gray-800">—</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider" id="res-monto-label">Monto máx.</span>
                            <span id="res-monto" class="font-bold text-gray-800">—</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Cupo</span>
                            <span id="res-cupo" class="font-bold text-gray-800">— beneficiarios</span>
                        </div>
                        <div class="flex justify-between items-center border-b border-gray-100 pb-2">
                            <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Presupuesto/Stock</span>
                            <span id="res-presupuesto" class="font-bold text-gray-800">—</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-gray-500 text-xs font-semibold uppercase tracking-wider">Vigencia</span>
                            <span id="res-vigencia" class="font-bold text-gray-800 text-right text-xs">—</span>
                        </div>
                    </div>
                </div>

                {{-- Panel: Documentos requeridos (EN COLUMNA DERECHA) --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                        </svg>
                        Documentos requeridos
                    </div>
                    <div class="panel-body">
                        <div class="mb-3 flex justify-end">
                            <button type="button" id="btn-open-doc-modal" class="btn-secondary text-xs !py-2">
                                + Agregar
                            </button>
                        </div>
                        @if(isset($tiposDocumentos) && $tiposDocumentos->count())
                            <div id="docs-grid" class="grid grid-cols-1 gap-2">
                                @foreach($tiposDocumentos as $td)
                                    <label class="doc-check">
                                        <input type="checkbox" name="documentos_requeridos[]"
                                               value="{{ $td->id_tipo_doc }}"
                                               class="w-4 h-4 accent-blue-700">
                                        <span class="text-xs">{{ $td->nombre_documento }}</span>
                                        @if(isset($td->tipo_archivo_permitido))
                                            <span class="text-[9px] font-bold px-1.5 py-0.5 rounded bg-slate-100 text-slate-600 uppercase">{{ $td->tipo_archivo_permitido }}</span>
                                        @endif
                                    </label>
                                @endforeach
                            </div>
                        @else
                            <p id="docs-empty" class="text-xs text-gray-400 italic">
                                Sin documentos configurados.
                            </p>
                            <div id="docs-grid" class="grid grid-cols-1 gap-2" style="display:none"></div>
                        @endif

                        <div class="mt-3 border-t border-dashed border-gray-200 pt-3">
                            <button type="button" id="btn-toggle-doc-admin" class="text-xs font-bold text-slate-700 hover:text-slate-900 underline underline-offset-2">
                                Administrar catálogo
                            </button>

                            <div id="doc-admin-wrap" class="mt-3" style="display:none">
                                <div class="overflow-x-auto border border-gray-200 rounded-xl">
                                    <table class="min-w-full text-xs">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th class="text-left p-2 font-bold text-gray-600">Documento</th>
                                                <th class="text-left p-2 font-bold text-gray-600">Tipo permitido</th>
                                                <th class="text-left p-2 font-bold text-gray-600">Validar tipo</th>
                                                <th class="text-left p-2 font-bold text-gray-600">Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="doc-admin-body">
                                            @if(isset($tiposDocumentos) && $tiposDocumentos->count())
                                                @foreach($tiposDocumentos as $td)
                                                    <tr data-doc-id="{{ $td->id_tipo_doc }}" class="border-t border-gray-100">
                                                        <td class="p-2 font-semibold text-gray-700">{{ $td->nombre_documento }}</td>
                                                        <td class="p-2">
                                                            <select class="field-input !py-1.5 !text-xs doc-admin-tipo">
                                                                @php($tipoActual = $td->tipo_archivo_permitido ?? 'pdf')
                                                                <option value="pdf"   {{ $tipoActual === 'pdf' ? 'selected' : '' }}>PDF</option>
                                                                <option value="image" {{ $tipoActual === 'image' ? 'selected' : '' }}>Imagen</option>
                                                                <option value="word"  {{ $tipoActual === 'word' ? 'selected' : '' }}>Word</option>
                                                                <option value="excel" {{ $tipoActual === 'excel' ? 'selected' : '' }}>Excel/CSV</option>
                                                                <option value="zip"   {{ $tipoActual === 'zip' ? 'selected' : '' }}>ZIP/RAR/7Z</option>
                                                                <option value="any"   {{ $tipoActual === 'any' ? 'selected' : '' }}>Cualquiera</option>
                                                            </select>
                                                        </td>
                                                        <td class="p-2">
                                                            <label class="inline-flex items-center gap-2">
                                                                <input type="checkbox" class="w-4 h-4 accent-blue-700 doc-admin-validar" {{ (isset($td->validar_tipo_archivo) ? (int) $td->validar_tipo_archivo : 1) === 1 ? 'checked' : '' }}>
                                                                <span class="text-gray-600">Activo</span>
                                                            </label>
                                                        </td>
                                                        <td class="p-2">
                                                            <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs doc-admin-save">Guardar</button>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Panel: Validación --}}
                <div class="panel">
                    <div class="panel-title">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                        </svg>
                        Checklist
                    </div>
                    <div class="panel-body space-y-2 text-xs" id="check-list">
                        <div class="check-item flex items-center gap-2" id="chk-nombre">
                            <span class="chk-icon w-4 h-4 rounded-full bg-gray-200 flex-shrink-0"></span>
                            <span>Nombre del apoyo</span>
                        </div>
                        <div class="check-item flex items-center gap-2" id="chk-tipo">
                            <span class="chk-icon w-4 h-4 rounded-full bg-green-400 flex-shrink-0"></span>
                            <span>Tipo de apoyo</span>
                        </div>
                        <div class="check-item flex items-center gap-2" id="chk-fechas">
                            <span class="chk-icon w-4 h-4 rounded-full bg-gray-200 flex-shrink-0"></span>
                            <span>Fechas de vigencia</span>
                        </div>
                        <div class="check-item flex items-center gap-2" id="chk-monto">
                            <span class="chk-icon w-4 h-4 rounded-full bg-gray-200 flex-shrink-0"></span>
                            <span id="chk-monto-lbl">Monto máximo</span>
                        </div>
                        <div class="check-item flex items-center gap-2" id="chk-cupo">
                            <span class="chk-icon w-4 h-4 rounded-full bg-gray-200 flex-shrink-0"></span>
                            <span>Cupo definido</span>
                        </div>
                        <div class="check-item flex items-center gap-2" id="chk-presupuesto">
                            <span class="chk-icon w-4 h-4 rounded-full bg-gray-200 flex-shrink-0"></span>
                            <span id="chk-presupuesto-lbl">Presupuesto asignado</span>
                        </div>
                        <div class="check-item flex items-center gap-2" id="chk-inventario">
                            <span class="chk-icon w-4 h-4 rounded-full bg-gray-200 flex-shrink-0"></span>
                            <span>Inventario consistente</span>
                        </div>
                        <div class="check-item flex items-center gap-2" id="chk-aprobacion" style="display:none">
                            <span class="chk-icon w-4 h-4 rounded-full bg-gray-200 flex-shrink-0"></span>
                            <span>Aprobación directivo</span>
                        </div>
                    </div>
                </div>

            </div>{{-- /columna derecha --}}

        </div>{{-- /grid --}}

        {{-- ═══ Barra de acciones fija ═══ --}}
        <div class="submit-bar">
            <div id="step-dots" class="flex items-center gap-1.5 mr-auto">
                <div class="step-dot active" id="dot-1"></div>
                <div class="step-dot" id="dot-2"></div>
                <div class="step-dot" id="dot-3"></div>
            </div>
            <a href="{{ route('apoyos.index') }}" class="btn-secondary">Cancelar</a>
            <button type="submit" class="btn-primary" id="btn-guardar" disabled>
                <span id="btn-label">Guardar Apoyo</span>
                <span id="btn-spinner" class="ml-2 inline-block w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin" style="display:none"></span>
            </button>
        </div>

    </form>

    {{-- ═══ Modal autenticación Directivo ═══ --}}
    <div id="dir-modal-overlay">
        <div id="dir-modal-box">
            <div class="text-center mb-5">
                <div class="mx-auto w-14 h-14 rounded-full bg-amber-100 flex items-center justify-center mb-3">
                    <svg class="w-7 h-7 text-amber-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.501 20.118a7.5 7.5 0 0114.998 0A17.933 17.933 0 0112 21.75c-2.676 0-5.216-.584-7.499-1.632z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-extrabold text-gray-900">Autorización de Directivo</h3>
                <p class="text-xs text-gray-500 mt-1">
                    El stock propuesto no cubre el cupo máximo.<br>
                    Un directivo debe autorizar el aumento de inventario.
                </p>
                <div id="dir-deficit-info" class="mt-2 text-xs font-bold text-amber-700 bg-amber-50 rounded-lg p-2"></div>
            </div>

            <div class="space-y-3">
                <div>
                    <label class="field-label" for="dir-email">Correo del directivo <span class="req">*</span></label>
                    <input id="dir-email" type="email" class="field-input" placeholder="directivo@ejemplo.com" autocomplete="off">
                </div>
                <div>
                    <label class="field-label" for="dir-password">Contraseña <span class="req">*</span></label>
                    <input id="dir-password" type="password" class="field-input" placeholder="••••••••" autocomplete="off">
                </div>
                <p id="dir-error" class="text-xs text-red-600 font-semibold hidden"></p>
            </div>

            <div class="flex gap-3 mt-5">
                <button type="button" id="btn-dir-cancel" class="btn-secondary flex-1">Cancelar</button>
                <button type="button" id="btn-dir-confirm" class="btn-primary flex-1">Autorizar</button>
            </div>
        </div>
    </div>

    {{-- ═══ Modal nuevo tipo de documento ═══ --}}
    <div id="doc-modal-overlay">
        <div id="doc-modal-box">
            <div class="mb-4">
                <h3 class="text-lg font-extrabold text-gray-900">Agregar documento al catálogo</h3>
                <p class="text-xs text-gray-500 mt-1">
                    Si el documento no aparece en la lista, créalo aquí y se seleccionará automáticamente.
                </p>
            </div>

            <div>
                <label class="field-label" for="doc-nombre">Nombre del documento <span class="req">*</span></label>
                <input id="doc-nombre" type="text" class="field-input" maxlength="120" placeholder="Ej. Carta de ingresos">

                <div class="mt-3 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="field-label" for="doc-tipo-archivo">Tipo de archivo permitido <span class="req">*</span></label>
                        <select id="doc-tipo-archivo" class="field-input">
                            <option value="pdf">PDF</option>
                            <option value="image">Imagen (JPG, PNG, WEBP)</option>
                            <option value="word">Word (DOC, DOCX)</option>
                            <option value="excel">Excel/CSV (XLS, XLSX, CSV)</option>
                            <option value="zip">Comprimido (ZIP, RAR, 7Z)</option>
                            <option value="any">Cualquiera</option>
                        </select>
                    </div>

                    <div class="flex items-end">
                        <label class="w-full flex items-center justify-between gap-3 p-2.5 rounded-xl border border-gray-200 bg-gray-50">
                            <span class="text-xs font-semibold text-gray-700">Validar tipo de archivo</span>
                            <input id="doc-validar-tipo" type="checkbox" class="w-4 h-4 accent-blue-700" checked>
                        </label>
                    </div>
                </div>

                <p id="doc-error" class="text-xs text-red-600 font-semibold mt-2 hidden"></p>
            </div>

            <div class="flex gap-3 mt-5">
                <button type="button" id="btn-doc-cancel" class="btn-secondary flex-1">Cancelar</button>
                <button type="button" id="btn-doc-save" class="btn-primary flex-1">Agregar</button>
            </div>
        </div>
    </div>

    {{-- Toast --}}
    <div id="toast"></div>

    {{-- ═══════════════════════════════════════════════
         SCRIPTS
    ═══════════════════════════════════════════════ --}}
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>

    <script>
    (function () {
        'use strict';

        /* ── QUILL ────────────────────────────────────────── */
        const quill = new Quill('#quill-editor', {
            theme: 'snow',
            placeholder: 'Describe el apoyo: objetivos, requisitos generales, proceso de entrega…',
            modules: {
                toolbar: [
                    [{ header: [2, 3, false] }],
                    ['bold', 'italic', 'underline'],
                    [{ list: 'ordered' }, { list: 'bullet' }],
                    ['link'],
                    ['clean']
                ]
            }
        });

        /* ── FLATPICKR ────────────────────────────────────── */
        const fpOpts = {
            locale: 'es', dateFormat: 'Y-m-d', allowInput: true,
            onChange: () => updateChecklist()
        };
        const fpInicio = flatpickr('#fechaInicio', fpOpts);
        const fpFin    = flatpickr('#fechafin', { ...fpOpts,
            onChange: () => updateChecklist()
        });

        /* ── REFS ──────────────────────────────────────────── */
        const selTipo          = document.getElementById('tipo_apoyo');
        const inpNombre        = document.getElementById('nombre_apoyo');
        const inpMontoMax      = document.getElementById('monto_maximo');
        const inpCupo          = document.getElementById('cupo_limite');
        const inpMontoAsg      = document.getElementById('monto_inicial_asignado');
        const inpStockIni      = document.getElementById('stock_inicial');
        const inpFotoRuta      = document.getElementById('foto_ruta');
        const secEco           = document.getElementById('section-economico');
        const secEsp           = document.getElementById('section-especie');
        const invAlert         = document.getElementById('inv-alert');
        const invAlertTitle    = document.getElementById('inv-alert-title');
        const invAlertBody     = document.getElementById('inv-alert-body');
        const invApprovedBadge = document.getElementById('inv-approved-badge');
        const invApprovedText  = document.getElementById('inv-approved-text');
        const lblCobertura     = document.getElementById('lbl-cobertura');
        const lblDeficitInfo   = document.getElementById('lbl-deficit-info');
        const btnGuardar       = document.getElementById('btn-guardar');
        const badgeTipo        = document.getElementById('badge-tipo');
        const panelFinTitle    = document.getElementById('panel-fin-title');
        const imgPreview       = document.getElementById('img-preview');
        const imgPlaceholder   = document.getElementById('img-placeholder');
        const imgName          = document.getElementById('img-name');
        const btnRemoveImg     = document.getElementById('btn-remove-img');
        const descHidden       = document.getElementById('descripcion-hidden');
        const stockAprobadoPor = document.getElementById('stock-aprobado-por');
        const form             = document.getElementById('apoyo-form');

        // Checklist dots
        const chkNombre     = document.getElementById('chk-nombre');
        const chkFechas     = document.getElementById('chk-fechas');
        const chkMonto      = document.getElementById('chk-monto');
        const chkCupo       = document.getElementById('chk-cupo');
        const chkPresupuesto= document.getElementById('chk-presupuesto');
        const chkInventario = document.getElementById('chk-inventario');
        const chkAprobacion = document.getElementById('chk-aprobacion');

        // Resumen
        const resTipo        = document.getElementById('res-tipo');
        const resMonto       = document.getElementById('res-monto');
        const resCupo        = document.getElementById('res-cupo');
        const resPresupuesto = document.getElementById('res-presupuesto');
        const resVigencia    = document.getElementById('res-vigencia');
        const resMLabel      = document.getElementById('res-monto-label');
        const chkMontoLbl    = document.getElementById('chk-monto-lbl');
        const chkPresLbl     = document.getElementById('chk-presupuesto-lbl');

        // Director modal
        const dirOverlay   = document.getElementById('dir-modal-overlay');
        const dirEmail     = document.getElementById('dir-email');
        const dirPassword  = document.getElementById('dir-password');
        const dirError     = document.getElementById('dir-error');
        const dirDeficit   = document.getElementById('dir-deficit-info');
        const btnDirCancel = document.getElementById('btn-dir-cancel');
        const btnDirConf   = document.getElementById('btn-dir-confirm');

        // Documento modal
        const docsGrid        = document.getElementById('docs-grid');
        const docsEmpty       = document.getElementById('docs-empty');
        const btnOpenDocModal = document.getElementById('btn-open-doc-modal');
        const docOverlay      = document.getElementById('doc-modal-overlay');
        const docNombre       = document.getElementById('doc-nombre');
        const docTipoArchivo  = document.getElementById('doc-tipo-archivo');
        const docValidarTipo  = document.getElementById('doc-validar-tipo');
        const docError        = document.getElementById('doc-error');
        const btnDocCancel    = document.getElementById('btn-doc-cancel');
        const btnDocSave      = document.getElementById('btn-doc-save');
        const btnToggleDocAdmin = document.getElementById('btn-toggle-doc-admin');
        const docAdminWrap      = document.getElementById('doc-admin-wrap');
        const docAdminBody      = document.getElementById('doc-admin-body');

        // State
        let inventarioAprobado = false;
        let stockAprobadoFinal = 0;
        let deficitActual      = 0;
        let hitoIndexCounter   = {{ count($milestonesBase ?? []) }};

        const btnAddHito       = document.getElementById('btn-add-hito');
        const hitosCustomGrid  = document.getElementById('hitos-custom-grid');

        function addCustomMilestoneRow() {
            if (!hitosCustomGrid) return;

            const idx = hitoIndexCounter++;
            const wrapper = document.createElement('div');
            wrapper.className = 'rounded-xl border border-slate-200 p-3 bg-white grid grid-cols-1 sm:grid-cols-2 gap-3';
            wrapper.innerHTML = `
                <input type="hidden" name="hitos[${idx}][incluir]" value="1">
                <input type="hidden" name="hitos[${idx}][es_base]" value="0">
                <div class="sm:col-span-2 flex items-center justify-between gap-2">
                    <label class="field-label !mb-0">Hito adicional</label>
                    <button type="button" class="text-xs font-bold text-red-600 hover:text-red-700" data-remove-hito="1">Eliminar</button>
                </div>
                <div class="sm:col-span-2">
                    <input type="text" name="hitos[${idx}][titulo]" class="field-input" placeholder="Nombre del hito" required>
                </div>
                <div class="sm:col-span-1">
                    <div class="space-y-2">
                        <div>
                            <label class="field-label">Fecha de inicio</label>
                            <input type="date" name="hitos[${idx}][fecha_inicio]" class="field-input hito-fecha-inicio">
                        </div>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600">
                            <input type="checkbox" name="hitos[${idx}][tiene_fin]" value="1" class="hito-toggle-fin w-4 h-4 accent-blue-700">
                            Tiene fecha fin
                        </label>
                        <div>
                            <label class="field-label">Fecha de fin</label>
                            <input type="date" name="hitos[${idx}][fecha_fin]" class="field-input hito-fecha-fin" disabled>
                        </div>
                    </div>
                </div>
            `;

            hitosCustomGrid.appendChild(wrapper);
            initHitoRow(wrapper);
        }

        function syncHitoDateRange(row) {
            const startInput = row.querySelector('input[name*="[fecha_inicio]"]');
            const endInput = row.querySelector('.hito-fecha-fin');
            if (!startInput || !endInput) return;

            endInput.min = startInput.value || '';
            if (startInput.value && endInput.value && endInput.value < startInput.value) {
                endInput.setCustomValidity('La fecha de fin no puede ser menor que la de inicio.');
            } else {
                endInput.setCustomValidity('');
            }
        }

        function syncHitoFinToggle(row) {
            const finToggle = row.querySelector('.hito-toggle-fin');
            const endInput = row.querySelector('.hito-fecha-fin');
            if (!finToggle || !endInput) return;

            if (finToggle.checked) {
                endInput.disabled = false;
            } else {
                endInput.value = '';
                endInput.disabled = true;
                endInput.setCustomValidity('');
            }
        }

        function syncHitoIncludeToggle(row) {
            const includeToggle = row.querySelector('.hito-toggle-incluir');
            if (!includeToggle) return;

            if (row.dataset.mandatoryBase === '1') {
                includeToggle.checked = true;
            }

            const content = row.querySelectorAll('[data-hito-content="1"] input, [data-hito-content="1"] select, [data-hito-content="1"] textarea, [data-hito-content="1"] .hito-fecha-fin');
            row.classList.toggle('opacity-60', !includeToggle.checked);

            content.forEach((input) => {
                if (includeToggle.checked) {
                    input.disabled = false;
                } else {
                    input.disabled = true;
                    if (input.classList.contains('hito-fecha-fin')) {
                        input.value = '';
                    }
                    input.setCustomValidity('');
                }
            });
        }

        function initHitoRow(row) {
            const startInput = row.querySelector('input[name*="[fecha_inicio]"]');
            const endInput = row.querySelector('.hito-fecha-fin');
            const finToggle = row.querySelector('.hito-toggle-fin');
            const includeToggle = row.querySelector('.hito-toggle-incluir');
            const includeCheckbox = row.querySelector('[data-hito-incluir-cb]');
            const includeHidden = row.querySelector('.hito-incluir-hidden');

            if (row.dataset.mandatoryBase === '1' && finToggle) {
                finToggle.checked = false;
            }

            if (startInput) {
                startInput.classList.add('hito-fecha-inicio');
                startInput.addEventListener('change', () => syncHitoDateRange(row));
            }

            if (endInput) {
                endInput.addEventListener('change', () => syncHitoDateRange(row));
            }

            if (finToggle) {
                finToggle.addEventListener('change', () => {
                    syncHitoFinToggle(row);
                    syncHitoDateRange(row);
                });
            }

            if (includeToggle) {
                includeToggle.addEventListener('change', () => syncHitoIncludeToggle(row));
            }

            // Sincronizar checkbox con campo hidden
            if (includeCheckbox && includeHidden) {
                includeCheckbox.addEventListener('change', () => {
                    includeHidden.value = includeCheckbox.checked ? '1' : '0';
                    syncHitoIncludeToggle(row);
                });
                // Establecer el valor inicial
                includeHidden.value = includeCheckbox.checked ? '1' : '0';
            }

            syncHitoFinToggle(row);
            syncHitoIncludeToggle(row);
            syncHitoDateRange(row);
        }

        if (btnAddHito) {
            btnAddHito.addEventListener('click', addCustomMilestoneRow);
        }

        if (hitosCustomGrid) {
            hitosCustomGrid.addEventListener('click', function (event) {
                const btn = event.target.closest('[data-remove-hito="1"]');
                if (!btn) return;
                const row = btn.closest('.rounded-xl');
                if (row) row.remove();
            });
        }

        document.querySelectorAll('#hitos-base-grid > .rounded-xl, #hitos-custom-grid > .rounded-xl').forEach(initHitoRow);

        /* ── HELPERS ──────────────────────────────────────── */
        function fmt(n) {
            return '$' + parseFloat(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        }
        function setChk(el, ok, na = false) {
            const dot = el.querySelector('.chk-icon');
            dot.className = 'chk-icon w-4 h-4 rounded-full flex-shrink-0 ' +
                (na ? 'bg-gray-200' : ok ? 'bg-green-400' : 'bg-red-400');
        }
        function showToast(msg, type = 'info') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.className = 'show ' + type;
            setTimeout(() => t.className = t.className.replace('show', '').trim(), 3500);
        }

        function appendDocumentoOption(documento) {
            if (!docsGrid || !documento) return;

            if (docsEmpty) {
                docsEmpty.classList.add('hidden');
            }
            docsGrid.style.display = 'grid';

            const label = document.createElement('label');
            label.className = 'doc-check';
            const tipoBadge = (documento.tipo_archivo_permitido || 'pdf').toUpperCase();
            label.innerHTML =
                `<input type="checkbox" name="documentos_requeridos[]" value="${documento.id_tipo_doc}" class="w-4 h-4 accent-blue-700" checked>` +
                `<span></span>` +
                `<span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 text-slate-600 uppercase">${tipoBadge}</span>`;
            label.querySelector('span').textContent = documento.nombre_documento;
            docsGrid.prepend(label);

            appendDocumentoAdminRow(documento);
        }

        function appendDocumentoAdminRow(documento) {
            if (!docAdminBody || !documento) return;

            const tr = document.createElement('tr');
            tr.className = 'border-t border-gray-100';
            tr.dataset.docId = documento.id_tipo_doc;

            const tipo = documento.tipo_archivo_permitido || 'pdf';
            const validar = documento.validar_tipo_archivo === undefined ? true : !!documento.validar_tipo_archivo;

            tr.innerHTML = `
                <td class="p-2 font-semibold text-gray-700"></td>
                <td class="p-2">
                    <select class="field-input !py-1.5 !text-xs doc-admin-tipo">
                        <option value="pdf" ${tipo === 'pdf' ? 'selected' : ''}>PDF</option>
                        <option value="image" ${tipo === 'image' ? 'selected' : ''}>Imagen</option>
                        <option value="word" ${tipo === 'word' ? 'selected' : ''}>Word</option>
                        <option value="excel" ${tipo === 'excel' ? 'selected' : ''}>Excel/CSV</option>
                        <option value="zip" ${tipo === 'zip' ? 'selected' : ''}>ZIP/RAR/7Z</option>
                        <option value="any" ${tipo === 'any' ? 'selected' : ''}>Cualquiera</option>
                    </select>
                </td>
                <td class="p-2">
                    <label class="inline-flex items-center gap-2">
                        <input type="checkbox" class="w-4 h-4 accent-blue-700 doc-admin-validar" ${validar ? 'checked' : ''}>
                        <span class="text-gray-600">Activo</span>
                    </label>
                </td>
                <td class="p-2">
                    <button type="button" class="btn-secondary !py-1.5 !px-3 !text-xs doc-admin-save">Guardar</button>
                </td>
            `;

            tr.children[0].textContent = documento.nombre_documento;
            docAdminBody.prepend(tr);
        }

        function routeDocUpdate(id) {
            return `{{ route('apoyos.documentos.update', ['id' => '__ID__']) }}`.replace('__ID__', String(id));
        }

        /* ── TIPO SWITCH ──────────────────────────────────── */
        function onTipoChange() {
            const tipo = selTipo.value;
            const isEco = tipo === 'Económico';

            secEco.classList.toggle('hidden', !isEco);
            secEsp.classList.toggle('hidden', isEco);
            panelFinTitle.textContent = isEco ? 'Financiamiento' : 'Inventario';

            badgeTipo.textContent = tipo;
            badgeTipo.className = 'inline-flex items-center px-3 py-1 rounded-full text-xs font-bold ring-1 ' +
                (isEco ? 'bg-amber-100 text-amber-800 ring-amber-200'
                       : 'bg-emerald-100 text-emerald-800 ring-emerald-200');

            // Checklist labels
            chkMontoLbl.textContent = isEco ? 'Monto máximo' : 'Valor/unidad (opcional)';
            chkPresLbl.textContent  = isEco ? 'Presupuesto asignado' : 'Stock inicial';
            resMLabel.textContent   = isEco ? 'Monto máx.' : 'Valor/unidad';

            document.getElementById('chk-inventario').classList.toggle('hidden', isEco);
            chkAprobacion.style.display = 'none';
            invAlert.style.display = 'none';
            inventarioAprobado     = false;
            stockAprobadoFinal     = 0;

            // Monto_maximo label
            document.getElementById('lbl-monto-maximo').innerHTML =
                isEco ? 'Monto máximo por beneficiario <span class="req">*</span>'
                      : 'Valor estimado por unidad (opcional)';
            document.getElementById('req-cupo').style.display = isEco ? '' : '';

            updateChecklist();
        }

        selTipo.addEventListener('change', onTipoChange);

        /* ── COBERTURA ESTIMADA (Económico) ───────────────── */
        function updateCobertura() {
            const monto = parseFloat(inpMontoAsg.value) || 0;
            const max   = parseFloat(inpMontoMax.value) || 0;
            if (monto > 0 && max > 0) {
                const est = Math.floor(monto / max);
                lblCobertura.textContent = `Cobertura estimada: ~${est.toLocaleString('es-MX')} beneficiarios`;
                lblCobertura.className = 'text-xs mt-1 ' + (est > 0 ? 'text-blue-600' : 'text-gray-400');
            } else {
                lblCobertura.textContent = 'Cobertura estimada: —';
                lblCobertura.className = 'text-xs text-gray-400 mt-1';
            }
        }
        inpMontoAsg.addEventListener('input', updateCobertura);
        inpMontoMax.addEventListener('input', updateCobertura);

        /* ── INVENTARIO CHECK (Especie) ───────────────────── */
        let invTimer = null;
        function scheduleInvCheck() {
            clearTimeout(invTimer);
            invTimer = setTimeout(checkInventario, 600);
        }

        async function checkInventario() {
            const stock = parseInt(inpStockIni.value, 10);
            const cupo  = parseInt(inpCupo.value, 10);

            if (isNaN(stock) || isNaN(cupo) || selTipo.value !== 'Especie') {
                invAlert.style.display = 'none';
                updateChecklist();
                return;
            }

            const deficit = Math.max(0, cupo - stock);
            deficitActual = deficit;

            if (deficit === 0) {
                invAlert.style.display = 'none';
                inventarioAprobado = false;
                stockAprobadoFinal = stock;
                lblDeficitInfo.textContent = `✓ Stock suficiente (${stock.toLocaleString('es-MX')} uds. ≥ cupo de ${cupo.toLocaleString('es-MX')})`;
                lblDeficitInfo.className = 'text-xs text-green-600 mt-1 font-semibold';
            } else {
                invAlert.style.display = 'block';
                invAlert.classList.remove('error-state');
                invAlertTitle.textContent = 'Inventario insuficiente';
                invAlertBody.textContent  =
                    `El stock inicial (${stock.toLocaleString('es-MX')}) no cubre el cupo máximo ` +
                    `(${cupo.toLocaleString('es-MX')}). Déficit: ${deficit.toLocaleString('es-MX')} unidades.`;
                lblDeficitInfo.textContent = `⚠ Déficit de ${deficit.toLocaleString('es-MX')} unidades`;
                lblDeficitInfo.className = 'text-xs text-amber-600 mt-1 font-semibold';

                if (!inventarioAprobado) {
                    invApprovedBadge.style.display = 'none';
                }
            }
            updateChecklist();
        }

        inpStockIni.addEventListener('input', () => { inventarioAprobado = false; scheduleInvCheck(); });
        inpCupo.addEventListener('input',     () => { scheduleInvCheck(); updateChecklist(); });

        /* ── CHECKLIST / RESUMEN ──────────────────────────── */
        function updateChecklist() {
            const tipo   = selTipo.value;
            const isEco  = tipo === 'Económico';
            const nombre = inpNombre.value.trim();
            const inicio = document.getElementById('fechaInicio').value;
            const fin    = document.getElementById('fechafin').value;
            const monto  = parseFloat(inpMontoMax.value) || 0;
            const cupo   = parseInt(inpCupo.value, 10);
            const presup = isEco ? (parseFloat(inpMontoAsg.value) || 0)
                                 : (parseInt(inpStockIni.value, 10) || 0);

            setChk(chkNombre, nombre.length > 0);
            setChk(chkFechas, inicio && fin && fin >= inicio);
            setChk(chkMonto,  !isEco || monto > 0, !isEco);  // opcional en Especie
            setChk(chkCupo,   !isNaN(cupo) && cupo > 0);
            setChk(chkPresupuesto, presup > 0);

            if (!isEco) {
                const invOk = inventarioAprobado || deficitActual === 0;
                setChk(chkInventario, invOk);
                document.getElementById('chk-inventario').classList.remove('hidden');

                const needsAprobacion = deficitActual > 0;
                chkAprobacion.style.display = needsAprobacion ? 'flex' : 'none';
                if (needsAprobacion) {
                    setChk(chkAprobacion, inventarioAprobado);
                }
            }

            // Resumen
            resTipo.textContent  = tipo;
            resMonto.textContent = isEco ? (monto > 0 ? fmt(monto) : '—') : (monto > 0 ? fmt(monto) : '—');
            resCupo.textContent  = (!isNaN(cupo) && cupo > 0) ? `${cupo.toLocaleString('es-MX')} beneficiarios` : '—';
            resPresupuesto.textContent = isEco
                ? (presup > 0 ? fmt(presup) : '—')
                : (presup > 0 ? `${presup.toLocaleString('es-MX')} uds.` : '—');
            resVigencia.textContent = (inicio && fin) ? `${inicio} → ${fin}` : '—';

            // Habilitar botón
            const hasNombre  = nombre.length > 0;
            const hasFechas  = inicio && fin && fin >= inicio;
            const hasPresup  = presup > 0;
            const hasCupo    = !isNaN(cupo) && cupo > 0;
            const invOk      = isEco || inventarioAprobado || deficitActual === 0;
            const montoOk    = isEco ? monto > 0 : true;

            btnGuardar.disabled = !(hasNombre && hasFechas && hasPresup && hasCupo && invOk && montoOk);
        }

        inpNombre.addEventListener('input', updateChecklist);
        inpMontoMax.addEventListener('input', updateChecklist);
        inpMontoAsg.addEventListener('input', updateChecklist);

        /* Contador nombre */
        inpNombre.addEventListener('input', () => {
            document.getElementById('nombre-count').textContent = inpNombre.value.length;
        });

        /* ── IMAGEN PREVIEW ───────────────────────────────── */
        inpFotoRuta.addEventListener('change', function () {
            const file = this.files[0];
            if (!file) return;
            if (file.size > 5 * 1024 * 1024) {
                showToast('La imagen supera el tamaño máximo permitido (5 MB).', 'error');
                this.value = '';
                return;
            }
            const reader = new FileReader();
            reader.onload = e => {
                imgPreview.src = e.target.result;
                imgPreview.style.display = 'block';
                imgPlaceholder.style.display = 'none';
                imgName.textContent = file.name;
                imgName.classList.remove('hidden');
                btnRemoveImg.classList.remove('hidden');
                document.getElementById('img-preview-wrap').style.cursor = 'default';
            };
            reader.readAsDataURL(file);
        });

        btnRemoveImg.addEventListener('click', function () {
            inpFotoRuta.value = '';
            imgPreview.src = '';
            imgPreview.style.display = 'none';
            imgPlaceholder.style.display = 'flex';
            imgName.classList.add('hidden');
            btnRemoveImg.classList.add('hidden');
            document.getElementById('img-preview-wrap').style.cursor = 'pointer';
        });

        /* ── SOLICITAR APROBACIÓN DIRECTIVO ──────────────── */
        document.getElementById('btn-solicitar-aprobacion').addEventListener('click', () => {
            dirDeficit.textContent =
                `Stock propuesto: ${parseInt(inpStockIni.value||0).toLocaleString('es-MX')} uds. | ` +
                `Cupo requerido: ${parseInt(inpCupo.value||0).toLocaleString('es-MX')} uds. | ` +
                `Déficit: ${deficitActual.toLocaleString('es-MX')} uds.`;
            dirEmail.value = '';
            dirPassword.value = '';
            dirError.classList.add('hidden');
            dirOverlay.classList.add('open');
        });

        btnDirCancel.addEventListener('click', () => dirOverlay.classList.remove('open'));
        dirOverlay.addEventListener('click', e => {
            if (e.target === dirOverlay) dirOverlay.classList.remove('open');
        });

        /* ── AGREGAR TIPO DE DOCUMENTO ───────────────────── */
        btnOpenDocModal.addEventListener('click', () => {
            docNombre.value = '';
            docTipoArchivo.value = 'pdf';
            docValidarTipo.checked = true;
            docError.classList.add('hidden');
            docOverlay.classList.add('open');
            setTimeout(() => docNombre.focus(), 80);
        });

        btnDocCancel.addEventListener('click', () => docOverlay.classList.remove('open'));
        docOverlay.addEventListener('click', e => {
            if (e.target === docOverlay) docOverlay.classList.remove('open');
        });

        docNombre.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                btnDocSave.click();
            }
        });

        btnDocSave.addEventListener('click', async () => {
            const nombre = docNombre.value.trim();
            const tipoArchivo = docTipoArchivo.value;
            const validarTipo = docValidarTipo.checked;
            if (!nombre) {
                docError.textContent = 'Ingresa el nombre del documento.';
                docError.classList.remove('hidden');
                return;
            }

            btnDocSave.disabled = true;
            btnDocSave.textContent = 'Guardando...';
            docError.classList.add('hidden');

            try {
                const res = await fetch('{{ route('apoyos.documentos.store') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nombre_documento: nombre,
                        tipo_archivo_permitido: tipoArchivo,
                        validar_tipo_archivo: validarTipo,
                    }),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    docError.textContent = data.message || 'No se pudo agregar el documento.';
                    docError.classList.remove('hidden');
                    return;
                }

                appendDocumentoOption(data.documento);
                docOverlay.classList.remove('open');
                showToast(data.message || 'Documento agregado.', 'success');

            } catch (err) {
                docError.textContent = 'Error de conexión. Intenta de nuevo.';
                docError.classList.remove('hidden');
            } finally {
                btnDocSave.disabled = false;
                btnDocSave.textContent = 'Agregar';
            }
        });

        btnToggleDocAdmin.addEventListener('click', () => {
            const open = docAdminWrap.style.display !== 'none';
            docAdminWrap.style.display = open ? 'none' : 'block';
            btnToggleDocAdmin.textContent = open
                ? 'Administrar catálogo de documentos'
                : 'Ocultar administración de catálogo';
        });

        docAdminBody.addEventListener('click', async (e) => {
            const btn = e.target.closest('.doc-admin-save');
            if (!btn) return;

            const row = btn.closest('tr[data-doc-id]');
            if (!row) return;

            const id = row.dataset.docId;
            const tipo = row.querySelector('.doc-admin-tipo')?.value || 'pdf';
            const validar = !!row.querySelector('.doc-admin-validar')?.checked;

            btn.disabled = true;
            btn.textContent = 'Guardando...';

            try {
                const res = await fetch(routeDocUpdate(id), {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        _method: 'PUT',
                        tipo_archivo_permitido: tipo,
                        validar_tipo_archivo: validar,
                    }),
                });

                const data = await res.json();
                if (!res.ok || !data.success) {
                    showToast(data.message || 'No se pudo actualizar.', 'error');
                    return;
                }

                showToast(data.message || 'Configuración guardada.', 'success');

            } catch (err) {
                showToast('Error de conexión al actualizar el catálogo.', 'error');
            } finally {
                btn.disabled = false;
                btn.textContent = 'Guardar';
            }
        });

        btnDirConf.addEventListener('click', async () => {
            const email    = dirEmail.value.trim();
            const password = dirPassword.value;
            const cupo     = parseInt(inpCupo.value, 10);

            if (!email || !password) {
                dirError.textContent = 'Ingresa correo y contraseña.';
                dirError.classList.remove('hidden');
                return;
            }

            btnDirConf.disabled = true;
            btnDirConf.textContent = 'Verificando…';

            try {
                const res = await fetch('{{ route('apoyos.aprobar-inventario') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ email, password, stock_solicitado: cupo }),
                });

                const data = await res.json();

                if (!res.ok || !data.ok) {
                    dirError.textContent = data.message || 'No se pudo verificar.';
                    dirError.classList.remove('hidden');
                    return;
                }

                // Éxito
                inventarioAprobado = true;
                stockAprobadoFinal = data.stock_aprobado;
                inpStockIni.value  = data.stock_aprobado;
                stockAprobadoPor.value = data.aprobado_por;
                deficitActual      = 0;

                invApprovedBadge.style.display = 'flex';
                invApprovedText.textContent =
                    `✓ Aprobado por ${data.aprobado_por}`;
                invAlert.style.display = 'none';

                dirOverlay.classList.remove('open');
                showToast(`Stock aprobado por ${data.aprobado_por}.`, 'success');
                updateChecklist();

            } catch (err) {
                dirError.textContent = 'Error de conexión. Intenta de nuevo.';
                dirError.classList.remove('hidden');
            } finally {
                btnDirConf.disabled = false;
                btnDirConf.textContent = 'Autorizar';
            }
        });

        /* ── SUBMIT ────────────────────────────────────────── */
        form.addEventListener('submit', async function (ev) {
            ev.preventDefault();

            // Sincronizar descripción Quill
            descHidden.value = quill.root.innerHTML === '<p><br></p>'
                ? '' : quill.root.innerHTML;

            const btnLabel   = document.getElementById('btn-label');
            const btnSpinner = document.getElementById('btn-spinner');
            btnGuardar.disabled = true;
            btnLabel.textContent = 'Guardando…';
            btnSpinner.style.display = 'inline-block';

            try {
                const res = await fetch(form.action, {
                    method: 'POST',
                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                    body: new FormData(form),
                });

                const data = await res.json();

                if (!res.ok || !data.success) {
                    showToast(data.message || 'Error al guardar el apoyo.', 'error');
                    btnGuardar.disabled = false;
                    btnLabel.textContent = 'Guardar Apoyo';
                    btnSpinner.style.display = 'none';
                    return;
                }

                showToast('Apoyo registrado correctamente.', 'success');
                setTimeout(() => window.location.href = '{{ route('apoyos.index') }}', 1200);

            } catch (err) {
                showToast('Error de conexión. Intenta de nuevo.', 'error');
                btnGuardar.disabled = false;
                btnLabel.textContent = 'Guardar Apoyo';
                btnSpinner.style.display = 'none';
            }
        });

        /* ── INIT ──────────────────────────────────────────── */
        onTipoChange();
        updateChecklist();

    })();
    </script>
        </main>
        <x-site-footer class="mt-16" />
    </div>
</body>
</html>
