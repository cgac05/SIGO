<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Historial de Recursos Financieros
        </h2>
    </x-slot>

    <link href="https://fonts.bunny.net/css?family=sora:400,600,700,800&display=swap" rel="stylesheet"/>

    <style>
        :root { --navy:#0f2044; --blue:#1a4a8a; --light:#eef3fb; --green:#16a34a; }
        body { font-family:'Sora',sans-serif; }

        /* HERO */
        .hero-panel {
            background: linear-gradient(135deg, #1e3a8a 0%, #1d4ed8 50%, #2563eb 100%);
            position:relative; overflow:hidden;
        }
        .hero-panel::before {
            content:''; position:absolute; inset:0;
            background-image: radial-gradient(circle at 80% 20%, rgba(255,255,255,.08) 0%, transparent 55%);
            pointer-events:none;
        }
        .hero-dots {
            position:absolute; inset:0;
            background-image: radial-gradient(rgba(255,255,255,.08) 1px, transparent 1px);
            background-size:24px 24px; pointer-events:none;
        }

        /* TABLA */
        .sol-table { width:100%; border-collapse:collapse; }
        .sol-table th {
            background:#1e3a8a; color:#fff;
            font-size:.72rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.06em; padding:.75rem 1rem; text-align:left;
        }
        .sol-table td { padding:.75rem 1rem; font-size:.85rem; color:#1e293b; border-bottom:1px solid #e2e8f0; }
        .sol-table tr:hover td { background:#eff6ff; cursor:pointer; }

        /* BADGES */
        .badge { display:inline-block; padding:.2rem .65rem; border-radius:999px; font-size:.7rem; font-weight:700; text-transform:uppercase; }
        .badge-eco  { background:#fef3c7; color:#92400e; }
        .badge-esp  { background:#dcfce7; color:#166534; }
        .badge-cuv  { background:#dbeafe; color:#1e40af; font-family:monospace; font-size:.65rem; }

        /* MODAL */
        .modal-overlay {
            position:fixed; inset:0; z-index:60;
            background:rgba(10,20,50,.65); backdrop-filter:blur(4px);
            display:flex; align-items:center; justify-content:center; padding:1rem;
            opacity:0; pointer-events:none; transition:opacity .25s;
        }
        .modal-overlay.open { opacity:1; pointer-events:all; }
        .modal-box {
            background:#fff; border-radius:20px; width:100%; max-width:700px;
            max-height:92vh; overflow-y:auto;
            box-shadow:0 24px 64px rgba(10,20,50,.30);
            transform:translateY(20px) scale(.97); transition:transform .3s ease; position:relative;
        }
        .modal-overlay.open .modal-box { transform:translateY(0) scale(1); }
        .modal-header {
            background:linear-gradient(135deg,#1e3a8a,#2563eb);
            padding:1.5rem 2rem; border-radius:20px 20px 0 0;
            display:flex; align-items:center; justify-content:space-between;
        }
        .modal-close {
            background:rgba(255,255,255,.2); border:none; color:#fff;
            width:32px; height:32px; border-radius:50%; cursor:pointer;
            display:flex; align-items:center; justify-content:center;
            font-size:1.1rem; transition:background .2s;
        }
        .modal-close:hover { background:rgba(255,255,255,.35); }
        .modal-body { padding:1.75rem 2rem; }

        /* INFO GRID */
        .info-grid { display:grid; grid-template-columns:1fr 1fr; gap:.75rem; margin-bottom:1.25rem; }
        .info-cell { background:var(--light); border-radius:10px; padding:.65rem .85rem; }
        .info-cell .lbl { font-size:.65rem; color:#64748b; font-weight:700; text-transform:uppercase; letter-spacing:.05em; }
        .info-cell .val { font-size:.9rem; color:var(--navy); font-weight:700; margin-top:.1rem; }

        /* SECCIÓN */
        .section-title {
            font-size:.72rem; font-weight:700; color:#1e40af;
            text-transform:uppercase; letter-spacing:.08em;
            padding-bottom:.4rem; border-bottom:2px solid #bfdbfe; margin-bottom:.85rem;
        }

        /* BOTONES */
        .btn-imprimir {
            width:100%; padding:.9rem;
            background:linear-gradient(135deg,#1e3a8a,#2563eb);
            color:#fff; border:none; border-radius:12px;
            font-size:.95rem; font-weight:700; cursor:pointer;
            display:inline-flex; align-items:center; justify-content:center; gap:.5rem;
            transition:opacity .2s, transform .15s; margin-top:.5rem; text-decoration: none;
        }
        .btn-imprimir:hover { opacity:.9; transform:translateY(-1px); color: white; }

        /* TABS */
        .tab-btn {
            padding:.5rem 1.25rem; border-radius:8px; font-size:.82rem; font-weight:600;
            cursor:pointer; border:none; transition:all .2s;
        }
        .tab-btn.active { background:#1e3a8a; color:#fff; }
        .tab-btn.inactive { background:#f1f5f9; color:#64748b; }
        .tab-btn.inactive:hover { background:#e2e8f0; }

        /* EMPTY */
        .empty-state { text-align:center; padding:4rem 2rem; color:#64748b; }
    </style>

    {{-- HERO --}}
    <div class="hero-panel py-8 px-4 md:px-8">
        <div class="hero-dots"></div>
        <div class="max-w-7xl mx-auto relative z-10">
            <div class="flex items-center gap-3 mb-1">
                <svg class="w-6 h-6 text-blue-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-blue-300 font-semibold text-sm uppercase tracking-widest">Recursos Financieros</span>
            </div>
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-3xl font-extrabold text-white">Historial de Cierres</h1>
                <a href="{{ route('finanzas.panel') }}" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-semibold transition ml-4 border border-white/30">
                    Ir al Panel Activo
                </a>
            </div>
            <p class="text-blue-100 text-sm">Explora el registro histórico de las solicitudes con entrega de recursos cerrada debidamente.</p>
            <div class="mt-4 inline-flex items-center gap-2 bg-white/10 border border-white/20 text-white text-xs font-medium rounded-full px-4 py-1.5">
                <span class="w-2 h-2 rounded-full bg-blue-300 inline-block"></span>
                {{ $solicitudes->count() }} solicitud(es) cerrada(s)
            </div>
        </div>
    </div>

    {{-- CONTENIDO --}}
    <div class="py-10 px-4 md:px-8 bg-gray-50 min-h-screen"
         x-data="historialPanel({{ json_encode($solicitudes->values()) }})">

        <div class="max-w-7xl mx-auto">

            {{-- Sin solicitudes --}}
            <template x-if="solicitudes.length === 0">
                <div class="empty-state">
                    <svg class="w-20 h-20 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-lg font-semibold text-gray-500 mb-1">El historial está vacío</p>
                    <p class="text-sm text-gray-400">Las solicitudes en las que confirmes el pago aparecerán aquí.</p>
                </div>
            </template>

            {{-- Tabla --}}
            <template x-if="solicitudes.length > 0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-800">Cierres Financieros Generados</h3>
                        </div>
                        {{-- Filtros por tipo --}}
                        <div class="flex gap-2">
                            <button class="tab-btn" :class="filtro === 'todos' ? 'active' : 'inactive'" @click="filtro = 'todos'">Todos</button>
                            <button class="tab-btn" :class="filtro === 'Económico' ? 'active' : 'inactive'" @click="filtro = 'Económico'">Económico</button>
                            <button class="tab-btn" :class="filtro === 'Especie' ? 'active' : 'inactive'" @click="filtro = 'Especie'">Especie</button>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="sol-table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Beneficiario</th>
                                    <th>Apoyo</th>
                                    <th>Tipo</th>
                                    <th>Fecha Cierre</th>
                                    <th>Monto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="sol in solicitudesFiltradas" :key="sol.folio">
                                    <tr @click="abrirModal(sol)">
                                        <td class="font-bold text-blue-700" x-text="'#' + sol.folio"></td>
                                        <td x-text="sol.nombre + ' ' + sol.apellido_paterno + ' ' + sol.apellido_materno"></td>
                                        <td x-text="sol.nombre_apoyo"></td>
                                        <td>
                                            <span class="badge" :class="sol.tipo_apoyo === 'Económico' ? 'badge-eco' : 'badge-esp'" x-text="sol.tipo_apoyo"></span>
                                        </td>
                                        <td x-text="formatFecha(sol.fecha_cierre_financiero)"></td>
                                        <td x-text="sol.monto_entregado ? '$' + Number(sol.monto_entregado).toLocaleString('es-MX') : 'N/A'"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

        </div>

        {{-- MODAL DE DETALLE --}}
        <div class="modal-overlay" :class="{ open: modalAbierto }" @click.self="cerrarModal()">
            <div class="modal-box">

                <div class="modal-header">
                    <div>
                        <p class="text-blue-200 text-xs font-semibold uppercase tracking-widest mb-1">Detalle del Cierre</p>
                        <h2 class="text-white text-xl font-extrabold" x-text="'Folio #' + (solActual && solActual.folio)"></h2>
                        <p class="text-blue-200 text-sm mt-1" x-text="solActual && solActual.nombre_apoyo"></p>
                    </div>
                    <button class="modal-close" @click="cerrarModal()">✕</button>
                </div>

                <div class="modal-body">

                    <p class="section-title">Datos del apoyo entregado</p>
                    <div class="info-grid">
                        <div class="info-cell">
                            <div class="lbl">Tipo</div>
                            <div class="val" x-text="solActual && solActual.tipo_apoyo"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">CUV Oficial</div>
                            <div class="val text-blue-700" style="font-family: monospace; font-size: 0.75rem;" x-text="solActual && solActual.cuv"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Monto / Valor Registrado</div>
                            <div class="val" x-text="solActual && solActual.monto_entregado ? '$' + Number(solActual.monto_entregado).toLocaleString('es-MX', {minimumFractionDigits:2}) : 'N/A'"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Referencia Interna / Cheque</div>
                            <div class="val" x-text="solActual && (solActual.folio_institucional || 'N/A')"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Fecha de Entrega Física</div>
                            <div class="val" x-text="solActual && formatFecha(solActual.fecha_entrega_recurso)"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Fecha Firma Directiva (Aprobación)</div>
                            <div class="val" x-text="solActual && formatFecha(solActual.fecha_firma_directivo || solActual.fecha_creacion)"></div>
                        </div>
                    </div>

                    <div class="border-t border-dashed border-slate-200 my-4"></div>

                    <template x-if="solActual">
                        <a :href="'/finanzas/' + solActual.folio + '/comprobante'" target="_blank" class="btn-imprimir">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                            </svg>
                            <span>Generar Comprobante PDF</span>
                        </a>
                    </template>
                </div>
            </div>
        </div>

    </div>

    <script>
        function historialPanel(data) {
            return {
                solicitudes: data,
                solActual: null,
                modalAbierto: false,
                filtro: 'todos',

                get solicitudesFiltradas() {
                    if (this.filtro === 'todos') return this.solicitudes;
                    return this.solicitudes.filter(s => s.tipo_apoyo === this.filtro);
                },
                abrirModal(sol) {
                    this.solActual = sol;
                    this.modalAbierto = true;
                    document.body.style.overflow = 'hidden';
                },
                cerrarModal() {
                    this.modalAbierto = false;
                    document.body.style.overflow = '';
                },
                formatFecha(fecha) {
                    if (!fecha) return '—';
                    try {
                        return new Date(fecha).toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric', hour:'2-digit', minute:'2-digit' });
                    } catch { return fecha; }
                }
            }
        }
    </script>

</x-app-layout>
