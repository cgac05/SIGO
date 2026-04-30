<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Panel de Recursos Financieros
        </h2>
    </x-slot>

    <link href="https://fonts.bunny.net/css?family=sora:400,600,700,800&display=swap" rel="stylesheet"/>

    <style>
        :root { --navy:#0f2044; --blue:#1a4a8a; --light:#eef3fb; --green:#16a34a; }
        body { font-family:'Sora',sans-serif; }

        /* HERO */
        .hero-panel {
            background: linear-gradient(135deg, #064e3b 0%, #065f46 50%, #059669 100%);
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
            background:#064e3b; color:#fff;
            font-size:.72rem; font-weight:700; text-transform:uppercase;
            letter-spacing:.06em; padding:.75rem 1rem; text-align:left;
        }
        .sol-table td { padding:.75rem 1rem; font-size:.85rem; color:#1e293b; border-bottom:1px solid #e2e8f0; }
        .sol-table tr:hover td { background:#f0fdf4; cursor:pointer; }

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
            background:linear-gradient(135deg,#064e3b,#059669);
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
            font-size:.72rem; font-weight:700; color:#065f46;
            text-transform:uppercase; letter-spacing:.08em;
            padding-bottom:.4rem; border-bottom:2px solid #d1fae5; margin-bottom:.85rem;
        }

        /* FORM */
        .field-group { display:flex; flex-direction:column; gap:.35rem; margin-bottom:1rem; }
        .field-label { font-size:.75rem; font-weight:700; color:#475569; text-transform:uppercase; letter-spacing:.05em; }
        .field-label span { color:#ef4444; }
        .field-input {
            width:100%; padding:.65rem 1rem; border:1.5px solid #cbd5e1; border-radius:10px;
            font-size:.9rem; color:#1e293b; outline:none; background:#f8fafc;
            transition:border-color .2s, box-shadow .2s; box-sizing:border-box;
        }
        .field-input:focus { border-color:#059669; box-shadow:0 0 0 3px rgba(5,150,105,.12); background:#fff; }

        /* BOTONES */
        .btn-cierre {
            width:100%; padding:.9rem;
            background:linear-gradient(135deg,#064e3b,#059669);
            color:#fff; border:none; border-radius:12px;
            font-size:.95rem; font-weight:700; cursor:pointer;
            display:flex; align-items:center; justify-content:center; gap:.5rem;
            transition:opacity .2s, transform .15s; margin-top:1.25rem;
        }
        .btn-cierre:hover { opacity:.9; transform:translateY(-1px); }

        /* TABS */
        .tab-btn {
            padding:.5rem 1.25rem; border-radius:8px; font-size:.82rem; font-weight:600;
            cursor:pointer; border:none; transition:all .2s;
        }
        .tab-btn.active { background:#064e3b; color:#fff; }
        .tab-btn.inactive { background:#f1f5f9; color:#64748b; }
        .tab-btn.inactive:hover { background:#e2e8f0; }

        /* EMPTY */
        .empty-state { text-align:center; padding:4rem 2rem; color:#64748b; }

        /* CONFIRM MODAL */
        .confirm-overlay {
            position:fixed; inset:0; z-index:70;
            background:rgba(10,20,50,.65); backdrop-filter:blur(4px);
            display:flex; align-items:center; justify-content:center; padding:1rem;
            opacity:0; pointer-events:none; transition:opacity .25s;
        }
        .confirm-overlay.open { opacity:1; pointer-events:all; }
        .confirm-box {
            background:#fff; border-radius:20px; width:100%; max-width:420px;
            padding:2rem; text-align:center;
            box-shadow:0 24px 64px rgba(10,20,50,.30);
            transform:scale(.95); transition:transform .25s;
        }
        .confirm-overlay.open .confirm-box { transform:scale(1); }
    </style>

    {{-- HERO --}}
    <div class="hero-panel py-8 px-4 md:px-8">
        <div class="hero-dots"></div>
        <div class="max-w-7xl mx-auto relative z-10">
            <div class="flex items-center gap-3 mb-1">
                <svg class="w-6 h-6 text-green-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-green-300 font-semibold text-sm uppercase tracking-widest">Recursos Financieros</span>
            </div>
            <div class="flex items-center justify-between mb-1">
                <h1 class="text-3xl font-extrabold text-white">Panel de Cierre Financiero</h1>
                <a href="{{ route('finanzas.historial') }}" class="bg-white/20 hover:bg-white/30 text-white px-4 py-2 rounded-lg text-sm font-semibold transition ml-4 border border-white/30">
                    Ver Historial y Comprobantes
                </a>
            </div>
            <p class="text-green-100 text-sm">Solicitudes firmadas por el directivo pendientes de cierre financiero y registro de pago.</p>
            <div class="mt-4 inline-flex items-center gap-2 bg-white/10 border border-white/20 text-white text-xs font-medium rounded-full px-4 py-1.5">
                <span class="w-2 h-2 rounded-full bg-green-300 inline-block"></span>
                {{ $solicitudes->count() }} solicitud(es) pendiente(s) de cierre
            </div>
        </div>
    </div>

    {{-- CONTENIDO --}}
    <div class="py-10 px-4 md:px-8 bg-gray-50 min-h-screen"
         x-data="financieroPanel({{ json_encode($solicitudes->values()) }})">

        {{-- Alertas --}}
        @if(session('exito'))
        <div class="max-w-7xl mx-auto mb-5">
            <div class="bg-green-50 border border-green-200 text-green-800 rounded-xl px-5 py-3 flex items-center justify-between gap-3 font-semibold text-sm">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                    </svg>
                    <span>{{ session('exito') }}</span>
                </div>
                @if(session('comprobante_url'))
                    <a href="{{ session('comprobante_url') }}" target="_blank" class="bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg text-xs transition whitespace-nowrap shadow-sm">
                        Generar Comprobante PDF
                    </a>
                @endif
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
                    <p class="text-lg font-semibold text-gray-500 mb-1">No hay solicitudes pendientes de cierre financiero</p>
                    <p class="text-sm text-gray-400">Cuando el directivo firme solicitudes aparecerán aquí.</p>
                </div>
            </template>

            {{-- Tabla --}}
            <template x-if="solicitudes.length > 0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-5 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-extrabold text-slate-800">Solicitudes pendientes de cierre financiero</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Haz clic en una fila para registrar el pago o entrega</p>
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
                                    <th>Monto máx.</th>
                                    <th>CUV</th>
                                    <th>Fecha firma directivo</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="sol in solicitudesFiltradas" :key="sol.folio">
                                    <tr @click="abrirModal(sol)">
                                        <td class="font-bold text-emerald-700" x-text="'#' + sol.folio"></td>
                                        <td x-text="sol.nombre + ' ' + sol.apellido_paterno + ' ' + sol.apellido_materno"></td>
                                        <td x-text="sol.nombre_apoyo"></td>
                                        <td>
                                            <span class="badge" :class="sol.tipo_apoyo === 'Económico' ? 'badge-eco' : 'badge-esp'" x-text="sol.tipo_apoyo"></span>
                                        </td>
                                        <td x-text="sol.monto_maximo ? '$' + Number(sol.monto_maximo).toLocaleString('es-MX') : 'N/A'"></td>
                                        <td>
                                            <span class="badge badge-cuv" x-text="sol.cuv ? sol.cuv.substring(0,12) + '...' : '—'"></span>
                                        </td>
                                        <td x-text="formatFecha(sol.fecha_firma_directivo || sol.fecha_creacion)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </template>

        </div>

        {{-- MODAL DE DETALLE + FORMULARIO --}}
        <div class="modal-overlay" :class="{ open: modalAbierto }" @click.self="cerrarModal()">
            <div class="modal-box">

                <div class="modal-header">
                    <div>
                        <p class="text-green-200 text-xs font-semibold uppercase tracking-widest mb-1">Cierre Financiero</p>
                        <h2 class="text-white text-xl font-extrabold" x-text="'Folio #' + (solActual && solActual.folio)"></h2>
                        <p class="text-green-200 text-sm mt-1" x-text="solActual && solActual.nombre_apoyo"></p>
                    </div>
                    <button class="modal-close" @click="cerrarModal()">✕</button>
                </div>

                <div class="modal-body">

                    {{-- Tipo de apoyo --}}
                    <div class="mb-4 flex items-center gap-3">
                        <span class="badge" :class="solActual && solActual.tipo_apoyo === 'Económico' ? 'badge-eco' : 'badge-esp'" x-text="solActual && solActual.tipo_apoyo"></span>
                        <span class="badge badge-cuv" x-text="'CUV: ' + (solActual && solActual.cuv)"></span>
                    </div>

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
                            <div class="lbl">Monto máximo autorizado</div>
                            <div class="val" x-text="solActual && solActual.monto_maximo ? '$' + Number(solActual.monto_maximo).toLocaleString('es-MX', {minimumFractionDigits:2}) : 'N/A'"></div>
                        </div>
                        <div class="info-cell">
                            <div class="lbl">Fecha de solicitud</div>
                            <div class="val" x-text="solActual && formatFecha(solActual.fecha_creacion)"></div>
                        </div>
                    </div>

                    <div class="border-t border-dashed border-slate-200 my-4"></div>

                    {{-- Formulario de cierre --}}
                    <p class="section-title">
                        <span x-text="solActual && solActual.tipo_apoyo === 'Económico' ? 'Registro de pago' : 'Registro de entrega en especie'"></span>
                    </p>

                    <form :action="'{{ route('finanzas.cierre') }}'" method="POST" id="formCierre">
                        @csrf
                        <input type="hidden" name="folio" :value="solActual && solActual.folio"/>

                        <template x-if="solActual && solActual.tipo_apoyo === 'Económico'">
                            <div class="info-grid">
                                <div class="field-group">
                                    <label class="field-label">Monto entregado ($) <span>*</span></label>
                                    <input type="number" name="monto_entregado" step="0.01" min="0"
                                           :max="solActual && solActual.monto_maximo"
                                           :value="solActual && solActual.monto_maximo"
                                           placeholder="0.00"
                                           class="field-input bg-slate-100 cursor-not-allowed text-slate-500" 
                                           readonly
                                           :required="solActual && solActual.tipo_apoyo === 'Económico'"/>
                                </div>
                                <div class="field-group">
                                    <label class="field-label">Fecha de entrega <span>*</span></label>
                                    <input type="date" name="fecha_entrega"
                                           :max="new Date().toISOString().split('T')[0]"
                                           class="field-input" :required="solActual && solActual.tipo_apoyo === 'Económico'"/>
                                </div>
                            </div>
                        </template>

                        <template x-if="solActual && solActual.tipo_apoyo === 'Económico'">
                            <div class="field-group">
                                <label class="field-label">Folio de cheque</label>
                                <input type="text" name="folio_cheque"
                                       placeholder="Ej. 1234567"
                                       pattern="^[0-9]+$"
                                       title="El folio del cheque debe contener únicamente números"
                                       class="field-input"/>
                            </div>
                        </template>

                        <template x-if="solActual && solActual.tipo_apoyo === 'Especie'">
                            <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-4">
                                <h4 class="text-xs font-bold text-green-800 uppercase tracking-wide mb-3">Requisitos de salida</h4>
                                
                                <label class="flex items-start gap-3 cursor-pointer mb-3">
                                    <input type="checkbox" required class="mt-1 w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500">
                                    <span class="text-sm text-gray-700 font-medium">Confirmar que el material y/o apoyo en especie fue entregado al beneficiario.</span>
                                </label>
                                
                                <label class="flex items-start gap-3 cursor-pointer">
                                    <input type="checkbox" required class="mt-1 w-4 h-4 text-green-600 rounded border-gray-300 focus:ring-green-500">
                                    <span class="text-sm text-gray-700 font-medium">Registrar oficialmente la salida y descuento de inventario.</span>
                                </label>

                                <input type="hidden" name="monto_entregado" :value="solActual && solActual.monto_maximo ? solActual.monto_maximo : 0">
                                <input type="hidden" name="fecha_entrega" :value="new Date().toISOString().split('T')[0]">
                            </div>
                        </template>



                        <div class="field-group">
                            <label class="field-label">Observaciones</label>
                            <textarea name="observaciones" rows="2"
                                      placeholder="Notas adicionales sobre la entrega..."
                                      class="field-input" style="resize:vertical"></textarea>
                        </div>

                        <button type="button" class="btn-cierre" @click="abrirConfirmacion()">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="solActual && solActual.tipo_apoyo === 'Económico' ? 'Confirmar registro de pago' : 'Confirmar entrega en especie'"></span>
                        </button>
                    </form>

                </div>
            </div>
        </div>

        {{-- MODAL CONFIRMACIÓN --}}
        <div class="confirm-overlay" :class="{ open: confirmarAbierto }">
            <div class="confirm-box">
                <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4" style="background:#d1fae5">
                    <svg class="w-8 h-8 text-green-700" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <h3 class="text-xl font-extrabold mb-2" style="color:#064e3b">¿Confirmar cierre financiero?</h3>
                <p class="text-sm text-gray-500 mb-5">
                    Estás a punto de registrar el cierre financiero del folio
                    <strong class="text-gray-800" x-text="'#' + (solActual && solActual.folio)"></strong>
                    a nombre de <strong class="text-gray-800" x-text="solActual && (solActual.nombre + ' ' + solActual.apellido_paterno)"></strong>.
                    Esta acción es <strong>irreversible</strong>.
                </p>
                <div class="flex gap-3">
                    <button type="button"
                            class="flex-1 py-2.5 rounded-xl border border-gray-300 text-gray-600 font-semibold text-sm hover:bg-gray-50 transition"
                            @click="confirmarAbierto = false">
                        Cancelar
                    </button>
                    <button type="button"
                            class="flex-1 py-2.5 rounded-xl text-white font-bold text-sm transition"
                            style="background:#064e3b"
                            @click="enviarCierre()">
                        Sí, confirmar
                    </button>
                </div>
            </div>
        </div>

    </div>

    <script>
        function financieroPanel(data) {
            return {
                solicitudes: data,
                solActual: null,
                modalAbierto: false,
                confirmarAbierto: false,
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
                    this.confirmarAbierto = false;
                    document.body.style.overflow = '';
                },
                abrirConfirmacion() {
                    const form = document.getElementById('formCierre');
                    if (!form.checkValidity()) {
                        form.reportValidity();
                        return;
                    }
                    this.confirmarAbierto = true;
                },
                enviarCierre() {
                    document.getElementById('formCierre').submit();
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