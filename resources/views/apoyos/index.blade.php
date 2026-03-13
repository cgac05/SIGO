
@php
    use Carbon\Carbon;
    // $user ya viene del controller con la relación 'personal' cargada
    $isAdmin = $user && $user->isPersonal() && $user->personal && $user->personal->fk_rol == 1;
    $isDirector = $user && $user->isPersonal() && $user->personal && $user->personal->fk_rol == 2;
    $canEdit = $isAdmin; // Solo administradores pueden editar
    $userRole = $isAdmin ? 'administrativo' : ($isDirector ? 'directivo' : 'otro');

    $currency = function ($v) {
        if ($v === null) return '-';
        return '$' . number_format((float)$v, 2);
    };
@endphp

@vite('resources/js/apoyos-app.js')

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Administrar Recursos de Apoyo</h2>
    </x-slot>

    <style>
        :root {
            --sigo-navy: #0f2044;
            --sigo-blue: #1a4a8a;
            --sigo-light: #eef3fb;
        }

        .apoyo-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: 0 2px 12px rgba(15,32,68,.08);
            transition: transform .25s ease, box-shadow .25s ease;
            cursor: pointer; border: 1.5px solid #e2e8f0;
            display: flex; flex-direction: column;
            position: relative;
        }
        .apoyo-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(15,32,68,.16);
            border-color: var(--sigo-blue);
        }
        .card-img-wrap {
            position: relative; height: 190px; overflow: hidden; background: var(--sigo-light);
        }
        .card-img-wrap img {
            width: 100%; height: 100%; object-fit: cover; transition: transform .4s ease;
        }
        .apoyo-card:hover .card-img-wrap img { transform: scale(1.06); }
        .card-img-placeholder {
            width: 100%; height: 100%; display: flex; flex-direction: column;
            align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--sigo-light), #dbeafe); color: var(--sigo-blue);
        }
        .tipo-badge {
            position: absolute; top: 12px; right: 12px;
            font-size: .7rem; font-weight: 700; letter-spacing: .05em;
            padding: 3px 10px; border-radius: 999px; text-transform: uppercase;
        }
        .tipo-badge.economico { background: #fef3c7; color: #92400e; }
        .tipo-badge.especie { background: #dcfce7; color: #166534; }
        
        .btn-action {
            position: absolute; top: 12px; left: 12px;
            width: 32px; height: 32px; border-radius: 50%;
            border: none; display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: background .2s;
            color: white; font-weight: bold;
        }
        .btn-edit { background: #3b82f6; margin-right: 8px; }
        .btn-edit:hover { background: #2563eb; }
        .btn-delete { background: #ef4444; }
        .btn-delete:hover { background: #dc2626; }

        .card-body {
            padding: 1rem 1.25rem 1.25rem; flex: 1;
            display: flex; flex-direction: column; gap: .5rem;
        }
        .card-title { font-size: 1rem; font-weight: 700; color: var(--sigo-navy); line-height: 1.35; }
        .card-meta { font-size: .75rem; color: #64748b; display: flex; align-items: center; gap: .3rem; }
        .btn-ver {
            margin-top: auto; padding: .55rem 1rem; background: var(--sigo-navy);
            color: #fff; border-radius: 8px; font-size: .8rem; font-weight: 600;
            text-align: center; transition: background .2s; border: none; cursor: pointer;
        }
        .btn-ver:hover { background: var(--sigo-blue); }

        .modal-overlay {
            position: fixed; inset: 0; z-index: 60;
            background: rgba(10,20,50,.65); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center; padding: 1rem;
            opacity: 0; pointer-events: none; transition: opacity .25s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal-box {
            background: #fff; border-radius: 20px; width: 100%; max-width: 900px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 24px 64px rgba(10,20,50,.30);
            transform: translateY(20px) scale(.97); transition: transform .3s ease; position: relative;
            display: flex; flex-direction: column;
        }
        .modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
        
        .modal-header {
            position: relative; height: 280px; overflow: hidden; flex-shrink: 0;
        }
        .modal-header img { 
            width: 100%; height: 100%; object-fit: cover; border-radius: 20px 20px 0 0; 
        }
        .modal-header .modal-img-placeholder {
            position: absolute; inset: 0;
            width: 100%; height: 100%; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--sigo-light), #dbeafe); color: var(--sigo-blue);
            border-radius: 20px 20px 0 0;
        }
        .modal-close {
            position: absolute; top: 14px; right: 14px; background: rgba(255,255,255,.95);
            border: none; width: 36px; height: 36px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.2rem; color: var(--sigo-navy);
            box-shadow: 0 2px 8px rgba(0,0,0,.15); transition: background .2s; z-index: 10;
            font-weight: bold;
        }
        .modal-close:hover { background: #fff; }
        
        .modal-content { 
            flex: 1; overflow-y: auto; padding: 2rem 2.5rem; 
        }
        
        .modal-header-info {
            display: flex; justify-content: space-between; align-items: start; margin-bottom: 1.5rem;
        }
        .modal-title-section h2 { 
            font-size: 1.8rem; font-weight: 800; color: var(--sigo-navy); line-height: 1.2; margin-bottom: .5rem; 
        }
        
        .modal-data-grid {
            display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1rem; margin-bottom: 2rem;
        }
        .data-card {
            background: var(--sigo-light); border-radius: 12px; padding: 1.25rem; border-left: 4px solid var(--sigo-blue);
        }
        .data-card .label { 
            font-size: .7rem; color: #64748b; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; margin-bottom: .4rem; 
        }
        .data-card .value { 
            font-size: 1.1rem; color: var(--sigo-navy); font-weight: 700; 
        }
        
        .modal-description {
            background: #f8fafc; border-radius: 12px; padding: 1.25rem; margin-bottom: 2rem; border-left: 4px solid #0891b2;
        }
        .modal-description strong {
            display: block; color: var(--sigo-navy); font-weight: 700; margin-bottom: .5rem; font-size: .85rem;
        }
        .modal-description p {
            color: #475569; line-height: 1.6; font-size: .95rem; margin: 0;
        }
        
        .message-section {
            background: #f1f5f9; border-radius: 12px; padding: 1.5rem; margin-bottom: 2rem;
        }
        .message-section label {
            display: block; color: var(--sigo-navy); font-weight: 700; font-size: .9rem; margin-bottom: .75rem;
        }
        .message-section textarea {
            width: 100%; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 1rem;
            font-size: .95rem; color: #334155; resize: vertical; min-height: 100px; 
            font-family: inherit; transition: border-color .2s;
        }
        .message-section textarea:focus {
            outline: none; border-color: var(--sigo-blue); box-shadow: 0 0 0 3px rgba(74,144,226,.1);
        }
        
        .modal-actions {
            padding-top: 1.5rem; border-top: 1px solid #e2e8f0; display: flex; gap: 1rem;
        }
        .modal-actions button, .modal-actions a {
            flex: 1; padding: 1rem; border-radius: 10px; font-weight: 600; text-align: center;
            border: none; cursor: pointer; transition: all .2s; font-size: .95rem;
        }
        .btn-primary {
            background: var(--sigo-blue); color: #fff;
        }
        .btn-primary:hover { 
            background: var(--sigo-navy); transform: translateY(-2px); box-shadow: 0 4px 12px rgba(26,74,138,.2);
        }
        .btn-secondary {
            background: #e2e8f0; color: var(--sigo-navy);
        }
        .btn-secondary:hover { 
            background: #cbd5e1; 
        }

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
        #toast.error { background: #991b1b; }

        .confirm-modal-overlay {
            position: fixed; inset: 0; z-index: 70;
            background: rgba(10,20,50,.65); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center; padding: 1rem;
            opacity: 0; pointer-events: none; transition: opacity .25s;
        }
        .confirm-modal-overlay.open { opacity: 1; pointer-events: all; }
        .confirm-box {
            background: #fff; border-radius: 20px; width: 100%; max-width: 420px;
            padding: 2rem; text-align: center;
            box-shadow: 0 24px 64px rgba(10,20,50,.30);
            transform: scale(.95); transition: transform .25s;
        }
        .confirm-modal-overlay.open .confirm-box { transform: scale(1); }
    </style>

    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6"
                 x-data="apoyosApp(window.apoyosData, window.apoyosUserRole, window.apoyosCanEdit)">

                @if(session('success'))
                    <div class="mb-4 font-medium text-sm text-green-600">{{ session('success') }}</div>
                @endif

                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-medium">Lista de Apoyos</h3>
                    @if($canEdit)
                    <div class="flex items-center gap-2">
                        <a href="{{ route('apoyos.create') }}">
                            <x-primary-button>+ Nuevo Apoyo</x-primary-button>
                        </a>
                    </div>
                    @endif
                </div>

                {{-- Pasar datos a través de window para evitar problemas con caracteres especiales en HTML --}}
                <script>
                    window.apoyosData = @json($apoyos);
                    window.apoyosUserRole = '{{ $userRole }}';
                    window.apoyosCanEdit = {{ $canEdit ? 'true' : 'false' }};
                </script>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">

                    <template x-for="apoyo in apoyos" :key="apoyo.id_apoyo">
                        <div class="apoyo-card" @click="abrirModal(apoyo)">

                            <div class="card-img-wrap">
                                <template x-if="apoyo.foto_ruta">
                                    <img :src="'/' + apoyo.foto_ruta" 
                                         :alt="apoyo.nombre_apoyo"
                                         onerror="this.style.display='none'; this.nextElementSibling.style.display='flex'"
                                         style="width: 100%; height: 100%; object-fit: cover;"/>
                                    <div class="card-img-placeholder-error" style="display:none;width: 100%; height: 100%; display: flex; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(135deg, var(--sigo-light), #dbeafe); color: var(--sigo-blue);">
                                        <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                                        </svg>
                                        <span class="text-xs font-semibold opacity-60">Imagen no disponible</span>
                                    </div>
                                </template>
                                <template x-if="!apoyo.foto_ruta">
                                    <div class="card-img-placeholder">
                                        <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                                        </svg>
                                        <span class="text-xs font-semibold opacity-60">Sin imagen</span>
                                    </div>
                                </template>

                                <span class="tipo-badge" :class="apoyo.tipo_apoyo === 'Económico' ? 'economico' : 'especie'" x-text="apoyo.tipo_apoyo"></span>

                                @if($canEdit)
                                <div class="absolute top-2 left-2 flex gap-1" @click.stop>
                                    <button type="button" class="btn-action btn-edit" title="Editar" @click="window.location.href='/apoyos/' + apoyo.id_apoyo + '/edit'">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button type="button" class="btn-action btn-delete" title="Eliminar" @click="abrirEliminar(apoyo)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                                @endif
                            </div>

                            <div class="card-body">
                                <div class="card-title" x-text="apoyo.nombre_apoyo"></div>
                                <div class="text-sm text-gray-500 mb-2">
                                    Monto: <strong x-text="'$' + Number(apoyo.monto_maximo).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></strong>
                                </div>
                                <div class="text-xs text-gray-400">
                                    Estado: <strong x-text="apoyo.activo ? 'Activo' : 'Inactivo'"></strong>
                                </div>
                                <div class="btn-ver">Ver detalles</div>
                            </div>

                        </div>
                    </template>

                    <template x-if="apoyos.length === 0">
                        <div class="col-span-full">
                            <div class="bg-gray-50 rounded-lg border border-dashed border-gray-300 p-12 text-center">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                <p class="text-gray-600 font-medium">No hay apoyos registrados.</p>
                                <p class="text-sm text-gray-500 mt-1">Crea uno nuevo para comenzar.</p>
                            </div>
                        </div>
                    </template>

                </div>

                {{-- MODAL DETALLE (Completo con imagen grande) --}}
                <div class="modal-overlay" :class="{ open: modalAbierto }" @click.self="cerrarModal()">
                    <div class="modal-box">
                        <button class="modal-close" @click="cerrarModal()">✕</button>

                        {{-- HEADER CON IMAGEN --}}
                        <div class="modal-header">
                            <template x-if="apoyoActual && apoyoActual.foto_ruta">
                                <img :src="'/' + apoyoActual.foto_ruta" 
                                     :alt="apoyoActual.nombre_apoyo"
                                     onerror="this.style.display='none'; this.parentElement.querySelector('.modal-img-placeholder').style.display='flex'"/>
                            </template>
                            <div class="modal-img-placeholder" :style="!apoyoActual || !apoyoActual.foto_ruta ? 'display: flex' : 'display: none'">
                                <div style="text-align: center;">
                                    <svg class="w-16 h-16 opacity-40 mx-auto mb-3" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                                    </svg>
                                    <p class="text-sm font-medium opacity-60">Sin imagen disponible</p>
                                </div>
                            </div>
                        </div>

                        {{-- CONTENIDO --}}
                        <div class="modal-content">
                            {{-- HEADER INFO --}}
                            <div class="modal-header-info">
                                <div class="modal-title-section" style="flex: 1;">
                                    <h2 x-text="apoyoActual && apoyoActual.nombre_apoyo"></h2>
                                    <span class="tipo-badge mb-2" 
                                          :class="apoyoActual && apoyoActual.tipo_apoyo === 'Económico' ? 'economico' : 'especie'"
                                          x-text="apoyoActual && apoyoActual.tipo_apoyo"
                                          style="position: static; display: inline-block; margin-top: .5rem;"></span>
                                </div>
                            </div>

                            {{-- GRID DE DATOS --}}
                            <div class="modal-data-grid">
                                <div class="data-card">
                                    <div class="label">Tipo de Apoyo</div>
                                    <div class="value" x-text="apoyoActual && apoyoActual.tipo_apoyo"></div>
                                </div>
                                <div class="data-card">
                                    <div class="label">Estado</div>
                                    <div class="value" x-text="apoyoActual && (apoyoActual.activo ? '✓ Activo' : '✗ Inactivo')"></div>
                                </div>
                                <template x-if="apoyoActual && apoyoActual.monto_maximo > 0">
                                    <div class="data-card">
                                        <div class="label">Monto Máximo</div>
                                        <div class="value" x-text="'$' + Number(apoyoActual.monto_maximo).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></div>
                                    </div>
                                </template>
                            </div>

                            {{-- DESCRIPCIÓN --}}
                            <template x-if="apoyoActual && apoyoActual.descripcion">
                                <div class="modal-description">
                                    <strong>Descripción</strong>
                                    <p x-html="apoyoActual.descripcion"></p>
                                </div>
                            </template>

                            {{-- REQUISITOS (si los hay) --}}
                            <template x-if="apoyoActual && apoyoActual.requisitos">
                                <div class="modal-description">
                                    <strong>Requisitos</strong>
                                    <p x-html="apoyoActual.requisitos"></p>
                                </div>
                            </template>

                            {{-- MENSAJE DEL USUARIO --}}
                            <div class="message-section">
                                <label for="mensaje-apoyo">Agregar un comentario o pregunta (opcional)</label>
                                <textarea id="mensaje-apoyo" 
                                          x-model="mensajeUsuario"
                                          placeholder="Escriba su pregunta, comentario o solicitud específica aquí..."></textarea>
                                <small style="display: block; color: #64748b; margin-top: .5rem;">
                                    Este mensaje se incluirá con tu solicitud de apoyo.
                                </small>
                            </div>

                            {{-- BOTONES DE ACCIÓN --}}
                            <div class="modal-actions">
                                @if($canEdit)
                                    <a :href="'/apoyos/' + (apoyoActual && apoyoActual.id_apoyo) + '/edit'"
                                       class="btn-primary">
                                        Editar
                                    </a>
                                    <button type="button" class="btn-secondary" @click="abrirEliminar(apoyoActual)">
                                        Eliminar
                                    </button>
                                @else
                                    <button type="button" class="btn-primary" @click="cerrarModal()">
                                        Entendido
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                {{-- MODAL CONFIRMACIÓN ELIMINACIÓN --}}
                <div class="confirm-modal-overlay" :class="{ open: confirmarEliminacionAbierto }">
                    <div class="confirm-box">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: #fef3c7">
                            <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                            </svg>
                        </div>
                        <h3 class="text-xl font-extrabold mb-2" style="color: var(--sigo-navy)">¿Eliminar apoyo?</h3>
                        <p class="text-sm text-gray-500 mb-5">
                            Estás a punto de eliminar permanentemente el apoyo
                            <strong class="text-gray-800" x-text="apoyoAEliminar && apoyoAEliminar.nombre_apoyo"></strong>.
                            Esta acción no se puede deshacer.
                        </p>
                        <div class="flex gap-3">
                            <button type="button"
                                    class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition"
                                    @click="confirmarEliminacionAbierto = false">
                                Cancelar
                            </button>
                            <button type="button"
                                    class="flex-1 py-2.5 rounded-xl text-white font-bold text-sm transition bg-red-500 hover:bg-red-600"
                                    @click="confirmarEliminacion()">
                                Sí, eliminar
                            </button>
                        </div>
                    </div>
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
