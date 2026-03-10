<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Apoyos Disponibles
        </h2>
    </x-slot>

    <link href="https://fonts.bunny.net/css?family=sora:400,600,700,800&display=swap" rel="stylesheet"/>

    <style>
        :root {
            --sigo-navy:  #0f2044;
            --sigo-blue:  #1a4a8a;
            --sigo-light: #eef3fb;
        }
        body { font-family: 'Sora', sans-serif; }

        /* HERO */
        .hero-panel {
            background: linear-gradient(135deg, var(--sigo-navy) 0%, var(--sigo-blue) 60%, #2563eb 100%);
            position: relative; overflow: hidden;
        }
        .hero-panel::before {
            content: ''; position: absolute; inset: 0;
            background-image: radial-gradient(circle at 80% 20%, rgba(232,160,32,.18) 0%, transparent 55%),
                              radial-gradient(circle at 10% 80%, rgba(255,255,255,.05) 0%, transparent 45%);
            pointer-events: none;
        }
        .hero-dots {
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,.10) 1px, transparent 1px);
            background-size: 24px 24px; pointer-events: none;
        }

        /* TARJETAS */
        .apoyo-card {
            background: #fff; border-radius: 16px; overflow: hidden;
            box-shadow: 0 2px 12px rgba(15,32,68,.08);
            transition: transform .25s ease, box-shadow .25s ease;
            cursor: pointer; border: 1.5px solid #e2e8f0;
            display: flex; flex-direction: column;
        }
        .apoyo-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 32px rgba(15,32,68,.16);
            border-color: var(--sigo-blue);
        }
        .apoyo-card .card-img-wrap {
            position: relative; height: 190px; overflow: hidden; background: var(--sigo-light);
        }
        .apoyo-card .card-img-wrap img {
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
        .tipo-badge.especie   { background: #dcfce7; color: #166534; }
        .apoyo-card .card-body {
            padding: 1rem 1.25rem 1.25rem; flex: 1;
            display: flex; flex-direction: column; gap: .5rem;
        }
        .apoyo-card .card-title { font-size: 1rem; font-weight: 700; color: var(--sigo-navy); line-height: 1.35; }
        .apoyo-card .card-meta  { font-size: .75rem; color: #64748b; display: flex; align-items: center; gap: .3rem; }
        .apoyo-card .btn-ver {
            margin-top: auto; padding: .55rem 1rem; background: var(--sigo-navy);
            color: #fff; border-radius: 8px; font-size: .8rem; font-weight: 600;
            text-align: center; transition: background .2s;
        }
        .apoyo-card:hover .btn-ver { background: var(--sigo-blue); }

        /* CHIPS */
        .req-chip {
            display: inline-flex; align-items: center; gap: .35rem;
            background: var(--sigo-light); color: var(--sigo-navy); border: 1px solid #c7d7f0;
            border-radius: 8px; padding: .3rem .75rem; font-size: .78rem; font-weight: 500;
        }

        /* MODAL DETALLE */
        .modal-overlay {
            position: fixed; inset: 0; z-index: 60;
            background: rgba(10,20,50,.65); backdrop-filter: blur(4px);
            display: flex; align-items: center; justify-content: center; padding: 1rem;
            opacity: 0; pointer-events: none; transition: opacity .25s;
        }
        .modal-overlay.open { opacity: 1; pointer-events: all; }
        .modal-box {
            background: #fff; border-radius: 20px; width: 100%; max-width: 640px;
            max-height: 90vh; overflow-y: auto;
            box-shadow: 0 24px 64px rgba(10,20,50,.30);
            transform: translateY(20px) scale(.97); transition: transform .3s ease; position: relative;
        }
        .modal-overlay.open .modal-box { transform: translateY(0) scale(1); }
        .modal-img { width: 100%; height: 220px; object-fit: cover; border-radius: 20px 20px 0 0; }
        .modal-img-placeholder {
            width: 100%; height: 220px; display: flex; align-items: center; justify-content: center;
            background: linear-gradient(135deg, var(--sigo-light), #dbeafe); color: var(--sigo-blue);
            border-radius: 20px 20px 0 0;
        }
        .modal-close {
            position: absolute; top: 14px; right: 14px; background: rgba(255,255,255,.9);
            border: none; width: 32px; height: 32px; border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; font-size: 1.1rem; color: var(--sigo-navy);
            box-shadow: 0 2px 8px rgba(0,0,0,.15); transition: background .2s;
        }
        .modal-close:hover { background: #fff; }
        .modal-body { padding: 1.5rem 1.75rem 1.75rem; }
        .modal-title { font-size: 1.35rem; font-weight: 800; color: var(--sigo-navy); line-height: 1.3; margin-bottom: .5rem; }
        .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: .65rem; margin: 1rem 0; }
        .info-cell { background: var(--sigo-light); border-radius: 10px; padding: .65rem .85rem; }
        .info-cell .label { font-size: .68rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .info-cell .value { font-size: .9rem; color: var(--sigo-navy); font-weight: 700; margin-top: .1rem; }
        .btn-solicitar {
            width: 100%; padding: .9rem;
            background: linear-gradient(135deg, var(--sigo-navy), var(--sigo-blue));
            color: #fff; border: none; border-radius: 12px;
            font-size: 1rem; font-weight: 700; cursor: pointer; letter-spacing: .03em;
            transition: opacity .2s, transform .15s; margin-top: 1.25rem;
            display: flex; align-items: center; justify-content: center; gap: .5rem;
        }
        .btn-solicitar:hover { opacity: .9; transform: translateY(-1px); }

        /* CARGA DE DOCUMENTOS */
        .doc-upload-row {
            display: flex; align-items: center; justify-content: space-between;
            gap: .75rem; flex-wrap: wrap;
            padding: .65rem .85rem; background: #f8fafc;
            border-radius: 10px; border: 1px solid #e2e8f0;
        }
        .doc-upload-row label { font-size: .85rem; color: var(--sigo-navy); font-weight: 600; flex: 1; min-width: 150px; }
        .doc-upload-row input[type=file] { font-size: .8rem; flex: 1.5; min-width: 180px; color: #334155; }
        .doc-upload-row input[type=file]::file-selector-button {
            background: var(--sigo-navy); color: #fff; border: none; border-radius: 6px;
            padding: .35rem .7rem; font-size: .75rem; font-weight: 600;
            cursor: pointer; margin-right: .5rem; transition: background .2s;
        }
        .doc-upload-row input[type=file]::file-selector-button:hover { background: var(--sigo-blue); }

        /* ESTADO VACÍO */
        .empty-state { text-align: center; padding: 4rem 2rem; color: #64748b; }
        .empty-state svg { width: 80px; height: 80px; margin: 0 auto 1rem; opacity: .4; }

        /* MODAL CONFIRMACIÓN */
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

    {{-- HERO --}}
    <div class="hero-panel py-10 px-4 md:px-8">
        <div class="hero-dots"></div>
        <div class="max-w-7xl mx-auto relative z-10">
            <div class="flex items-center gap-3 mb-1">
                <svg class="w-7 h-7 text-yellow-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.5 0-3 .7-3 2s1.5 2 3 2 3 .7 3 2-1.5 2-3 2m0-8v1m0 8v1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-yellow-300 font-semibold text-sm uppercase tracking-widest">Panel de Solicitudes</span>
            </div>
            <h1 class="text-3xl md:text-4xl font-extrabold text-white mb-2">Apoyos Disponibles</h1>
            <p class="text-blue-200 text-sm max-w-xl">
                Selecciona el apoyo de tu interés para conocer los detalles y registrar tu solicitud.
                Solo se muestran los programas vigentes al día de hoy.
            </p>
            <div class="mt-4 inline-flex items-center gap-2 bg-white/10 border border-white/20 text-white text-xs font-medium rounded-full px-4 py-1.5">
                <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                Fecha de consulta: {{ now()->format('d/m/Y H:i') }}
            </div>
        </div>
    </div>

    {{-- CONTENIDO PRINCIPAL --}}
    <div class="py-10 px-4 md:px-8 bg-gray-50 min-h-screen"
         x-data="solicitudesApp({{ isset($apoyosJson) ? $apoyosJson : '[]' }})">

        {{-- Alerta éxito --}}
        @if(session('exito'))
        <div class="max-w-7xl mx-auto mb-6">
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-green-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="font-semibold">¡Solicitud registrada exitosamente!</span>
            </div>
        </div>
        @endif

        {{-- Alerta error --}}
        @if(session('error'))
        <div class="max-w-7xl mx-auto mb-6">
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-3 flex items-center gap-3">
                <svg class="w-5 h-5 text-red-600 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
        </div>
        @endif

        <div class="max-w-7xl mx-auto">

            {{-- Historial de solicitudes registradas --}}
            <div class="bg-white border border-gray-200 rounded-2xl shadow-sm p-5 mb-8">
                <div class="flex items-center justify-between gap-3 mb-4">
                    <h3 class="text-base md:text-lg font-extrabold" style="color: var(--sigo-navy)">Mis solicitudes recientes</h3>
                    <span class="text-xs text-gray-500">Ultimos 10 registros</span>
                </div>

                @if(isset($misSolicitudes) && count($misSolicitudes) > 0)
                    <div class="overflow-x-auto">
                        <table class="min-w-full text-sm">
                            <thead>
                                <tr class="text-left text-gray-500 border-b border-gray-200">
                                    <th class="py-2 pr-4 font-semibold">Folio</th>
                                    <th class="py-2 pr-4 font-semibold">Apoyo</th>
                                    <th class="py-2 pr-4 font-semibold">Fecha</th>
                                    <th class="py-2 pr-4 font-semibold">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($misSolicitudes as $solicitud)
                                    <tr class="border-b border-gray-100 text-gray-700">
                                        <td class="py-2 pr-4 font-semibold">{{ $solicitud->folio }}</td>
                                        <td class="py-2 pr-4">{{ $solicitud->nombre_apoyo ?? 'Sin nombre' }}</td>
                                        <td class="py-2 pr-4">{{ $solicitud->fecha_creacion ? \Illuminate\Support\Carbon::parse($solicitud->fecha_creacion)->format('d/m/Y H:i') : 'Sin fecha' }}</td>
                                        <td class="py-2 pr-4">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                                {{ $solicitud->estado ?? 'Pendiente' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="text-sm text-gray-500">Aun no tienes solicitudes registradas.</p>
                @endif
            </div>

            {{-- Sin apoyos vigentes --}}
            <template x-if="apoyos.length === 0">
                <div class="empty-state">
                    <svg fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-3-3v6m-8.25 4.5A2.25 2.25 0 004.5 21h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5H15M9 4.5H4.5A2.25 2.25 0 002.25 6.75v1.5"/>
                    </svg>
                    <p class="text-lg font-semibold text-gray-500 mb-1">No hay apoyos disponibles en este momento</p>
                    <p class="text-sm text-gray-400">Revisa nuevamente más adelante o contacta con tu asesor.</p>
                </div>
            </template>

            {{-- Grid de tarjetas --}}
            <template x-if="apoyos.length > 0">
                <div>
                    <p class="text-sm text-gray-500 mb-5">
                        <span class="font-bold text-gray-700" x-text="apoyos.length"></span> apoyo(s) vigente(s) disponible(s)
                    </p>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                        <template x-for="apoyo in apoyos" :key="apoyo.id_apoyo">
                            <div class="apoyo-card" @click="abrirModal(apoyo)">

                                <div class="card-img-wrap">
                                    <template x-if="apoyo.foto_ruta">
                                        <img :src="'/' + apoyo.foto_ruta" :alt="apoyo.nombre_apoyo"/>
                                    </template>
                                    <template x-if="!apoyo.foto_ruta">
                                        <div class="card-img-placeholder">
                                            <svg class="w-12 h-12 mb-2 opacity-50" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909M3 21h18M3.75 3h16.5M4.5 3v18m15-18v18"/>
                                            </svg>
                                            <span class="text-xs font-semibold opacity-60">Sin imagen</span>
                                        </div>
                                    </template>
                                    <span class="tipo-badge"
                                          :class="apoyo.tipo_apoyo === 'Económico' ? 'economico' : 'especie'"
                                          x-text="apoyo.tipo_apoyo"></span>
                                </div>

                                <div class="card-body">
                                    <div class="card-title" x-text="apoyo.nombre_apoyo"></div>
                                    <div class="card-meta">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25"/>
                                        </svg>
                                        <span x-text="'Vigente hasta ' + formatFecha(apoyo.fechafin ?? apoyo.fechaFin ?? apoyo.fechafin)"></span>
                                    </div>
                                    <template x-if="apoyo.tipo_apoyo === 'Económico' && apoyo.monto_maximo > 0">
                                        <div class="card-meta" style="color: #92400e">
                                            <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33"/>
                                            </svg>
                                            <span x-text="'Hasta $' + Number(apoyo.monto_maximo).toLocaleString('es-MX')"></span>
                                        </div>
                                    </template>
                                    <div class="btn-ver">Ver detalles y solicitar →</div>
                                </div>

                            </div>
                        </template>
                    </div>
                </div>
            </template>

        </div>

        {{-- ═══════════════════════════════════════
             MODAL DE DETALLE + FORMULARIO
        ═══════════════════════════════════════ --}}
        <div class="modal-overlay" :class="{ open: modalAbierto }" @click.self="cerrarModal()">
            <div class="modal-box">
                <button class="modal-close" @click="cerrarModal()">✕</button>

                <template x-if="apoyoActual && apoyoActual.foto_ruta">
                    <img class="modal-img" :src="'/' + apoyoActual.foto_ruta" :alt="apoyoActual.nombre_apoyo"/>
                </template>
                <template x-if="apoyoActual && !apoyoActual.foto_ruta">
                    <div class="modal-img-placeholder">
                        <svg class="w-16 h-16 opacity-40" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909"/>
                        </svg>
                    </div>
                </template>

                <div class="modal-body">
                    <span class="tipo-badge mb-2" style="position: static; display: inline-block"
                          :class="apoyoActual && apoyoActual.tipo_apoyo === 'Económico' ? 'economico' : 'especie'"
                          x-text="apoyoActual && apoyoActual.tipo_apoyo"></span>

                    <h2 class="modal-title" x-text="apoyoActual && apoyoActual.nombre_apoyo"></h2>

                    <template x-if="apoyoActual && apoyoActual.descripcion">
                        <p class="text-sm text-gray-600 mb-3 leading-relaxed" x-text="apoyoActual.descripcion"></p>
                    </template>

                    <div class="info-grid">
                        <div class="info-cell">
                            <div class="label">Fecha de inicio</div>
                            <div class="value" x-text="formatFecha(apoyoActual && apoyoActual.fechaInicio)"></div>
                        </div>
                        <div class="info-cell">
                            <div class="label">Fecha de cierre</div>
                            <div class="value" x-text="formatFecha(apoyoActual && (apoyoActual.fechafin ?? apoyoActual.fechaFin))"></div>
                        </div>
                        <template x-if="apoyoActual && apoyoActual.tipo_apoyo === 'Económico' && apoyoActual.monto_maximo > 0">
                            <div class="info-cell" style="grid-column: 1 / -1">
                                <div class="label">Apoyo económico máximo</div>
                                <div class="value" x-text="'$' + Number(apoyoActual.monto_maximo).toLocaleString('es-MX', { minimumFractionDigits: 2 })"></div>
                            </div>
                        </template>
                    </div>

                    <template x-if="apoyoActual && apoyoActual.requisitos && apoyoActual.requisitos.length > 0">
                        <div class="mb-1">
                            <p class="text-xs font-bold text-gray-500 uppercase tracking-widest mb-2">Documentos requeridos</p>
                            <div class="flex flex-wrap gap-2">
                                <template x-for="req in apoyoActual.requisitos" :key="req.fk_id_tipo_doc">
                                    <span class="req-chip">
                                        <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                                        </svg>
                                        <span x-text="req.nombre_documento"></span>
                                    </span>
                                </template>
                            </div>
                        </div>
                    </template>

                    <div class="border-t border-dashed border-gray-200 my-4"></div>

                    <form action="{{ route('solicitud.guardar') }}" method="POST" enctype="multipart/form-data" id="formSolicitud">
                        @csrf
                        <input type="hidden" name="apoyo" :value="apoyoActual && apoyoActual.id_apoyo"/>

                        <template x-if="apoyoActual && apoyoActual.requisitos && apoyoActual.requisitos.length > 0">
                            <div>
                                <p class="text-sm font-bold text-gray-700 mb-3">Adjunta los documentos solicitados:</p>
                                <div class="space-y-2">
                                    <template x-for="req in apoyoActual.requisitos" :key="req.fk_id_tipo_doc">
                                        <div class="doc-upload-row">
                                            <label>
                                                <span x-text="req.nombre_documento"></span>
                                                <span class="text-red-500 ml-1">*</span>
                                            </label>
                                            <input type="file"
                                                   :name="'documento_' + req.fk_id_tipo_doc"
                                                   :accept="req.fk_id_tipo_doc == 7 ? 'image/jpeg,image/png' : '.pdf'"
                                                   required/>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </template>

                        <button type="button" class="btn-solicitar" @click="abrirConfirmacion()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Confirmar Solicitud
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- ═══════════════════════════════════════
             MODAL DE CONFIRMACIÓN
        ═══════════════════════════════════════ --}}
        <div class="confirm-modal-overlay" :class="{ open: confirmarAbierto }">
            <div class="confirm-box">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background: #fef3c7">
                    <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-extrabold mb-2" style="color: var(--sigo-navy)">¿Confirmar solicitud?</h3>
                <p class="text-sm text-gray-500 mb-5">
                    Estás a punto de enviar tu solicitud para el apoyo
                    <strong class="text-gray-800" x-text="apoyoActual && apoyoActual.nombre_apoyo"></strong>.
                    Esta acción no se puede deshacer.
                </p>
                <div class="flex gap-3">
                    <button type="button"
                            class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition"
                            @click="confirmarAbierto = false">
                        Cancelar
                    </button>
                    <button type="button"
                            class="flex-1 py-2.5 rounded-xl text-white font-bold text-sm transition"
                            style="background: var(--sigo-navy)"
                            @click="enviarSolicitud()">
                        Sí, enviar
                    </button>
                </div>
            </div>
        </div>

    </div>{{-- /x-data --}}

    <script>
        function solicitudesApp(apoyosData) {
            return {
                apoyos: apoyosData,
                apoyoActual: null,
                modalAbierto: false,
                confirmarAbierto: false,

                abrirModal(apoyo) {
                    this.apoyoActual = apoyo;
                    this.modalAbierto = true;
                    document.body.style.overflow = 'hidden';
                },
                cerrarModal() {
                    this.modalAbierto = false;
                    this.confirmarAbierto = false;
                    document.body.style.overflow = '';
                },
                abrirConfirmacion() {
                    const form = document.getElementById('formSolicitud');
                    const inputs = form.querySelectorAll('input[type=file][required]');
                    let valido = true;
                    inputs.forEach(i => { if (!i.files || i.files.length === 0) valido = false; });
                    if (!valido) {
                        alert('Por favor adjunta todos los documentos requeridos antes de continuar.');
                        return;
                    }
                    this.confirmarAbierto = true;
                },
                enviarSolicitud() {
                    document.getElementById('formSolicitud').submit();
                },
                formatFecha(fecha) {
                    if (!fecha) return '—';
                    try {
                        const d = new Date(fecha);
                        return d.toLocaleDateString('es-MX', { day: '2-digit', month: 'short', year: 'numeric' });
                    } catch(e) { return fecha; }
                }
            }
        }
    </script>

</x-app-layout>