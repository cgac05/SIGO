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

            {{-- SECCIÓN DE MENSAJES --}}
            <div id="messagesContainer" class="max-w-screen-xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                {{-- Errores de validación --}}
                <div id="errorsAlert" class="hidden bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-semibold text-red-900">Por favor, corrija los siguientes errores:</h3>
                            <ul id="errorsList" class="mt-2 space-y-1 list-disc list-inside text-sm text-red-700"></ul>
                        </div>
                    </div>
                </div>

                {{-- Mensaje de éxito --}}
                <div id="successAlert" class="hidden bg-green-50 border border-green-200 rounded-lg p-4 mb-4">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-green-600 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <div class="flex-1">
                            <h3 class="font-semibold text-green-900" id="successMessage">✅ Apoyo creado exitosamente</h3>
                        </div>
                    </div>
                </div>
            </div>

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
                                            <input id="unidad_medida" name="unidad_medida" type="text" class="field-input" placeholder="Ej: pieza, kit, paquete" value="{{ old('unidad_medida', $unidadMedida ?? 'pieza') }}">
                                        </div>

                                        <div>
                                            <label class="field-label" for="costo_unitario">Costo unitario estimado</label>
                                            <div class="prefix-wrap">
                                                <span class="prefix">$</span>
                                                <input id="costo_unitario" name="costo_unitario" type="number" class="field-input" step="0.01" min="0" placeholder="0.00" value="{{ old('costo_unitario', $costoUnitario ?? 0) }}">
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
                                                {{-- Ocultar campos de fecha para inicio_publicacion y proceso_cerrado --}}
                                                {{-- Las fechas se sincronizan automáticamente desde fecha_inicio/fin del apoyo --}}
                                                @if(!$isMandatoryBase)
                                                    <div class="sm:col-span-1" data-hito-content="1">
                                                        <label class="field-label">Fecha</label>
                                                        <input type="date" name="hitos[{{ $i }}][fecha_inicio]" class="field-input hito-fecha-inicio" value="{{ old('hitos.' . $i . '.fecha_inicio', !empty($saved?->fecha_inicio) ? Carbon::parse($saved->fecha_inicio)->toDateString() : '') }}">
                                                    </div>
                                                @else
                                                    {{-- Para iniciopublicacion y proceso_cerrado: campo oculto, se sincroniza automáticamente --}}
                                                    <input type="hidden" name="hitos[{{ $i }}][fecha_inicio]" class="hito-fecha-inicio-hidden" value="">
                                                    <input type="hidden" name="hitos[{{ $i }}][fecha_fin]" class="hito-fecha-fin-hidden" value="">
                                                    <div class="sm:col-span-2 p-2 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700">
                                                        <strong>ℹ️ Sincronización automática:</strong> Esta fecha se sincroniza con el período de vigencia del apoyo
                                                    </div>
                                                @endif
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

                                {{-- Modal para editar tipo de documento --}}
                                <div id="modalEditarDocumento" style="display: none;" class="bg-gray-50 p-4 rounded-lg border border-blue-200 space-y-3">
                                    <input id="editDocId" type="hidden">
                                    <div class="font-semibold text-sm text-gray-700">Editar: <span id="editDocName">Documento</span></div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-medium text-gray-600">Tipo de archivos permitidos</label>
                                        <select id="editDocTipo" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500">
                                            <option value="pdf">PDF</option>
                                            <option value="image">Imágenes (JPG, PNG, WebP)</option>
                                            <option value="word">Documentos Word (.docx)</option>
                                            <option value="excel">Hojas de Cálculo (.xlsx)</option>
                                            <option value="zip">Archivos comprimidos (.zip)</option>
                                            <option value="any">Cualquier tipo</option>
                                        </select>
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-xs font-medium text-gray-600">Peso máximo (MB)</label>
                                        <input id="editDocPeso" type="number" min="1" max="500" value="5" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:ring-1 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="flex items-center gap-2 text-xs">
                                            <input id="editDocValidar" type="checkbox" class="w-4 h-4">
                                            <span class="text-gray-600">Validar tipo de archivo</span>
                                        </label>
                                    </div>
                                    <div class="flex gap-2">
                                        <button type="button" id="btn-guardar-edicion" class="flex-1 px-3 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition">Guardar cambios</button>
                                        <button type="button" id="btn-cancelar-edicion" class="flex-1 px-3 py-2 bg-gray-300 text-gray-700 text-xs font-semibold rounded-lg hover:bg-gray-400 transition">Cancelar</button>
                                    </div>
                                    <div id="msg-edicion-doc" class="text-xs"></div>
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

                        {{-- Panel: Validación / Checklist --}}
                        <div class="panel">
                            <div class="panel-title">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z"/>
                                </svg>
                                Checklist de completitud
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
                function updateEspecieLogic() {
                    const tipo = document.getElementById('tipo_apoyo').value;
                    const isEconomico = tipo === 'Económico';
                    const isEspecie = tipo === 'Especie';
                    
                    document.getElementById('grp-economico').classList.toggle('hidden', !isEconomico);
                    document.getElementById('grp-especie').classList.toggle('hidden', !isEspecie);
                    document.getElementById('panel-fin-title').textContent = isEconomico ? 'Financiamiento' : 'Inventario';
                    document.getElementById('lbl-presupuesto-seccion').textContent = isEconomico ? 'Presupuesto' : 'Gestión de Inventario';
                    document.getElementById('badge-tipo').textContent = tipo;
                    
                    const cupoLimiteInput = document.getElementById('cupo_limite');
                    const montoMaximoInput = document.getElementById('monto_maximo');
                    
                    if (isEspecie) {
                        cupoLimiteInput.readOnly = true;
                        cupoLimiteInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                        montoMaximoInput.readOnly = true;
                        montoMaximoInput.classList.add('bg-gray-100', 'cursor-not-allowed');
                    } else {
                        cupoLimiteInput.readOnly = false;
                        cupoLimiteInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                        montoMaximoInput.readOnly = false;
                        montoMaximoInput.classList.remove('bg-gray-100', 'cursor-not-allowed');
                    }
                }

                document.getElementById('tipo_apoyo').addEventListener('change', updateEspecieLogic);
                updateEspecieLogic(); // Llamar al cargar la página
                
                // Lógica de autocompletado para Especie
                const stockInicialInput = document.getElementById('stock_inicial');
                const costoUnitarioInput = document.getElementById('costo_unitario');
                const cupoLimiteInput = document.getElementById('cupo_limite');
                const montoMaximoInput = document.getElementById('monto_maximo');

                function syncEspecieFields() {
                    if (document.getElementById('tipo_apoyo').value === 'Especie') {
                        const stock = parseInt(stockInicialInput.value) || 0;
                        const costo = parseFloat(costoUnitarioInput.value) || 0;
                        
                        // 1 beneficiario = 1 pieza
                        cupoLimiteInput.value = stock;
                        
                        // Monto máximo = Total del apoyo (stock * costo)
                        montoMaximoInput.value = (stock * costo).toFixed(2);
                        
                        // Disparar evento change para que se actualice el "Cálculo Automático" del layout general
                        montoMaximoInput.dispatchEvent(new Event('input'));
                        cupoLimiteInput.dispatchEvent(new Event('input'));
                    }
                }

                if (stockInicialInput) stockInicialInput.addEventListener('input', syncEspecieFields);
                if (costoUnitarioInput) costoUnitarioInput.addEventListener('input', syncEspecieFields);

                console.log("✅ Tipo apoyo listener y sincronización de Especie agregados");
            } catch (e) {
                console.error("❌ Error en lógica de tipo_apoyo o sincronización Especie:", e);
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
                selectTipoApoyo.addEventListener('change', updateChecklist);
            }

            // Ejecutar cálculo inicial
            actualizarCalculoPresupuesto();

            /* ── HELPERS ──────────────────────────────────────── */
            function fmt(n) {
                return '$' + parseFloat(n || 0).toLocaleString('es-MX', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            function setChk(el, ok, na = false) {
                const dot = el.querySelector('.chk-icon');
                dot.className = 'chk-icon w-4 h-4 rounded-full flex-shrink-0 ' +
                    (na ? 'bg-gray-200' : ok ? 'bg-green-400' : 'bg-red-400');
            }

            /* ── CHECKLIST / VALIDACIÓN ──────────────────────────── */
            function updateChecklist() {
                const tipo   = (document.getElementById('tipo_apoyo')?.value || 'Económico');
                const isEco  = tipo === 'Económico';
                const nombre = document.getElementById('nombre_apoyo')?.value.trim() || '';
                const inicio = document.getElementById('fechaInicio')?.value || '';
                const fin    = document.getElementById('fechafin')?.value || '';
                const monto  = parseFloat(document.getElementById('monto_maximo')?.value) || 0;
                const cupo   = parseInt(document.getElementById('cupo_limite')?.value, 10);
                const presup = isEco ? (parseFloat(document.getElementById('monto_inicial_asignado')?.value) || 0)
                                     : (parseInt(document.getElementById('stock_inicial')?.value, 10) || 0);

                // Get checklist elements
                const chkNombre = document.getElementById('chk-nombre');
                const chkFechas = document.getElementById('chk-fechas');
                const chkMonto  = document.getElementById('chk-monto');
                const chkCupo   = document.getElementById('chk-cupo');
                const chkPresupuesto = document.getElementById('chk-presupuesto');

                // Update labels dinámicamente
                const lblMonto = document.getElementById('chk-monto-lbl');
                if (lblMonto) lblMonto.textContent = isEco ? 'Monto máximo' : 'Precio unitario';
                const lblPresupuesto = document.getElementById('chk-presupuesto-lbl');
                if (lblPresupuesto) lblPresupuesto.textContent = isEco ? 'Presupuesto asignado' : 'Stock inicial';

                // Parse dates in dd/mm/yyyy format to compare correctly
                function parseDateMX(dStr) {
                    if (!dStr) return 0;
                    const parts = dStr.split('/');
                    if (parts.length === 3) return new Date(`${parts[2]}-${parts[1]}-${parts[0]}T00:00:00`).getTime();
                    return new Date(dStr).getTime();
                }

                // Update checklist
                setChk(chkNombre, nombre.length > 0);
                setChk(chkFechas, inicio && fin && (parseDateMX(fin) >= parseDateMX(inicio)));
                setChk(chkMonto,  isEco ? (monto > 0) : true, false);
                setChk(chkCupo,   !isNaN(cupo) && cupo > 0);
                setChk(chkPresupuesto, presup > 0);
            }

            // Event listeners para actualizar checklist
            const updateChecklistFields = [
                'nombre_apoyo', 'tipo_apoyo', 'fechaInicio', 'fechafin',
                'monto_maximo', 'cupo_limite', 'monto_inicial_asignado', 'stock_inicial'
            ];

            updateChecklistFields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('input', updateChecklist);
                    field.addEventListener('change', updateChecklist);
                }
            });

            // Ejecutar checklist inicial
            updateChecklist();

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

                    // Check if response is not OK (e.g., 403, 500)
                    if (!response.ok) {
                        const contentType = response.headers.get('content-type');
                        let errorMsg = `Server error: ${response.status} ${response.statusText}`;
                        
                        // Try to extract error message from HTML or JSON
                        try {
                            const text = await response.text();
                            if (contentType && contentType.includes('application/json')) {
                                const errorData = JSON.parse(text);
                                errorMsg = errorData.message || errorMsg;
                            } else if (text.includes('No cuentas con permisos')) {
                                errorMsg = 'You do not have permission to manage documents';
                            } else {
                                errorMsg = 'Server error: Unable to process request';
                            }
                        } catch (e) {
                            // Use default error message
                        }
                        
                        mostrarMensaje(errorMsg, 'error');
                        btnGuardarDocumento.disabled = false;
                        btnGuardarDocumento.textContent = 'Guardar documento';
                        return;
                    }

                    // Try to parse JSON response
                    let data;
                    try {
                        data = await response.json();
                    } catch (e) {
                        mostrarMensaje('Invalid server response format', 'error');
                        btnGuardarDocumento.disabled = false;
                        btnGuardarDocumento.textContent = 'Guardar documento';
                        return;
                    }

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
            const requisitosActuales = @json($requisitosActuales ?? []);

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
                    html = '<div class="space-y-2">';
                    documentos.forEach(doc => {
                        const isChecked = requisitosActuales.includes(doc.id_tipo_doc) ? 'checked' : '';
                        html += `
                            <div class="flex items-center gap-2 p-3 rounded-lg border border-gray-200 hover:border-blue-300 hover:bg-blue-50 transition">
                                <input type="checkbox" name="documentos_requeridos[]" value="${doc.id_tipo_doc}" class="w-4 h-4 accent-blue-700" ${isChecked}>
                                <div class="flex-1 min-w-0">
                                    <div class="text-xs font-medium text-gray-800">${doc.nombre_documento}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">
                                        ${doc.tipo_archivo_permitido ? doc.tipo_archivo_permitido.charAt(0).toUpperCase() + doc.tipo_archivo_permitido.slice(1) : 'Cualquier tipo'} 
                                        ${doc.peso_maximo_mb ? `• Máx: ${doc.peso_maximo_mb} MB` : '• Sin límite de peso'}
                                    </div>
                                </div>
                                <button type="button" class="btn-editar-doc px-2 py-1 text-xs bg-blue-100 text-blue-700 rounded hover:bg-blue-200 transition" data-doc-id="${doc.id_tipo_doc}" data-doc-name="${doc.nombre_documento}" data-doc-type="${doc.tipo_archivo_permitido || 'any'}" data-doc-validate="${doc.validar_tipo_archivo ? 1 : 0}" data-doc-peso="${doc.peso_maximo_mb || 5}">
                                    Editar
                                </button>
                                <button type="button" class="btn-eliminar-doc px-2 py-1 text-xs bg-red-100 text-red-700 rounded hover:bg-red-200 transition" data-doc-id="${doc.id_tipo_doc}" data-doc-name="${doc.nombre_documento}">
                                    Eliminar
                                </button>
                            </div>
                        `;
                    });
                    html += '</div>';
                }
                listaDocumentos.innerHTML = html;

                // Agregar event listeners a los botones
                document.querySelectorAll('.btn-editar-doc').forEach(btn => {
                    btn.addEventListener('click', (e) => {
                        e.preventDefault();
                        const docId = btn.getAttribute('data-doc-id');
                        const docName = btn.getAttribute('data-doc-name');
                        const docType = btn.getAttribute('data-doc-type');
                        const docValidate = btn.getAttribute('data-doc-validate') === '1';
                        const docPeso = btn.getAttribute('data-doc-peso');
                        
                        // Llenar el modal de edición
                        document.getElementById('editDocId').value = docId;
                        document.getElementById('editDocName').textContent = docName;
                        document.getElementById('editDocTipo').value = docType;
                        document.getElementById('editDocValidar').checked = docValidate;
                        document.getElementById('editDocPeso').value = docPeso;
                        document.getElementById('modalEditarDocumento').style.display = 'block';
                    });
                });

                document.querySelectorAll('.btn-eliminar-doc').forEach(btn => {
                    btn.addEventListener('click', async (e) => {
                        e.preventDefault();
                        const docId = btn.getAttribute('data-doc-id');
                        const docName = btn.getAttribute('data-doc-name');
                        
                        if (confirm(`¿Estás seguro de que deseas eliminar el tipo de documento "${docName}"?`)) {
                            try {
                                const response = await fetch(`/apoyos/documentos/${docId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                    }
                                });

                                if (!response.ok) {
                                    const contentType = response.headers.get('content-type');
                                    let errorMsg = `Error: ${response.status}`;
                                    try {
                                        const text = await response.text();
                                        if (contentType && contentType.includes('application/json')) {
                                            const errorData = JSON.parse(text);
                                            errorMsg = errorData.message || errorMsg;
                                        }
                                    } catch (e) {}
                                    mostrarMensaje(errorMsg, 'error');
                                    return;
                                }

                                const data = await response.json();
                                if (data.success) {
                                    mostrarMensaje(`✅ "${docName}" eliminado correctamente`, 'success');
                                    setTimeout(() => recargarDocumentos(), 800);
                                } else {
                                    mostrarMensaje(data.message || 'Error al eliminar', 'error');
                                }
                            } catch (error) {
                                mostrarMensaje('Error: ' + error.message, 'error');
                            }
                        }
                    });
                });
            }

            // Event listeners para el modal de edición
            const btnCancelarEdicion = document.getElementById('btn-cancelar-edicion');
            const btnGuardarEdicion = document.getElementById('btn-guardar-edicion');
            const msgEdicionDoc = document.getElementById('msg-edicion-doc');
            
            if (btnCancelarEdicion) {
                btnCancelarEdicion.addEventListener('click', () => {
                    document.getElementById('modalEditarDocumento').style.display = 'none';
                });
            }

            if (btnGuardarEdicion) {
                btnGuardarEdicion.addEventListener('click', async () => {
                    const docId = document.getElementById('editDocId').value;
                    const docTipo = document.getElementById('editDocTipo').value;
                    const docPeso = parseInt(document.getElementById('editDocPeso').value) || 0;
                    const docValidar = document.getElementById('editDocValidar').checked;

                    // Validar campos
                    if (!docId || !docTipo || docPeso < 1 || docPeso > 500) {
                        if (msgEdicionDoc) msgEdicionDoc.textContent = '⚠️ Valores inválidos. Peso debe ser entre 1 y 500 MB';
                        if (msgEdicionDoc) msgEdicionDoc.className = 'text-xs text-red-600 mt-2';
                        return;
                    }

                    btnGuardarEdicion.disabled = true;
                    btnGuardarEdicion.textContent = 'Guardando...';
                    if (msgEdicionDoc) msgEdicionDoc.textContent = '';

                    try {
                        const response = await fetch(`/apoyos/documentos/${docId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                            },
                            body: JSON.stringify({
                                tipo_archivo_permitido: docTipo,
                                peso_maximo_mb: docPeso,
                                validar_tipo_archivo: docValidar ? 1 : 0
                            })
                        });

                        if (!response.ok) {
                            const contentType = response.headers.get('content-type');
                            let errorMsg = `Error: ${response.status}`;
                            try {
                                const text = await response.text();
                                if (contentType && contentType.includes('application/json')) {
                                    const errorData = JSON.parse(text);
                                    errorMsg = errorData.message || errorMsg;
                                }
                            } catch (e) {}
                            if (msgEdicionDoc) msgEdicionDoc.textContent = `❌ ${errorMsg}`;
                            if (msgEdicionDoc) msgEdicionDoc.className = 'text-xs text-red-600 mt-2';
                            return;
                        }

                        const data = await response.json();
                        if (data.success) {
                            if (msgEdicionDoc) msgEdicionDoc.textContent = '✅ Documento actualizado correctamente';
                            if (msgEdicionDoc) msgEdicionDoc.className = 'text-xs text-green-600 mt-2';
                            setTimeout(() => {
                                document.getElementById('modalEditarDocumento').style.display = 'none';
                                recargarDocumentos();
                            }, 1200);
                        } else {
                            if (msgEdicionDoc) msgEdicionDoc.textContent = `❌ ${data.message || 'Error al actualizar'}`;
                            if (msgEdicionDoc) msgEdicionDoc.className = 'text-xs text-red-600 mt-2';
                        }
                    } catch (error) {
                        if (msgEdicionDoc) msgEdicionDoc.textContent = `❌ Error: ${error.message}`;
                        if (msgEdicionDoc) msgEdicionDoc.className = 'text-xs text-red-600 mt-2';
                    } finally {
                        btnGuardarEdicion.disabled = false;
                        btnGuardarEdicion.textContent = 'Guardar cambios';
                    }
                });
            }

            // Mostrar mensajes
            function mostrarMensaje(texto, tipo) {
                msgNuevoDoc.textContent = texto;
                msgNuevoDoc.className = `text-xs ${tipo === 'success' ? 'text-green-600' : 'text-red-600'}`;
            }

            // Cargar documentos cuando la página se carga
            recargarDocumentos();

            // ==================== INTERCEPTAR SUBMIT DEL FORMULARIO ====================
            
            // Función para convertir fechas de d/m/Y a Y-m-d
            function convertDateFormat(dateStr) {
                if (!dateStr) return '';
                const [day, month, year] = dateStr.split('/');
                if (!day || !month || !year) return dateStr;
                return `${year}-${String(month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            }
            
            const formularioApoyo = document.getElementById('formularioApoyo');
            
            formularioApoyo.addEventListener('submit', async function(e) {
                e.preventDefault(); // Evitar envío tradicional
                console.log('✅ Submit interceptado');

                // Sincronizar contenido de Quill antes de enviar
                if (typeof quill !== 'undefined') {
                    document.getElementById('descripcion-hidden').value = quill.root.innerHTML;
                }

                // Ocultar mensajes anteriores
                document.getElementById('errorsAlert').classList.add('hidden');
                document.getElementById('successAlert').classList.add('hidden');
                document.getElementById('errorsList').innerHTML = '';

                // Crear FormData con los datos del formulario
                const formData = new FormData(this);
                
                // ⚠️ CONVERTIR FECHAS A FORMATO Y-m-d
                const fechaInicio = formData.get('fechaInicio');
                const fechafin = formData.get('fechafin');
                
                if (fechaInicio) {
                    formData.set('fechaInicio', convertDateFormat(fechaInicio));
                    console.log('📅 Fecha inicio convertida:', fechaInicio, '→', convertDateFormat(fechaInicio));
                }
                if (fechafin) {
                    formData.set('fechafin', convertDateFormat(fechafin));
                    console.log('📅 Fecha fin convertida:', fechafin, '→', convertDateFormat(fechafin));
                }

                try {
                    const response = await fetch(this.action, {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    console.log('📤 Respuesta recibida:', response.status, response.statusText);

                    const data = await response.json().catch(() => ({}));
                    console.log('📋 Datos de respuesta:', data);

                    // Manejar errores de validación (422)
                    if (response.status === 422) {
                        console.log('❌ Errores de validación:', data.errors);
                        
                        const errorsAlert = document.getElementById('errorsAlert');
                        const errorsList = document.getElementById('errorsList');
                        
                        // Limpiar lista anterior
                        errorsList.innerHTML = '';

                        // Agregar cada error a la lista
                        if (data.errors && typeof data.errors === 'object') {
                            Object.entries(data.errors).forEach(([field, messages]) => {
                                if (Array.isArray(messages)) {
                                    messages.forEach(message => {
                                        const li = document.createElement('li');
                                        li.textContent = message;
                                        errorsList.appendChild(li);
                                        
                                        // Marcar el campo con error
                                        const fieldElement = document.querySelector(`[name="${field}"]`);
                                        if (fieldElement) {
                                            fieldElement.classList.add('error');
                                            fieldElement.addEventListener('change', () => {
                                                fieldElement.classList.remove('error');
                                            }, { once: true });
                                        }
                                    });
                                }
                            });
                        }

                        // Mostrar alerta de errores
                        errorsAlert.classList.remove('hidden');
                        
                        // Scroll al inicio
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    // Manejar otros errores HTTP
                    if (!response.ok) {
                        console.log('❌ Error del servidor:', response.status);
                        
                        const errorsAlert = document.getElementById('errorsAlert');
                        const errorsList = document.getElementById('errorsList');
                        
                        errorsList.innerHTML = '';
                        const li = document.createElement('li');
                        li.textContent = data.message || `Error del servidor (${response.status})`;
                        errorsList.appendChild(li);
                        
                        errorsAlert.classList.remove('hidden');
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        return;
                    }

                    // Si fue exitoso
                    if (data.success) {
                        console.log('✅ Apoyo guardado exitosamente');
                        
                        // Mostrar mensaje de éxito
                        const successAlert = document.getElementById('successAlert');
                        const successMessage = document.getElementById('successMessage');
                        
                        if (data.message) {
                            successMessage.textContent = '✅ ' + data.message;
                        }
                        
                        successAlert.classList.remove('hidden');
                        
                        // Scroll al inicio
                        window.scrollTo({ top: 0, behavior: 'smooth' });
                        
                        // Redirigir después de 2 segundos
                        setTimeout(() => {
                            window.location.href = '{{ route("apoyos.index") }}';
                        }, 2000);
                    }

                } catch (error) {
                    console.error('🔥 Error en fetch:', error);
                    
                    const errorsAlert = document.getElementById('errorsAlert');
                    const errorsList = document.getElementById('errorsList');
                    
                    errorsList.innerHTML = '';
                    const li = document.createElement('li');
                    li.textContent = 'Error de conexión: ' + error.message;
                    errorsList.appendChild(li);
                    
                    errorsAlert.classList.remove('hidden');
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                }
            });
            // ==================== FIN INTERCEPTOR ====================

        })();
    </script>

    <x-site-footer class="mt-16" />

</body>
</html>
