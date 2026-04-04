@php
use Carbon\Carbon;

// Determinar si es crear o editar
$isCreating = request()->input('mode', 'edit') === 'create' || !isset($apoyo);
$isEditing = !$isCreating;

// Procesar hitos
if ($isEditing) {
    $existingMilestonesCollection = collect($existingMilestones ?? []);
    $baseMilestonesCollection = collect($milestonesBase ?? []);
    $normalizedMilestones = $existingMilestonesCollection->map(function ($hito) {
        $slug = $hito->slug_hito ?? null;
        if (empty($slug) && !empty($hito->clave_hito)) {
            $slug = strtolower((string) $hito->clave_hito);
        }
        $hito->__slug_normalizado = $slug;
        $hito->__titulo_normalizado = $hito->titulo_hito ?? $hito->nombre_hito ?? $hito->clave_hito ?? $hito->slug_hito ?? 'Hito';
        return $hito;
    });
    $baseMilestonesBySlug = $normalizedMilestones->where('es_base', 1)->keyBy('__slug_normalizado');
    $customMilestones = $normalizedMilestones->where('es_base', 0)->values();
} else {
    $existingMilestonesCollection = collect([]);
    $baseMilestonesCollection = collect($milestonesBase ?? []);
    $normalizedMilestones = collect([]);
    $baseMilestonesBySlug = collect([]);
    $customMilestones = collect([]);
    $apoyo = null;
}
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $isCreating ? 'Nuevo Apoyo' : 'Editar Apoyo' }} - {{ config('app.name', 'SIGO') }}</title>
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
                            <h2 class="font-extrabold text-xl text-gray-900 leading-tight">
                                {{ $isCreating ? 'Nuevo Apoyo' : 'Editar Apoyo' }}
                            </h2>
                            <p class="text-xs text-gray-500 mt-0.5">
                                {{ $isCreating ? 'Completa todos los campos para registrar un programa de apoyo' : 'Modifica los detalles del programa de apoyo' }}
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <span id="badge-tipo"
                              class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-800 ring-1 ring-amber-200">
                            {{ old('tipo_apoyo', $apoyo->tipo_apoyo ?? 'Económico') }}
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
                    outline: none;
                    transition: border-color .2s, box-shadow .2s;
                }
                .field-input:focus {
                    border-color: var(--blue);
                    box-shadow: 0 0 0 3px rgba(26,74,138,.1);
                }
                .field-input.error { border-color: #ef4444; }
                .prefix-wrap {
                    position: relative;
                    display: flex;
                    font-size: .85rem; font-weight: 600; color: #6b7280;
                }
                .prefix { position: absolute; left: .75rem; top: 50%; transform: translateY(-50%); pointer-events: none; }
                .prefix-wrap .field-input { padding-left: 1.75rem; }
                .submit-bar {
                    position: fixed; bottom: 0; left: 0; right: 0;
                    background: white; border-top: 1.5px solid #e5e7eb;
                    padding: 1rem; display: flex; gap: .75rem; justify-content: flex-end;
                    z-index: 40;
                }
                .btn-primary {
                    background: var(--blue);
                    color: #fff; border: none; border-radius: 10px; padding: .65rem 2rem;
                    font-size: .875rem; font-weight: 600; cursor: pointer;
                    transition: background .2s, transform .15s;
                }
                .btn-primary:hover { background: #0f3a6b; transform: translateY(-2px); }
                .btn-primary:disabled { opacity: .5; cursor: not-allowed; transform: none; }
                .btn-secondary {
                    background: #f1f5f9; color: #374151; border: 1.5px solid #d1d5db;
                    border-radius: 10px; padding: .65rem 1.25rem; font-size: .875rem;
                    font-weight: 600; cursor: pointer; transition: background .2s;
                }
                .btn-secondary:hover { background: #e2e8f0; }
            </style>

            @if ($isCreating)
                <form id="formularioApoyo" method="POST" action="{{ route('apoyos.store') }}" enctype="multipart/form-data" novalidate>
            @else
                <form id="formularioApoyo" method="POST" action="{{ route('apoyos.update', $apoyo->id_apoyo) }}" enctype="multipart/form-data" novalidate>
            @endif
                @csrf
                @if ($isEditing)
                    @method('PUT')
                @endif
                <input type="hidden" name="descripcion" id="descripcion-hidden">
                <input type="hidden" name="documentos_requeridos_present" value="1">
                <input type="hidden" name="mode" value="{{ $isCreating ? 'create' : 'edit' }}">

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
                            <div class="panel-body space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div class="sm:col-span-2">
                                        <label class="field-label" for="nombre_apoyo">Nombre del apoyo <span class="req">*</span></label>
                                        <input id="nombre_apoyo" name="nombre_apoyo" type="text"
                                               class="field-input" placeholder="Ej. Beca de emprendimiento juvenil"
                                               required maxlength="100" value="{{ old('nombre_apoyo', $apoyo->nombre_apoyo ?? '') }}"
                                               {{ $isEditing ? '' : '' }}>
                                    </div>

                                    <div>
                                        <label class="field-label" for="tipo_apoyo">Tipo de apoyo <span class="req">*</span></label>
                                        <select id="tipo_apoyo" name="tipo_apoyo" class="field-input" required>
                                            <option value="Económico" {{ old('tipo_apoyo', $apoyo->tipo_apoyo ?? 'Económico') === 'Económico' ? 'selected' : '' }}>💰 Económico</option>
                                            <option value="Especie" {{ old('tipo_apoyo', $apoyo->tipo_apoyo ?? '') === 'Especie' ? 'selected' : '' }}>📦 Especie (material)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="field-label" for="anio_fiscal">Año fiscal</label>
                                        <input id="anio_fiscal" name="anio_fiscal" type="number" class="field-input"
                                               value="{{ old('anio_fiscal', $apoyo->anio_fiscal ?? date('Y')) }}" min="2020" max="2099"
                                               {{ $isEditing ? 'disabled' : '' }}>
                                        @if ($isEditing)
                                            <small class="text-gray-500">No se puede cambiar</small>
                                        @endif
                                    </div>
                                </div>

                                <div class="border-t border-dashed border-gray-200"></div>

                                <div>
                                    <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Período de vigencia</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="field-label" for="fechaInicio">Fecha de inicio <span class="req">*</span></label>
                                            <input id="fechaInicio" name="fechaInicio" type="text" class="field-input flatpickr"
                                                   placeholder="dd/mm/aaaa" required
                                                   value="{{ old('fechaInicio', $apoyo && $apoyo->fecha_inicio ? Carbon::parse($apoyo->fecha_inicio)->format('d/m/Y') : '') }}">
                                        </div>

                                        <div>
                                            <label class="field-label" for="fechafin">Fecha de cierre <span class="req">*</span></label>
                                            <input id="fechafin" name="fechafin" type="text" class="field-input flatpickr"
                                                   placeholder="dd/mm/aaaa" required
                                                   value="{{ old('fechafin', $apoyo && $apoyo->fecha_fin ? Carbon::parse($apoyo->fecha_fin)->format('d/m/Y') : '') }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-dashed border-gray-200"></div>

                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-xl border border-gray-200">
                                    <input type="hidden" name="activo" value="0">
                                    <input id="activo" name="activo" value="1" type="checkbox"
                                           class="w-4 h-4 accent-blue-700 cursor-pointer"
                                           {{ old('activo', $apoyo->activo ?? true) ? 'checked' : '' }}>
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
                                <span id="panel-fin-title">{{ old('tipo_apoyo', $apoyo->tipo_apoyo ?? 'Económico') === 'Económico' ? 'Financiamiento' : 'Inventario' }}</span>
                            </div>
                            <div class="panel-body space-y-6">
                                {{-- Capacidad --}}
                                <div>
                                    <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3">Alcance y capacidad</p>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label class="field-label" for="monto_maximo">Monto máximo por beneficiario <span class="req">*</span></label>
                                            <div class="prefix-wrap">
                                                <span class="prefix">$</span>
                                                <input id="monto_maximo" name="monto_maximo" type="number" step="0.01" min="0" class="field-input" placeholder="0.00" value="{{ old('monto_maximo', $apoyo->monto_maximo ?? 0) }}">
                                            </div>
                                        </div>

                                        <div>
                                            <label class="field-label" for="cupo_limite">Cupo máximo de beneficiarios <span class="req">*</span></label>
                                            <input id="cupo_limite" name="cupo_limite" type="number" min="1" step="1" class="field-input" value="{{ old('cupo_limite', $apoyo->cupo_limite ?? 1) }}">
                                        </div>
                                    </div>
                                </div>

                                <div class="border-t border-dashed border-gray-200"></div>

                                {{-- Presupuesto/Inventario --}}
                                <div>
                                    <p class="text-xs font-bold text-gray-600 uppercase tracking-wider mb-3" id="lbl-presupuesto-seccion">
                                        {{ old('tipo_apoyo', $apoyo->tipo_apoyo ?? 'Económico') === 'Económico' ? 'Presupuesto' : 'Gestión de Inventario' }}
                                    </p>

                                    {{-- ECONÓMICO: Categoría presupuestaria + monto --}}
                                    <div id="grp-economico" class="{{ old('tipo_apoyo', $apoyo->tipo_apoyo ?? 'Económico') === 'Económico' ? '' : 'hidden' }} space-y-4">
                                        <div>
                                            <label class="field-label" for="id_categoria">Categoría presupuestaria <span class="req">*</span></label>
                                            <select id="id_categoria" name="id_categoria" class="field-input">
                                                <option value="">-- Selecciona una categoría --</option>
                                                @if(isset($categorias))
                                                    @foreach($categorias as $cat)
                                                        <option value="{{ $cat->id_categoria }}"
                                                            {{ old('id_categoria', $apoyo->id_categoria ?? null) == $cat->id_categoria ? 'selected' : '' }}
                                                            data-disponible="{{ $cat->disponible }}">
                                                            {{ $cat->nombre }} (Disponible: ${{ number_format($cat->disponible, 2) }})
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <small class="text-gray-500" id="presupuesto-info">Selecciona una categoría para ver presupuesto disponible</small>
                                        </div>

                                        {{-- SECCIÓN: Presupuesto Real (Cálculo Automático) --}}
                                        <div id="presupuesto-real-section" class="hidden bg-gradient-to-br from-blue-50 to-blue-100 border-2 border-blue-300 rounded-lg p-4 space-y-4">
                                            <div class="flex items-center gap-2 mb-3">
                                                <svg class="w-5 h-5 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M4 4a2 2 0 00-2 2v4a2 2 0 002 2V6h10a2 2 0 00-2-2H4zm2 6a2 2 0 012-2h8a2 2 0 012 2v4a2 2 0 01-2 2H8a2 2 0 01-2-2v-4zm6 4a2 2 0 100-4 2 2 0 000 4z" clip-rule="evenodd"/>
                                                </svg>
                                                <h4 class="font-bold text-blue-900">Cálculo de Presupuesto Real</h4>
                                            </div>

                                            {{-- Fila 1: Presupuesto Disponible --}}
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div class="bg-white rounded p-3 border border-blue-200">
                                                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Presupuesto Disponible</p>
                                                    <p class="text-2xl font-bold text-blue-600 mt-1">$<span id="txt-presupuesto-disponible">0.00</span></p>
                                                    <p class="text-xs text-gray-500 mt-1">De categoría seleccionada</p>
                                                </div>

                                                <div class="bg-white rounded p-3 border border-orange-200">
                                                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Monto por Beneficiario</p>
                                                    <p class="text-2xl font-bold text-orange-600 mt-1">$<span id="txt-monto-beneficiario">0.00</span></p>
                                                    <p class="text-xs text-gray-500 mt-1">Del campo monto máximo</p>
                                                </div>
                                            </div>

                                            {{-- Fila 2: Cantidad Beneficiarios --}}
                                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                                <div class="bg-white rounded p-3 border border-green-200">
                                                    <p class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Cantidad Máx. Beneficiarios</p>
                                                    <p class="text-2xl font-bold text-green-600 mt-1"><span id="txt-cantidad-beneficiarios">0</span></p>
                                                    <p class="text-xs text-gray-500 mt-1">Del cupo límite</p>
                                                </div>

                                                <div class="bg-white rounded p-3 border-2 border-purple-400">
                                                    <p class="text-xs font-semibold text-gray-700 uppercase tracking-wide">💰 TOTAL NECESARIO</p>
                                                    <p class="text-3xl font-bold text-purple-600 mt-1">$<span id="txt-total-calculado">0.00</span></p>
                                                    <p class="text-xs text-gray-600 mt-1">Monto × Cantidad</p>
                                                </div>
                                            </div>

                                            {{-- Fila 3: Validación --}}
                                            <div id="div-validacion-presupuesto" class="bg-white rounded p-3 border-2 border-yellow-300 hidden">
                                                <div class="flex items-start gap-3">
                                                    <svg class="w-5 h-5 text-yellow-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <div>
                                                        <p class="font-semibold text-yellow-800">⚠️ Presupuesto Insuficiente</p>
                                                        <p class="text-sm text-yellow-700 mt-1">El total necesario excede el presupuesto disponible en categoría.</p>
                                                        <p class="text-xs text-yellow-600 mt-2">Faltante: <span class="font-bold">$<span id="txt-presupuesto-faltante">0.00</span></span></p>
                                                    </div>
                                                </div>
                                            </div>

                                            {{-- Fila 4: Resumen Disponible después de asignar --}}
                                            <div id="div-resumen-final" class="bg-white rounded p-3 border-2 border-green-300 hidden">
                                                <div class="flex items-start gap-3">
                                                    <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                    </svg>
                                                    <div>
                                                        <p class="font-semibold text-green-800">✅ Presupuesto Suficiente</p>
                                                        <p class="text-sm text-green-700 mt-1">Presupuesto disponible después de esta asignación:</p>
                                                        <p class="text-lg font-bold text-green-600 mt-2">$<span id="txt-presupuesto-restante">0.00</span></p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div id="div-monto-inicial" style="display: none;">
                                            <label class="field-label" for="monto_inicial_asignado">Monto reservado (automático) <span class="req">*</span></label>
                                            <div class="prefix-wrap">
                                                <span class="prefix">$</span>
                                                <input id="monto_inicial_asignado" name="monto_inicial_asignado" type="number" class="field-input bg-gray-100 border-gray-300 cursor-not-allowed" step="0.01" min="0" readonly value="{{ old('monto_inicial_asignado', 0) }}">
                                            </div>
                                            <small class="text-gray-500">Se calcula como: Monto máximo × Cantidad máxima beneficiarios</small>
                                        </div>
                                    </div>

                                    {{-- ESPECIE: Inicializar con stock --}}
                                    <div id="grp-especie" class="{{ old('tipo_apoyo', $apoyo->tipo_apoyo ?? 'Económico') === 'Especie' ? '' : 'hidden' }} space-y-4">
                                        <div>
                                            <label class="field-label" for="stock_inicial">Stock inicial disponible <span class="req">*</span></label>
                                            <input id="stock_inicial" name="stock_inicial" type="number" class="field-input" min="0" step="1" value="{{ old('stock_inicial', $stockInicial ?? 0) }}">
                                            <small class="text-gray-500">Cantidad inicial de unidades en inventario</small>
                                        </div>

                                        <div>
                                            <label class="field-label" for="unidad_medida">Unidad de medida <span class="req">*</span></label>
                                            <input id="unidad_medida" name="unidad_medida" type="text" class="field-input" placeholder="Ej: pieza, kit, paquete" value="{{ old('unidad_medida', 'pieza') }}">
                                        </div>

                                        <div>
                                            <label class="field-label" for="costo_unitario">Costo unitario estimado</label>
                                            <div class="prefix-wrap">
                                                <span class="prefix">$</span>
                                                <input id="costo_unitario" name="costo_unitario" type="number" class="field-input" step="0.01" min="0" placeholder="0.00" value="{{ old('costo_unitario', 0) }}">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Panel: Descripción --}}
                        <div class="panel">
                            <div class="panel-title">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25H12"/>
                                </svg>
                                Descripción del apoyo
                            </div>
                            <div class="panel-body">
                                <div id="editor-container">
                                    <div id="quill-editor">{!! old('descripcion', $apoyo->descripcion ?? '') !!}</div>
                                </div>
                                <p class="text-xs text-gray-400 mt-2">Esta descripción la verán los beneficiarios en la convocatoria.</p>
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
                                <p class="text-xs text-gray-500">{{ $isCreating ? 'Define los hitos principales' : 'Puedes ajustar los hitos y agregar adicionales.' }}</p>

                                <div id="hitos-base-grid" class="space-y-3">
                                    @foreach($baseMilestonesCollection as $i => $base)
                                        @php($saved = $baseMilestonesBySlug->get($base['slug']))
                                        @php($isMandatoryBase = in_array($base['slug'], ['inicio_publicacion', 'proceso_cerrado'], true))
                                        @php($tienePeriodo = !empty($base['tiene_periodo']) && $base['tiene_periodo'] === true)
                                        <div class="rounded-xl border border-slate-200 p-3 bg-slate-50 grid grid-cols-1 sm:grid-cols-2 gap-3" data-mandatory-base="{{ $isMandatoryBase ? '1' : '0' }}">
                                            <input type="hidden" name="hitos[{{ $i }}][slug]" value="{{ $base['slug'] }}">
                                            <input type="hidden" name="hitos[{{ $i }}][es_base]" value="1">
                                            <input type="hidden" name="hitos[{{ $i }}][incluir]" value="1" class="hito-incluir-hidden">

                                            <div class="sm:col-span-2" data-hito-content="1">
                                                <div class="flex items-center justify-between gap-3 mb-2">
                                                    <label class="field-label !mb-0">{{ $base['titulo'] ?? 'Hito' }}</label>
                                                    @if(!$isMandatoryBase)
                                                        <label class="inline-flex items-center gap-2 cursor-pointer text-xs font-semibold text-slate-600">
                                                            <input type="checkbox" data-hito-incluir-cb value="1" class="hito-toggle-incluir w-4 h-4 accent-blue-700" {{ old('hitos.' . $i . '.incluir', !empty($saved) ? '1' : '0') == '1' ? 'checked' : '' }}>
                                                            <span>Incluir</span>
                                                        </label>
                                                    @endif
                                                </div>
                                                <input type="text" name="hitos[{{ $i }}][titulo]" class="field-input" value="{{ old('hitos.' . $i . '.titulo', $saved->__titulo_normalizado ?? $base['titulo']) }}" required>
                                            </div>

                                            @if(!$tienePeriodo)
                                                <div class="sm:col-span-1" data-hito-content="1">
                                                    <label class="field-label">Fecha</label>
                                                    <input type="date" name="hitos[{{ $i }}][fecha_inicio]" class="field-input hito-fecha-inicio" value="{{ old('hitos.' . $i . '.fecha_inicio', !empty($saved?->fecha_inicio) ? Carbon::parse($saved->fecha_inicio)->toDateString() : '') }}">
                                                </div>
                                            @else
                                                <div class="sm:col-span-2 grid grid-cols-1 sm:grid-cols-2 gap-3" data-hito-content="1">
                                                    <div>
                                                        <label class="field-label">Inicio</label>
                                                        <input type="date" name="hitos[{{ $i }}][fecha_inicio]" class="field-input hito-fecha-inicio" value="{{ old('hitos.' . $i . '.fecha_inicio', !empty($saved?->fecha_inicio) ? Carbon::parse($saved->fecha_inicio)->toDateString() : '') }}" required>
                                                    </div>
                                                    <div>
                                                        <label class="field-label">Fin</label>
                                                        <input type="date" name="hitos[{{ $i }}][fecha_fin]" class="field-input hito-fecha-fin" value="{{ old('hitos.' . $i . '.fecha_fin', !empty($saved?->fecha_fin) ? Carbon::parse($saved->fecha_fin)->toDateString() : '') }}" required>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>

                                <div class="pt-2 border-t border-dashed border-slate-200">
                                    <button type="button" id="btn-add-hito" class="btn-secondary !py-2 !text-xs">+ Agregar hito adicional</button>
                                </div>

                                <div id="hitos-custom-grid" class="space-y-3">
                                    @foreach($customMilestones as $j => $custom)
                                        @php($idx = $baseMilestonesCollection->count() + $j)
                                        <div class="rounded-xl border border-slate-200 p-3 bg-white grid grid-cols-1 sm:grid-cols-3 gap-3">
                                            <input type="hidden" name="hitos[{{ $idx }}][incluir]" value="1">
                                            <input type="hidden" name="hitos[{{ $idx }}][es_base]" value="0">
                                            <div class="sm:col-span-3 flex items-center justify-between gap-2">
                                                <label class="field-label !mb-0">Hito adicional</label>
                                                <button type="button" class="text-xs font-bold text-red-600 hover:text-red-700" data-remove-hito="1">Eliminar</button>
                                            </div>
                                            <div class="sm:col-span-3">
                                                <input type="text" name="hitos[{{ $idx }}][titulo]" class="field-input" value="{{ old('hitos.' . $idx . '.titulo', $custom->__titulo_normalizado ?? 'Hito') }}" required>
                                            </div>
                                            <div>
                                                <label class="field-label">Inicio</label>
                                                <input type="date" name="hitos[{{ $idx }}][fecha_inicio]" class="field-input hito-fecha-inicio" value="{{ old('hitos.' . $idx . '.fecha_inicio', !empty($custom->fecha_inicio) ? Carbon::parse($custom->fecha_inicio)->toDateString() : '') }}">
                                            </div>
                                            <div>
                                                <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 mb-2">
                                                    <input type="checkbox" name="hitos[{{ $idx }}][tiene_fin]" value="1" class="hito-toggle-fin w-4 h-4 accent-blue-700" {{ old('hitos.' . $idx . '.fecha_fin', !empty($custom->fecha_fin) ? Carbon::parse($custom->fecha_fin)->toDateString() : '') ? 'checked' : '' }}>
                                                    Tiene fin
                                                </label>
                                                <label class="field-label">Fin</label>
                                                <input type="date" name="hitos[{{ $idx }}][fecha_fin]" class="field-input hito-fecha-fin" value="{{ old('hitos.' . $idx . '.fecha_fin', !empty($custom->fecha_fin) ? Carbon::parse($custom->fecha_fin)->toDateString() : '') }}">
                                            </div>
                                            <div class="flex items-end text-xs text-slate-500 font-semibold">Personalizado</div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                    </div>

                    {{-- COLUMNA DERECHA --}}
                    <div class="xl:col-span-1 flex flex-col gap-6">

                        {{-- Panel: Documentos requeridos --}}
                        <div class="panel">
                            <div class="panel-title flex items-center justify-between">
                                <div class="flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                    </svg>
                                    Documentación requerida
                                </div>
                                <button type="button" id="btn-new-documento" class="px-2 py-1 text-xs bg-blue-600 text-white rounded-md hover:bg-blue-700 transition">+ Agregar Tipo</button>
                            </div>
                            <div class="panel-body space-y-4">
                                {{-- Modal para crear nuevo tipo de documento --}}
                                <div id="modal-nuevo-documento" style="display: none;" class="bg-gray-50 p-4 rounded-lg border border-gray-200 space-y-3">
                                    <div class="font-semibold text-sm text-gray-700">Crear nuevo tipo de documento</div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-medium text-gray-600">Nombre del documento</label>
                                        <input id="new-doc-nombre" type="text" placeholder="Ej: Cédula de Identidad" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-medium text-gray-600">Tipo de archivos permitidos</label>
                                        <select id="new-doc-tipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                                            <option value="pdf">PDF</option>
                                            <option value="image">Imágenes (JPG, PNG, WebP)</option>
                                            <option value="word">Documentos Word (.docx)</option>
                                            <option value="excel">Hojas de Cálculo (.xlsx)</option>
                                            <option value="zip">Archivos comprimidos (.zip)</option>
                                            <option value="any">Cualquier tipo</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-medium text-gray-600">Peso máximo (MB) — 0 para sin límite</label>
                                        <input id="new-doc-peso" type="number" min="0" max="500" value="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-xs">
                                            <input id="new-doc-validar" type="checkbox" checked class="w-4 h-4">
                                            <span class="text-gray-600">Validar tipo de archivo</span>
                                        </label>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" id="btn-guardar-documento" class="flex-1 px-3 py-2 bg-green-600 text-white text-xs font-semibold rounded-lg hover:bg-green-700 transition">Guardar documento</button>
                                        <button type="button" id="btn-cancelar-documento" class="flex-1 px-3 py-2 bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-400 transition">Cancelar</button>
                                    </div>
                                    <div id="msg-nuevo-doc" class="text-xs hidden"></div>
                                </div>

                                {{-- Listado de documentos --}}
                                <div id="lista-documentos" class="text-xs text-gray-500 text-center py-4">
                                    <p>Cargando documentos...</p>
                                </div>
                            </div>
                        </div>

                        {{-- Panel: Imagen representativa --}}
                        <div class="panel">
                            <div class="panel-title">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                                </svg>
                                Imagen representativa
                            </div>
                            <div class="panel-body space-y-3">
                                <div id="img-preview-wrap" onclick="document.getElementById('foto_ruta').click()" style="cursor: pointer; border: 2px dashed #d1d5db; border-radius: 10px; Python: 3rem;  text-align: center;">
                                    @if($isEditing && $apoyo->foto_ruta)
                                        <img id="img-preview" src="{{ $apoyo->foto_url ?? asset($apoyo->foto_ruta) }}" alt="{{ $apoyo->nombre_apoyo }}" style="display:block; max-width: 100%; border-radius: 8px;">
                                    @else
                                        <img id="img-preview" src="" alt="" style="display:none; max-width: 100%; border-radius: 8px;">
                                    @endif
                                    <div class="placeholder" id="img-placeholder" style="{{ ($isEditing && $apoyo->foto_ruta) ? 'display:none' : '' }}; padding: 2rem;">
                                        <svg class="w-10 h-10 mx-auto mb-2 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0l3 3m-3-3l-3 3M6.75 19.5a4.5 4.5 0 01-1.41-8.775 5.25 5.25 0 0110.338-2.32 5.75 5.75 0 011.348 11.095H6.75z"/>
                                        </svg>
                                        <p class="text-xs font-semibold">Haz clic para cargar imagen</p>
                                        <p class="text-xs opacity-60 mt-0.5">JPG, PNG, WebP — máx. 5 MB</p>
                                    </div>
                                </div>
                                <input id="foto_ruta" name="foto_ruta" type="file" accept="image/jpeg,image/png,image/webp" class="hidden">
                                <div id="img-name" class="text-xs text-gray-400 text-center hidden"></div>
                                <button type="button" id="btn-remove-img" class="hidden w-full text-xs text-red-500 font-semibold hover:text-red-700 transition">
                                    Remover imagen
                                </button>
                            </div>
                        </div>

                    </div>
                </div>

                {{-- Submit bar --}}
                <div class="submit-bar">
                    <a href="{{ route('apoyos.index') }}" class="btn-secondary">Cancelar</a>
                    <button type="submit" class="btn-primary">{{ $isCreating ? 'Crear apoyo' : 'Guardar cambios' }}</button>
                </div>
            </form>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script src="https://cdn.quilljs.com/1.3.7/quill.min.js"></script>

    <script>
        (function() {
            console.log("✅ Script iniciado - IIFE ejecutada");
            
            // Flatpickr initialization
            try {
                flatpickr('.flatpickr', {
                    dateFormat: 'd/m/Y',
                    locale: 'es',
                    minDate: 'today'
                });
                console.log("✅ Flatpickr inicializado");
            } catch (e) {
                console.error("❌ Error en Flatpickr:", e);
            }

            // Quill editor
            try {
                const quill = new Quill('#quill-editor', {
                    theme: 'snow',
                    placeholder: 'Describe el apoyo y sus alcances...',
                    modules: {
                        toolbar: [['bold', 'italic', 'underline'], ['list', 'blockquote'], ['link', 'image'], ['clean']]
                    }
                });
                console.log("✅ Quill inicializado");

                // Sincronizar contenido del editor
                document.getElementById('formularioApoyo').addEventListener('submit', function () {
                    document.getElementById('descripcion-hidden').value = quill.root.innerHTML;
                });
            } catch (e) {
                console.error("❌ Error en Quill:", e);
            }

            // Cambio de tipo de apoyo
            try {
                document.getElementById('tipo_apoyo').addEventListener('change', function () {
                    const isEconomico = this.value === 'Económico';
                    document.getElementById('grp-economico').classList.toggle('hidden', !isEconomico);
                    document.getElementById('grp-especie').classList.toggle('hidden', isEconomico);
                    document.getElementById('panel-fin-title').textContent = isEconomico ? 'Financiamiento' : 'Inventario';
                    document.getElementById('lbl-presupuesto-seccion').textContent = isEconomico ? 'Presupuesto' : 'Gestión de Inventario';
                    document.getElementById('badge-tipo').textContent = this.value;
                });
                console.log("✅ Tipo apoyo listener agregado");
            } catch (e) {
                console.error("❌ Error en tipo_apoyo:", e);
            }

            // Observar disponibilidad de presupuesto
            document.getElementById('id_categoria')?.addEventListener('change', function () {
                const option = this.options[this.selectedIndex];
                const disponible = option.dataset.disponible || 0;
                document.getElementById('presupuesto-info').textContent = `Presupuesto disponible: $${parseFloat(disponible).toLocaleString('es-MX', {minimumFractionDigits: 2})}`;
            });

            // Image preview
            document.getElementById('foto_ruta')?.addEventListener('change', function (e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (event) {
                        document.getElementById('img-preview').src = event.target.result;
                        document.getElementById('img-preview').style.display = 'block';
                        document.getElementById('img-placeholder').style.display = 'none';
                        document.getElementById('img-name').textContent = file.name;
                        document.getElementById('img-name').classList.remove('hidden');
                        document.getElementById('btn-remove-img').classList.remove('hidden');
                    };
                    reader.readAsDataURL(file);
                }
            });

            document.getElementById('btn-remove-img')?.addEventListener('click', function (e) {
                e.preventDefault();
                document.getElementById('foto_ruta').value = '';
                document.getElementById('img-preview').src = '';
                document.getElementById('img-preview').style.display = 'none';
                document.getElementById('img-placeholder').style.display = 'block';
                document.getElementById('img-name').classList.add('hidden');
                this.classList.add('hidden');
            });

            // Hito management
            let hitosCount = {{ $baseMilestonesCollection->count() + $customMilestones->count() }};
            document.getElementById('btn-add-hito')?.addEventListener('click', function () {
                const grid = document.getElementById('hitos-custom-grid');
                const newHito = document.createElement('div');
                newHito.className = 'rounded-xl border border-slate-200 p-3 bg-white grid grid-cols-1 sm:grid-cols-3 gap-3';
                newHito.innerHTML = `
                    <input type="hidden" name="hitos[${hitosCount}][incluir]" value="1">
                    <input type="hidden" name="hitos[${hitosCount}][es_base]" value="0">
                    <div class="sm:col-span-3 flex items-center justify-between gap-2">
                        <label class="field-label !mb-0">Hito adicional</label>
                        <button type="button" class="text-xs font-bold text-red-600 hover:text-red-700" data-remove-hito="1">Eliminar</button>
                    </div>
                    <div class="sm:col-span-3">
                        <input type="text" name="hitos[${hitosCount}][titulo]" class="field-input" placeholder="Ej: Evaluación de solicitudes" required>
                    </div>
                    <div>
                        <label class="field-label">Inicio</label>
                        <input type="date" name="hitos[${hitosCount}][fecha_inicio]" class="field-input">
                    </div>
                    <div>
                        <label class="inline-flex items-center gap-2 text-xs font-semibold text-slate-600 mb-2">
                            <input type="checkbox" name="hitos[${hitosCount}][tiene_fin]" value="1" class="hito-toggle-fin w-4 h-4 accent-blue-700">
                            Tiene fin
                        </label>
                        <label class="field-label">Fin</label>
                        <input type="date" name="hitos[${hitosCount}][fecha_fin]" class="field-input">
                    </div>
                    <div class="flex items-end text-xs text-slate-500 font-semibold">Personalizado</div>
                `;
                grid.appendChild(newHito);
                hitosCount++;
            });

            // Delegate click for remove buttons
            document.addEventListener('click', function (e) {
                if (e.target.dataset.removeHito) {
                    e.preventDefault();
                    e.target.closest('[data-remove-hito]').parentElement.remove();
                }
            });

            console.log("✅ Event listeners agregados");



            // ===== CÁLCULO AUTOMÁTICO DE PRESUPUESTO REAL =====
            const selectCategoria = document.getElementById('id_categoria');
            const inputMontoMaximo = document.getElementById('monto_maximo');
            const inputCupoLimite = document.getElementById('cupo_limite');
            const inputMontoInicial = document.getElementById('monto_inicial_asignado');
            const sectionPresupuestoReal = document.getElementById('presupuesto-real-section');
            const divMontoInicial = document.getElementById('div-monto-inicial');
            
            const txtPresupuestoDisponible = document.getElementById('txt-presupuesto-disponible');
            const txtMontoTotal = document.getElementById('txt-total-calculado');
            const txtMontoBeneficiario = document.getElementById('txt-monto-beneficiario');
            const txtCantidadBeneficiarios = document.getElementById('txt-cantidad-beneficiarios');
            const divValidacionPresupuesto = document.getElementById('div-validacion-presupuesto');
            const divResumenFinal = document.getElementById('div-resumen-final');
            const txtPresupuestoFaltante = document.getElementById('txt-presupuesto-faltante');
            const txtPresupuestoRestante = document.getElementById('txt-presupuesto-restante');

            console.log('✅ Presupuesto setup listo');

            // Limpiar valores iniciales malformados
            if (inputMontoMaximo && inputMontoMaximo.value) {
                const cleanMonto = parseFloat(inputMontoMaximo.value);
                if (cleanMonto > 0) {
                    inputMontoMaximo.value = cleanMonto;
                }
            }
            if (inputCupoLimite && inputCupoLimite.value) {
                const cleanCupo = parseInt(inputCupoLimite.value, 10);
                if (cleanCupo > 0) {
                    inputCupoLimite.value = cleanCupo;
                }
            }

            // ===== FUNCIÓN PARA FORMATEAR DINERO CON COMAS =====
            function formatCurrency(value) {
                // Convertir a número
                const num = parseFloat(value) || 0;
                // Formato: 1,000,000.00
                return num.toLocaleString('es-MX', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                });
            }

            function actualizarCalculoPresupuesto() {
                const tipoApoyo = document.getElementById('tipo_apoyo')?.value || 'Económico';
                
                // Solo mostrar en modo Económico
                if (tipoApoyo !== 'Económico') {
                    sectionPresupuestoReal.classList.add('hidden');
                    divMontoInicial.classList.add('hidden');
                    return;
                }

                const selectedOption = selectCategoria.options[selectCategoria.selectedIndex];
                const presupuestoDisponible = parseFloat(selectedOption.dataset.disponible) || 0;
                
                // Parsear valores de inputs - usar 0/1 como fallback
                const montoStr = (inputMontoMaximo.value || '').trim();
                const cupoStr = (inputCupoLimite.value || '').trim();
                
                // Si está vacío o es inválido, usar 0 para monto y 1 para cupo
                const montoMaximo = montoStr ? parseFloat(montoStr) : 0;
                const cupoLimite = cupoStr ? parseInt(cupoStr) : 1;
                
                // Usar 0 si es NaN
                const montoFinal = isNaN(montoMaximo) ? 0 : montoMaximo;
                const cupoFinal = isNaN(cupoLimite) ? 1 : cupoLimite;
                
                const totalCalculado = montoFinal * cupoFinal;

                // Actualizar campo monto_inicial_asignado automáticamente (valor crudo para guardar)
                if (inputMontoInicial) {
                    inputMontoInicial.value = totalCalculado.toFixed(2);
                }

                // Actualizar valores mostrados CON FORMATO DINERO
                if (txtPresupuestoDisponible) {
                    txtPresupuestoDisponible.textContent = formatCurrency(presupuestoDisponible);
                }
                if (txtMontoBeneficiario) {
                    txtMontoBeneficiario.textContent = formatCurrency(montoFinal);
                }
                if (txtCantidadBeneficiarios) {
                    txtCantidadBeneficiarios.textContent = Math.floor(cupoFinal);
                }
                if (txtMontoTotal) {
                    txtMontoTotal.textContent = formatCurrency(totalCalculado);
                }

                // Mostrar sección SIEMPRE (no depende de categoría)
                sectionPresupuestoReal.classList.remove('hidden');
                divMontoInicial.classList.remove('hidden');

                // Validar presupuesto SOLO si hay categoría seleccionada
                if (selectedOption.value && presupuestoDisponible > 0) {
                    // Validar presupuesto
                    if (totalCalculado > presupuestoDisponible) {
                        // Mostrar alerta
                        divValidacionPresupuesto.classList.remove('hidden');
                        divResumenFinal.classList.add('hidden');
                        const faltante = totalCalculado - presupuestoDisponible;
                        if (txtPresupuestoFaltante) {
                            txtPresupuestoFaltante.textContent = formatCurrency(faltante);
                        }
                        
                        // Marcar input como inválido
                        inputMontoMaximo.classList.add('border-red-500', 'bg-red-50');
                        inputCupoLimite.classList.add('border-red-500', 'bg-red-50');
                    } else if (totalCalculado > 0) {
                        // Mostrar resumen válido
                        divValidacionPresupuesto.classList.add('hidden');
                        divResumenFinal.classList.remove('hidden');
                        const restante = presupuestoDisponible - totalCalculado;
                        if (txtPresupuestoRestante) {
                            txtPresupuestoRestante.textContent = formatCurrency(restante);
                        }
                        
                        // Quitar marcado de inválido
                        inputMontoMaximo.classList.remove('border-red-500', 'bg-red-50');
                        inputCupoLimite.classList.remove('border-red-500', 'bg-red-50');
                    } else {
                        // Total es cero
                        divValidacionPresupuesto.classList.add('hidden');
                        divResumenFinal.classList.add('hidden');
                        inputMontoMaximo.classList.remove('border-red-500', 'bg-red-50');
                        inputCupoLimite.classList.remove('border-red-500', 'bg-red-50');
                    }
                } else {
                    // Sin categoría seleccionada - ocultar validación, solo mostrar cálculo
                    divValidacionPresupuesto.classList.add('hidden');
                    divResumenFinal.classList.add('hidden');
                    inputMontoMaximo.classList.remove('border-red-500', 'bg-red-50');
                    inputCupoLimite.classList.remove('border-red-500', 'bg-red-50');
                }
            }

            // Event listeners para actualizar cálculo
            if (selectCategoria) {
                selectCategoria.addEventListener('change', function() {
                    actualizarCalculoPresupuesto();
                });
            }
            if (inputMontoMaximo) {
                // Limpiar leading zeros mientras se escribe
                inputMontoMaximo.addEventListener('input', function() {
                    // Primero, limpiar cualquier cero adelante
                    if (this.value && this.value.startsWith('0') && this.value.length > 1 && this.value[1] !== '.') {
                        // Si empieza con 0 y hay más dígitos (y no es 0.xx), remover el 0
                        let cleaned = this.value.replace(/^0+/, '');
                        if (!cleaned || cleaned[0] === '.') {
                            cleaned = '0' + cleaned;
                        }
                        this.value = cleaned;
                    }
                    actualizarCalculoPresupuesto();
                });
                inputMontoMaximo.addEventListener('change', function() {
                    actualizarCalculoPresupuesto();
                });
            }
            if (inputCupoLimite) {
                // Limpiar leading zeros mientras se escribe
                inputCupoLimite.addEventListener('input', function() {
                    // Remover leading zeros para números enteros
                    if (this.value && this.value.startsWith('0') && this.value.length > 1) {
                        this.value = this.value.replace(/^0+/, '');
                        if (!this.value) this.value = '1';
                    }
                    actualizarCalculoPresupuesto();
                });
                inputCupoLimite.addEventListener('change', function() {
                    actualizarCalculoPresupuesto();
                });
            }

            // Event listener para tipo_apoyo para ocultar sección en Especie
            const selectTipoApoyo = document.getElementById('tipo_apoyo');
            if (selectTipoApoyo) {
                selectTipoApoyo.addEventListener('change', actualizarCalculoPresupuesto);
            }

            // Ejecutar cálculo inicial
            actualizarCalculoPresupuesto();

        })(); // Cierre de IIFE
    </script>

    {{-- Script para gestionar tipos de documentos --}}
    <script>
        (function() {
            // Elementos del DOM
            const btnNewDocumento = document.getElementById('btn-new-documento');
            const modalNuevoDocumento = document.getElementById('modal-nuevo-documento');
            const btnGuardarDocumento = document.getElementById('btn-guardar-documento');
            const btnCancelarDocumento = document.getElementById('btn-cancelar-documento');
            const newDocNombre = document.getElementById('new-doc-nombre');
            const newDocTipo = document.getElementById('new-doc-tipo');
            const newDocPeso = document.getElementById('new-doc-peso');
            const newDocValidar = document.getElementById('new-doc-validar');
            const msgNuevoDoc = document.getElementById('msg-nuevo-doc');
            const listaDocumentos = document.getElementById('lista-documentos');

            // Mostrar/ocultar modal
            btnNewDocumento?.addEventListener('click', () => {
                modalNuevoDocumento.style.display = 'block';
                newDocNombre.focus();
                newDocNombre.value = '';
                newDocTipo.value = 'pdf';
                newDocPeso.value = 5;
                newDocValidar.checked = true;
                msgNuevoDoc.textContent = '';
                msgNuevoDoc.className = 'text-xs hidden';
            });

            btnCancelarDocumento?.addEventListener('click', () => {
                modalNuevoDocumento.style.display = 'none';
            });

            // Guardar nuevo documento
            btnGuardarDocumento?.addEventListener('click', async () => {
                const nombre = newDocNombre.value.trim();
                if (!nombre) {
                    mostrarMensaje('Please enter a document name', 'error');
                    return;
                }

                btnGuardarDocumento.disabled = true;
                btnGuardarDocumento.textContent = 'Guardando...';

                try {
                    const response = await fetch('/apoyos/documentos', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                        },
                        body: JSON.stringify({
                            nombre_documento: nombre,
                            tipo_archivo_permitido: newDocTipo.value,
                            peso_maximo_mb: parseInt(newDocPeso.value) || 5,
                            validar_tipo_archivo: newDocValidar.checked
                        })
                    });

                    const data = await response.json();

                    if (data.success) {
                        mostrarMensaje('✅ Document created successfully!', 'success');
                        setTimeout(() => {
                            modalNuevoDocumento.style.display = 'none';
                            recargarDocumentos();
                        }, 1200);
                    } else {
                        mostrarMensaje(data.message || 'Error creating document', 'error');
                    }
                } catch (error) {
                    mostrarMensaje('Network error: ' + error.message, 'error');
                } finally {
                    btnGuardarDocumento.disabled = false;
                    btnGuardarDocumento.textContent = 'Guardar documento';
                }
            });

            // Recargar lista de documentos
            async function recargarDocumentos() {
                try {
                    const response = await fetch('/apoyos/documentos', {
                        headers: {
                            'Accept': 'application/json'
                        }
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP Error: ${response.status} ${response.statusText}`);
                    }

                    const data = await response.json();
                    
                    if (data.success && data.datos && Array.isArray(data.datos)) {
                        actualizarListaDocumentos(data.datos);
                    } else if (data.datos && Array.isArray(data.datos)) {
                        // Si viene datos pero sin success flag, igual lo procesamos
                        actualizarListaDocumentos(data.datos);
                    } else {
                        console.warn('Unexpected response format:', data);
                        listaDocumentos.innerHTML = '<p class="text-xs text-red-500 text-center py-4">Error al cargar documentos. Respuesta inesperada.</p>';
                    }
                } catch (error) {
                    console.error('Error loading documents:', error);
                    listaDocumentos.innerHTML = `<p class="text-xs text-red-500 text-center py-4">Error: ${error.message}</p>`;
                }
            }

            // Actualizar lista en la UI
            function actualizarListaDocumentos(documentos) {
                let html = '';
                if (documentos.length === 0) {
                    html = '<p class="text-xs text-gray-500 text-center py-4">Sin documentos configurados.</p>';
                } else {
                    html = '<div class="grid grid-cols-1 gap-2">';
                    documentos.forEach(doc => {
                        html += `
                            <label class="flex items-center gap-3 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition cursor-pointer">
                                <input type="checkbox" name="documentos_requeridos[]" value="${doc.id_tipo_doc}" class="w-4 h-4 accent-blue-700">
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-800">${doc.nombre_documento}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        ${doc.tipo_archivo_permitido ? doc.tipo_archivo_permitido.charAt(0).toUpperCase() + doc.tipo_archivo_permitido.slice(1) : 'Cualquier tipo'} 
                                        ${doc.peso_maximo_mb ? `• Máx: ${doc.peso_maximo_mb} MB` : '• Sin límite de peso'}
                                    </div>
                                </div>
                            </label>
                        `;
                    });
                    html += '</div>';
                }
                listaDocumentos.innerHTML = html;
            }

            // Mostrar mensajes
            function mostrarMensaje(texto, tipo) {
                msgNuevoDoc.textContent = texto;
                msgNuevoDoc.className = `text-xs ${tipo === 'success' ? 'text-green-600' : 'text-red-600'}`;
            }

            // Cargar documentos cuando la página se carga
            recargarDocumentos();
        })();
    </script>

</body>
</html>
