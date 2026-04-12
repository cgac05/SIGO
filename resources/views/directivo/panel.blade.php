<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel Directivo
        </h2>
    </x-slot>

    <link href="https://fonts.bunny.net/css?family=sora:400,600,700,800&display=swap" rel="stylesheet"/>

    <style>
        :root { --navy:#0f2044; --blue:#1a4a8a; --light:#eef3fb; }
        body { font-family:'Sora',sans-serif; }

        /* HERO */
        .hero-panel {
            background: linear-gradient(135deg,var(--navy) 0%,var(--blue) 60%,#2563eb 100%);
            position:relative; overflow:hidden;
        }
        .hero-panel::before {
            content:''; position:absolute; inset:0;
            background-image: radial-gradient(circle at 80% 20%,rgba(232,160,32,.15) 0%,transparent 55%);
            pointer-events:none;
        }
        .hero-dots {
            position:absolute; inset:0;
            background-image: radial-gradient(rgba(255,255,255,.08) 1px,transparent 1px);
            background-size:24px 24px; pointer-events:none;
        }

        /* TABLA */
        .sol-table { width:100%; border-collapse:collapse; }
        .sol-table th {
            background:var(--navy); color:#fff;
            font-size:.72rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.06em; padding:.75rem 1rem; text-align:left;
        }
        .sol-table td { padding:.75rem 1rem; font-size:.85rem; color:#1e293b; border-bottom:1px solid #e2e8f0; }
        .sol-table tr:hover td { background:#f1f5f9; cursor:pointer; }

        /* BADGE ESTADO */
        .badge {
            display:inline-block; padding:.2rem .65rem; border-radius:999px;
            font-size:.7rem; font-weight:700; text-transform:uppercase;
        }
        .badge-aprobada { background:#dcfce7; color:#166534; }
        .badge-pendiente { background:#fef9c3; color:#854d0e; }

        /* MODAL */
        .modal-overlay {
            position:fixed; inset:0; z-index:60;
            background:rgba(10,20,50,.65); backdrop-filter:blur(4px);
            display:flex; align-items:center; justify-content:center; padding:1rem;
            opacity:0; pointer-events:none; transition:opacity .25s;
        }
        .modal-overlay.open { opacity:1; pointer-events:all; }
        .modal-box {
            background:#fff; border-radius:20px; width:100%; max-width:780px;
            max-height:90vh; overflow-y:auto;
            box-shadow:0 24px 64px rgba(10,20,50,.30);
            transform:translateY(20px) scale(.97); transition:transform .3s ease;
            position:relative;
        }
        .modal-overlay.open .modal-box { transform:translateY(0) scale(1); }
        .modal-header {
            background:linear-gradient(135deg,var(--navy),var(--blue));
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

        /* DOCS TABLE */
        .docs-table { width:100%; border-collapse:collapse; margin-top:.5rem; }
        .docs-table th { font-size:.7rem; font-weight:700; color:#64748b; text-transform:uppercase; padding:.5rem .75rem; border-bottom:2px solid #e2e8f0; text-align:left; }
        .docs-table td { padding:.6rem .75rem; font-size:.82rem; border-bottom:1px solid #f1f5f9; }

        /* ESTADO DOC */
        .doc-ok  { color:#166534; font-weight:700; }
        .doc-pen { color:#854d0e; font-weight:700; }
        .doc-rej { color:#991b1b; font-weight:700; }

        /* SECCIÓN */
        .section-title {
            font-size:.72rem; font-weight:700; color:var(--blue);
            text-transform:uppercase; letter-spacing:.08em;
            padding-bottom:.4rem; border-bottom:2px solid var(--light);
            margin-bottom:.85rem;
        }

        /* EMPTY STATE */
        .empty-state { text-align:center; padding:4rem 2rem; color:#64748b; }

        /* FIRMA BTN */
        .btn-firma {
            width:100%; padding:.85rem;
            background:linear-gradient(135deg,var(--navy),var(--blue));
            color:#fff; border:none; border-radius:12px;
            font-size:.95rem; font-weight:700; cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:.5rem;
            transition:opacity .2s, transform .15s; margin-top:1.25rem;
        }
        .btn-firma:hover { opacity:.9; transform:translateY(-1px); }
    </style>

    {{-- HERO --}}
    <div class="hero-panel py-8 px-4 md:px-8">
        <div class="hero-dots"></div>
        <div class="max-w-7xl mx-auto relative z-10">
            <div class="flex items-center gap-3 mb-1">
                <svg class="w-6 h-6 text-yellow-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <span class="text-yellow-300 font-semibold text-sm uppercase tracking-widest">Panel Directivo</span>
            </div>
            <h1 class="text-3xl font-extrabold text-white mb-1">Solicitudes Pendientes de Firma</h1>
            <p class="text-blue-200 text-sm">Solicitudes aprobadas por el área administrativa que requieren tu firma para continuar el proceso.</p>
            <div class="mt-4 inline-flex items-center gap-2 bg-white/10 border border-white/20 text-white text-xs font-medium rounded-full px-4 py-1.5">
                <span class="w-2 h-2 rounded-full bg-green-400 inline-block"></span>
                {{ $solicitudes->count() }} solicitud(es) pendiente(s) de firma
            </div>
        </div>
    </div>

    {{-- CONTENIDO --}}
    <div class="py-10 px-4 md:px-8 bg-gray-50 min-h-screen"
         x-data="directivoPanel({{ json_encode($solicitudes) }})"

        @if(session('status'))
        <div class="max-w-7xl mx-auto mb-5">
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 flex items-center gap-3 font-semibold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                </svg>
                {{ session('status') }}
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="max-w-7xl mx-auto mb-5">
            <div class="bg-red-50 border border-red-200 text-red-800 rounded-xl px-5 py-3 flex items-center gap-3 font-semibold text-sm">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ session('error') }}
            </div>
        </div>
        @endif

        <div class="max-w-7xl mx-auto">

            {{-- Sin solicitudes --}}
            <template x-if="solicitudes.length === 0">
                <div class="empty-state">
                    <svg class="w-20 h-20 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-lg font-semibold text-gray-500 mb-1">No hay solicitudes pendientes de firma</p>
                    <p class="text-sm text-gray-400">Cuando el área administrativa apruebe solicitudes aparecerán aquí.</p>
                </div>
            </template>

            {{-- Tabla --}}
            <template x-if="solicitudes.length > 0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <h3 class="text-lg font-extrabold text-slate-800">Solicitudes aprobadas por el área administrativa</h3>
                        <span class="text-sm text-slate-500">Haz clic en una fila para ver el detalle</span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="sol-table">
                            <thead>
                                <tr>
                                    <th>Folio</th>
                                    <th>Beneficiario</th>
                                    <th>Apoyo</th>
                                    <th>Tipo</th>
                                    <th>Fecha</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="sol in solicitudes" :key="sol.folio">
                                    <tr @click="abrirModal(sol)">
                                        <td class="font-bold text-indigo-700" x-text="'#' + sol.folio"></td>
                                        <td x-text="sol.nombre + ' ' + sol.apellido_paterno + ' ' + sol.apellido_materno"></td>
                                        <td x-text="sol.nombre_apoyo"></td>
                                        <td>
                                            <span class="badge" :class="sol.tipo_apoyo === 'Económico' ? 'badge-aprobada' : 'badge-pendiente'" x-text="sol.tipo_apoyo"></span>
                                        </td>
                                        <td x-text="formatFecha(sol.fecha_creacion)"></td>
                                        <td>
                                            <span class="badge badge-aprobada" x-text="sol.estado"></span>
                                        </td>
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

                {{-- Header --}}
                <div class="modal-header">
                    <div>
                        <p class="text-blue-200 text-xs font-semibold uppercase tracking-widest mb-1">Detalle de Solicitud</p>
                        <h2 class="text-white text-xl font-extrabold" x-text="'Folio #' + (solActual && solActual.folio)"></h2>
                        <p class="text-blue-200 text-sm mt-1" x-text="solActual && solActual.nombre_apoyo"></p>
                    </div>
                    <button class="modal-close" @click="cerrarModal()">✕</button>
                </div>

                <div class="modal-body">

                    {{-- Info beneficiario --}}
                    <p class="section-title">Datos del beneficiario</p>
                    <div class="info-grid">
                        <div class="info-cell">
                            <div class="lbl">Nombre completo</div>
                            <div class="val" x-text="solActual && (solActual.nombre + ' ' + solActual.apellido_paterno + ' ' + solActual.apellido_materno)"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">CURP</div>
                            <div class="val" x-text="solActual && solActual.curp"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Correo</div>
                            <div class="val" x-text="solActual && solActual.correo"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Teléfono</div>
                            <div class="val" x-text="solActual && (solActual.telefono || 'No registrado')"></div>
                        </div>
                    </div>

                    {{-- Info apoyo --}}
                    <p class="section-title">Datos del apoyo</p>
                    <div class="info-grid">
                        <div class="info-cell">
                            <div class="lbl">Apoyo</div>
                            <div class="val" x-text="solActual && solActual.nombre_apoyo"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Tipo</div>
                            <div class="val" x-text="solActual && solActual.tipo_apoyo"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Monto máximo</div>
                            <div class="val" x-text="solActual && solActual.monto_maximo ? '$' + Number(solActual.monto_maximo).toLocaleString('es-MX') : 'N/A'"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Fecha de solicitud</div>
                            <div class="val" x-text="solActual && formatFecha(solActual.fecha_creacion)"></div>
                        </div>
                    </div>

                    {{-- Documentos --}}
                    <p class="section-title">Documentos del expediente</p>
                    <template x-if="solActual && solActual.documentos && solActual.documentos.length > 0">
                        <div class="overflow-x-auto rounded-xl border border-slate-200">
                            <table class="docs-table">
                                <thead>
                                    <tr>
                                        <th>Documento</th>
                                        <th>Estado validación</th>
                                        <th>Revisión admin</th>
                                        <th>Observaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template x-for="doc in solActual.documentos" :key="doc.id_doc">
                                        <tr>
                                            <td x-text="doc.nombre_documento"></td>
                                            <td>
                                                <span :class="doc.estado_validacion === 'Correcto' ? 'doc-ok' : doc.estado_validacion === 'Incorrecto' ? 'doc-rej' : 'doc-pen'"
                                                      x-text="doc.estado_validacion || 'Pendiente'"></span>
                                            </td>
                                            <td>
                                                <span :class="doc.admin_status === 'aceptado' ? 'doc-ok' : doc.admin_status === 'rechazado' ? 'doc-rej' : 'doc-pen'"
                                                      x-text="doc.admin_status || 'Pendiente'"></span>
                                            </td>
                                            <td class="text-slate-500 text-xs" x-text="doc.observaciones_revision || '—'"></td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </template>
                    <template x-if="!solActual || !solActual.documentos || solActual.documentos.length === 0">
                        <p class="text-sm text-slate-400 italic">Sin documentos registrados.</p>
                    </template>

                    {{-- Firma --}}
                    <div class="border-t border-dashed border-slate-200 mt-5 pt-5">
                        <p class="section-title">Firma directiva</p>
                        <p class="text-sm text-slate-500 mb-3">Ingresa tu contraseña para firmar y aprobar esta solicitud. Esta acción no se puede deshacer.</p>
                        <form :action="'{{ route('solicitudes.proceso.firma-directiva') }}'" method="POST">
                            @csrf
                            <input type="hidden" name="folio" :value="solActual && solActual.folio"/>
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-widest mb-2">Contraseña <span class="text-red-500">*</span></label>
                                <input type="password" name="password"
                                       placeholder="Ingresa tu contraseña"
                                       class="w-full px-4 py-2.5 border-2 border-slate-200 rounded-xl text-sm focus:border-blue-500 focus:outline-none"
                                       required/>
                            </div>
                            <button type="submit" class="btn-firma">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                                Firmar y aprobar solicitud
                            </button>
                        </form>
                    </div>

                </div>
            </div>
        </div>

    </div>

    <script>
        function directivoPanel(data) {
            return {
                solicitudes: data,
                solActual: null,
                modalAbierto: false,
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
                        return new Date(fecha).toLocaleDateString('es-MX', { day:'2-digit', month:'short', year:'numeric' });
                    } catch { return fecha; }
                }
            }
        }
    </script>

</x-app-layout>