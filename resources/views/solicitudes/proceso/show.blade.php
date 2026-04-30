@extends('layouts.app')

@section('title', 'Solicitud #' . $solicitud->folio)

@section('content')
<div class="min-h-screen bg-slate-50">
    <!-- STICKY HEADER (como admin) -->
    <div class="bg-white shadow-sm border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center gap-3">
                <a href="{{ route('solicitudes.proceso.index') }}" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                    <svg class="h-5 w-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Folio {{ $solicitud->folio }}</h1>
                    <p class="text-sm text-slate-500">{{ $apoyo->nombre_apoyo }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN CONTENT -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

        <!-- ========== ALERTAS ========== -->
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-900 font-bold">Error</p>
                @foreach ($errors->all() as $error)
                    <p class="text-red-700 text-sm mt-1">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg alert-success">
                <p class="text-green-900 font-bold">{{ session('success') }}</p>
            </div>
        @endif

        <!-- ========== GRID PRINCIPAL (2 COLS: 2/3 + 1/3) ========== -->
        <div class="grid grid-cols-3 gap-6">

            <!-- COLUMNA IZQUIERDA (2/3) - Información General, Documentos, Historial -->
            <div class="col-span-2 space-y-6">

                <!-- 1. INFORMACIÓN GENERAL -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6">Información General</h2>
                    
                    <div class="space-y-6">
                        <!-- Beneficiario con Foto -->
                        <div class="flex items-start gap-6 pb-6 border-b border-slate-200">
                            <!-- Foto del Beneficiario -->
                            <div class="flex-shrink-0">
                                <img 
                                    src="{{ $beneficiario->user?->getFotoUrl() ?? 'https://ui-avatars.com/api/?name=' . urlencode($beneficiario->nombre . ' ' . $beneficiario->apellido_paterno) . '&background=3b82f6&color=fff&size=120' }}"
                                    alt="{{ $beneficiario->nombre }}"
                                    class="w-24 h-24 rounded-full object-cover border-2 border-slate-200 shadow-md"
                                >
                            </div>
                            
                            <!-- Datos del Beneficiario -->
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide mb-2">Beneficiario</p>
                                <p class="text-2xl font-bold text-slate-900">{{ $beneficiario->nombre }} {{ $beneficiario->apellido_paterno }} {{ $beneficiario->apellido_materno }}</p>
                                <div class="mt-3 space-y-1">
                                    <p class="text-sm text-slate-600"><span class="font-semibold">CURP:</span> {{ $beneficiario->curp }}</p>
                                    <p class="text-sm text-slate-600"><span class="font-semibold">Edad:</span> {{ $beneficiario->edad ?? 'N/A' }} años</p>
                                    <p class="text-sm text-slate-600"><span class="font-semibold">Género:</span> {{ $beneficiario->sexo_label }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Resto de Información en Grid -->
                        <div class="grid grid-cols-2 gap-6">
                            <!-- Apoyo -->
                            <div>
                                <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Apoyo</p>
                                <p class="text-lg font-bold text-slate-900 mt-2">{{ $apoyo->nombre_apoyo }}</p>
                            </div>

                            <!-- Categoría del Apoyo -->
                            <div>
                                <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Categoría del Apoyo</p>
                                <p class="text-lg font-bold text-slate-900 mt-2">{{ $apoyo->categoria_nombre ?? 'Sin Categoría' }}</p>
                            </div>

                            <!-- Monto por Beneficiario -->
                            <div>
                                <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Monto por Beneficiario</p>
                                <p class="text-2xl font-bold text-green-600 mt-2">${{ number_format($apoyo->monto_maximo ?? 0, 2) }}</p>
                            </div>

                            <!-- Fecha Solicitud -->
                            <div>
                                <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Fecha Solicitud</p>
                                <p class="text-lg text-slate-900 mt-2">{{ \Carbon\Carbon::parse($solicitud->fecha_creacion)->format('d/m/Y H:i') }}</p>
                            </div>

                            <!-- Estado Actual -->
                            <div>
                                <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Estado</p>
                                @if($yaFirmada)
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-green-100 text-green-800 mt-2">Aprobada</span>
                                @elseif($yaRechazada)
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-red-100 text-red-800 mt-2">Rechazada</span>
                                @else
                                    <span class="inline-block px-3 py-1 rounded-full text-sm font-semibold bg-yellow-100 text-yellow-800 mt-2">En proceso</span>
                                @endif
                            </div>

                            <!-- CUV -->
                            <div>
                                <p class="text-sm font-semibold text-slate-600 uppercase tracking-wide">CUV</p>
                                @if($solicitud->cuv)
                                    <p class="text-sm font-mono text-slate-900 mt-2 bg-slate-100 px-3 py-1 rounded">{{ $solicitud->cuv }}</p>
                                @else
                                    <p class="text-sm text-slate-500 mt-2">— Por generar</p>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DOCUMENTOS ENVIADOS -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4">📄 Documentos Enviados</h2>
                    
                    @if($documentos->count() > 0)
                        <div class="space-y-3">
                            @foreach($documentos as $doc)
                                <div class="border border-slate-200 rounded-lg p-4 hover:bg-slate-50 transition">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-3 flex-1">
                                            <div class="text-2xl">
                                                @if(Str::endsWith($doc->ruta_archivo, '.pdf'))
                                                    📕
                                                @elseif(Str::endsWith($doc->ruta_archivo, ['.jpg', '.jpeg', '.png', '.gif']))
                                                    🖼️
                                                @else
                                                    📎
                                                @endif
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-semibold text-slate-900 truncate">{{ basename($doc->ruta_archivo) }}</p>
                                                <p class="text-xs text-slate-500">Documento {{ $doc->fk_id_tipo_doc }}</p>
                                            </div>
                                        </div>
                                        <div class="flex gap-2 ml-4">
                                            <a href="{{ route('documentos.view', ['path' => $doc->ruta_archivo]) }}" target="_blank" class="px-3 py-1 text-xs font-medium bg-blue-50 text-blue-700 border border-blue-200 rounded hover:bg-blue-100 transition">Ver</a>
                                            <a href="{{ route('documentos.download', ['path' => $doc->ruta_archivo]) }}" download class="px-3 py-1 text-xs font-medium bg-slate-100 text-slate-700 border border-slate-200 rounded hover:bg-slate-200 transition">Descargar</a>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 bg-slate-50 rounded">
                            <p class="text-slate-600">No hay documentos disponibles</p>
                        </div>
                    @endif
                </div>

                <!-- 3. HISTORIAL DE APOYOS PREVIOS -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-2xl font-bold text-slate-900 mb-4">📜 Historial de Apoyos</h2>
                    
                    @if($historialApoyos->count() > 0)
                        <div class="mb-4 p-3 bg-blue-50 rounded text-sm text-blue-900">
                            <strong>ℹ️</strong> Este beneficiario ha recibido <strong>{{ $totalApoyosPrevios }}</strong> apoyo(s) previo(s)
                        </div>
                        <div class="space-y-3">
                            @foreach($historialApoyos as $apoyo_prev)
                                <div class="border-l-4 border-green-500 bg-green-50 p-4 rounded">
                                    <div class="flex items-center justify-between">
                                        <div>
                                            <p class="font-semibold text-slate-900">{{ $apoyo_prev->nombre_apoyo }}</p>
                                            <p class="text-sm text-slate-600">
                                                Folio: <strong>#{{ $apoyo_prev->folio }}</strong> | 
                                                {{ \Carbon\Carbon::parse($apoyo_prev->fecha_creacion)->format('d/m/Y') }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <p class="font-bold text-green-600">${{ number_format($apoyo_prev->monto, 0) }}</p>
                                            <p class="text-xs text-slate-500 mt-1">{{ substr($apoyo_prev->cuv, 0, 16) }}...</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-center py-8 bg-slate-50 rounded">
                            <p class="text-slate-600">Primer apoyo para este beneficiario</p>
                        </div>
                    @endif
                </div>

            </div>

            <!-- COLUMNA DERECHA (1/3) - Presupuesto y Firma -->
            <div class="col-span-1 space-y-6">

                <!-- VALIDACIÓN PRESUPUESTARIA -->
                <div class="bg-white rounded-lg shadow p-6">
                    <h3 class="text-lg font-bold text-slate-900 mb-4">Presupuesto</h3>
                    
                    <div class="space-y-4">
                        @if($apoyo->tipo_apoyo === 'Especie')
                            <!-- Monto a Autorizar (Especie: Costo Unitario) -->
                            <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                                <p class="text-xs text-slate-700 font-semibold uppercase tracking-wide">Monto a Autorizar</p>
                                <p class="text-3xl font-bold text-blue-600 mt-2">${{ number_format($apoyo->costo_unitario ?? 0, 2) }}</p>
                                <p class="text-xs text-slate-600 mt-1">Precio unitario por pieza</p>
                            </div>

                            <!-- Disponible en Apoyo (Especie: Piezas Disponibles) -->
                            <div class="bg-slate-50 border border-slate-300 rounded-lg p-4">
                                <p class="text-xs text-slate-700 font-semibold uppercase tracking-wide">Disponible en Apoyo</p>
                                <p class="text-2xl font-bold text-slate-900 mt-1">{{ $apoyo->stock_actual ?? 0 }}</p>
                                <p class="text-xs text-slate-600 mt-1">Piezas disponibles restantes</p>
                                @if(($apoyo->stock_actual ?? 0) >= 1)
                                    <span class="inline-block mt-2 text-xs font-semibold text-green-600">Disponible</span>
                                @else
                                    <span class="inline-block mt-2 text-xs font-semibold text-red-600">SIN DISPONIBILIDAD</span>
                                @endif
                            </div>

                            <!-- Disponible en Categoría (Especie: Monto Total del Apoyo) -->
                            <div class="bg-slate-50 border border-slate-300 rounded-lg p-4">
                                <p class="text-xs text-slate-700 font-semibold uppercase tracking-wide">Disponible en Categoría</p>
                                <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format(($apoyo->stock_actual ?? 0) * ($apoyo->costo_unitario ?? 0), 2) }}</p>
                                <p class="text-xs text-slate-600 mt-1">Monto equivalente disponible</p>
                                @if(($apoyo->stock_actual ?? 0) >= 1)
                                    <span class="inline-block mt-2 text-xs font-semibold text-green-600">Suficiente</span>
                                @else
                                    <span class="inline-block mt-2 text-xs font-semibold text-red-600">INSUFICIENTE</span>
                                @endif
                            </div>
                        @else
                            <!-- Monto a Autorizar -->
                            <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-4">
                                <p class="text-xs text-slate-700 font-semibold uppercase tracking-wide">Monto a Autorizar</p>
                                <p class="text-3xl font-bold text-blue-600 mt-2">${{ number_format($apoyo->monto_maximo ?? 0, 2) }}</p>
                            </div>

                            <!-- Disponible en Apoyo -->
                            <div class="bg-slate-50 border border-slate-300 rounded-lg p-4">
                                <p class="text-xs text-slate-700 font-semibold uppercase tracking-wide">Disponible en Apoyo</p>
                                <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($disponibleEnApoyo, 2) }}</p>
                                <p class="text-xs text-slate-600 mt-1">De {{ number_format($totalNecesario, 2) }} total ({{ $apoyo->cupo_limite ?? 0 }} beneficiarios)</p>
                                @if($disponibleEnApoyo > 0)
                                    <span class="inline-block mt-2 text-xs font-semibold text-green-600">Disponible</span>
                                @else
                                    <span class="inline-block mt-2 text-xs font-semibold text-red-600">SIN DISPONIBILIDAD</span>
                                @endif
                            </div>

                            <!-- Disponible en Categoría -->
                            <div class="bg-slate-50 border border-slate-300 rounded-lg p-4">
                                <p class="text-xs text-slate-700 font-semibold uppercase tracking-wide">Disponible en Categoría</p>
                                <p class="text-2xl font-bold text-slate-900 mt-1">${{ number_format($presupuestoCategoriaDisponible, 0) }}</p>
                                @if($presupuestoCategoriaDisponible >= ($apoyo->monto_maximo ?? 0))
                                    <span class="inline-block mt-2 text-xs font-semibold text-green-600">Suficiente</span>
                                @else
                                    <span class="inline-block mt-2 text-xs font-semibold text-red-600">INSUFICIENTE</span>
                                @endif
                            </div>
                        @endif

                        <!-- VEREDICTO FINAL -->
                        <div class="mt-6 pt-4 border-t border-slate-200">
                            @if($puedeAprobarse)
                                <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded">
                                    <p class="text-green-900 font-bold">@if($apoyo->tipo_apoyo === 'Especie') OK INVENTARIO @else OK PRESUPUESTO @endif</p>
                                    <p class="text-xs text-green-700 mt-1">Disponible para aprobar</p>
                                </div>
                            @else
                                <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                                    <p class="text-red-900 font-bold">INSUFICIENTE</p>
                                    <p class="text-xs text-red-700 mt-1">No se puede autorizar</p>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- ACCIONES (FASE 2: FIRMA) -->
                @if(in_array($estadoActual->nombre_estado, ['DOCS_VERIFICADOS', 'DOCUMENTOS_VERIFICADOS', 'Aprobado', 'APROBADA']))
                    <div class="bg-white rounded-lg shadow p-6">
                        <h3 class="text-lg font-bold text-slate-900 mb-4">Fase 2: Firma</h3>
                        
                        @if($procesada)
                            <!-- SOLICITUD YA PROCESADA -->
                            <div class="space-y-4">
                                @if($yaFirmada)
                                    <div class="bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                                        <p class="text-green-900 font-bold">Solicitud Firmada</p>
                                        <p class="text-sm text-green-800 mt-2">Esta solicitud ya fue firmada y tiene CUV generado. No se puede volver a firmar.</p>
                                        <div class="mt-3 p-3 bg-white border border-green-300 rounded">
                                            <p class="text-xs text-slate-600 font-semibold">CUV (Comprobante Único de Verificación)</p>
                                            <p class="text-lg font-bold text-green-700 mt-1">{{ $solicitud->cuv }}</p>
                                        </div>
                                    </div>
                                @elseif($yaRechazada)
                                    <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                                        <p class="text-red-900 font-bold">Solicitud Rechazada</p>
                                        <p class="text-sm text-red-800 mt-2">Esta solicitud fue rechazada. No se puede volver a procesar.</p>
                                    </div>
                                @endif
                            </div>
                        @elseif($puedeAprobarse)
                            @if($disponibleEnApoyo > 0)
                                <form action="{{ route('solicitudes.proceso.firmar', $solicitud->folio) }}" method="POST" class="space-y-4" id="formFirma">
                                    @csrf
                                    
                                    <!-- Botón Ver Resumen -->
                                    <button type="button" 
                                            class="w-full rounded-lg bg-slate-100 hover:bg-slate-200 text-slate-900 py-3 font-semibold transition border border-slate-300"
                                            onclick="document.getElementById('modalResumen').classList.remove('hidden')">
                                        👁️ Ver Resumen
                                    </button>

                                    <!-- Campo Contraseña -->
                                    <div>
                                        <label class="block text-sm font-semibold text-slate-700 mb-2">Contraseña</label>
                                        <input type="password" 
                                               name="password" 
                                               required
                                               class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:ring-2 focus:ring-green-500 focus:border-transparent"
                                               placeholder="Confirma tu contraseña">
                                        <p class="text-xs text-slate-500 mt-1">Tu contraseña es requerida para la firma</p>
                                    </div>

                                    <!-- Botón Firmar -->
                                    <button type="submit" 
                                            class="w-full rounded-lg bg-green-700 text-white px-6 py-3 font-bold hover:bg-green-800 transition text-lg">
                                        Firmar y Generar CUV
                                    </button>
                                </form>

                                <!-- SEPARADOR -->
                                <div class="relative my-6">
                                    <div class="absolute inset-0 flex items-center">
                                        <div class="w-full border-t border-slate-300"></div>
                                    </div>
                                    <div class="relative flex justify-center text-sm">
                                        <span class="px-3 bg-white text-slate-600 font-medium">O</span>
                                    </div>
                                </div>
                            @else
                                <!-- PRESUPUESTO AGOTADO: SOLO RECHAZAR -->
                                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-lg mb-6">
                                    <p class="text-amber-900 font-bold">Presupuesto Agotado</p>
                                    <p class="text-sm text-amber-800 mt-2">Se han aceptado todas las solicitudes permitidas para este apoyo. Ya no se pueden autorizar más beneficiarios. Solo es posible rechazar solicitudes.</p>
                                </div>
                            @endif

                            <!-- BOTÓN RECHAZAR (abre modal) -->
                            <button type="button" 
                                    class="w-full rounded-lg bg-red-700 text-white px-6 py-3 font-bold hover:bg-red-800 transition text-lg"
                                    onclick="document.getElementById('modalRechazo').classList.remove('hidden')">
                                Rechazar Solicitud
                            </button>

                            <!-- MODAL RECHAZO -->
                            <div id="modalRechazo" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                                <div class="bg-white rounded-lg max-w-2xl w-full p-8 max-h-96 overflow-y-auto">
                                    <h3 class="text-2xl font-bold text-red-900 mb-2">Rechazar Solicitud</h3>
                                    <p class="text-sm text-slate-600 mb-6">Si considera que la solicitud no cumple con los requisitos, puede rechazarla. Se enviará una notificación al beneficiario inmediatamente.</p>
                                    
                                    <form action="{{ route('solicitudes.proceso.rechazar', $solicitud->folio) }}" method="POST" class="space-y-4">
                                        @csrf

                                        <!-- Detalles de la Solicitud -->
                                        <div class="space-y-3 bg-slate-50 p-4 rounded-lg mb-4">
                                            <div class="grid grid-cols-2 gap-4">
                                                <div>
                                                    <p class="text-xs text-slate-600 font-semibold">Folio</p>
                                                    <p class="text-lg font-bold text-slate-900 mt-1">#{{ $solicitud->folio }}</p>
                                                </div>
                                                <div>
                                                    <p class="text-xs text-slate-600 font-semibold">Beneficiario</p>
                                                    <p class="text-lg font-bold text-slate-900 mt-1">{{ $beneficiario->nombre }}</p>
                                                </div>
                                            </div>
                                            <div>
                                                <p class="text-xs text-slate-600 font-semibold">Programa</p>
                                                <p class="text-sm font-bold text-slate-900 mt-1">{{ $apoyo->nombre_apoyo }}</p>
                                            </div>
                                        </div>

                                        <!-- Campo Motivo -->
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Motivo del Rechazo (Opcional)</label>
                                            <textarea name="motivo" 
                                                      rows="4"
                                                      class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                                      placeholder="Explica el motivo del rechazo. Este mensaje se incluirá en la notificación al beneficiario."></textarea>
                                            <p class="text-xs text-slate-500 mt-1">💡 Si no escribes un motivo, se enviarán motivos generales</p>
                                        </div>

                                        <!-- Campo Contraseña -->
                                        <div>
                                            <label class="block text-sm font-semibold text-slate-700 mb-2">Contraseña</label>
                                            <input type="password" 
                                                   name="password" 
                                                   required
                                                   class="w-full rounded-lg border border-slate-300 px-4 py-2 text-sm focus:ring-2 focus:ring-red-500 focus:border-transparent"
                                                   placeholder="Confirma tu contraseña">
                                            <p class="text-xs text-slate-500 mt-1">Requerida para confirmar el rechazo</p>
                                        </div>

                                        <!-- Advertencia -->
                                        <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                                            <p class="text-sm text-red-900 font-bold">Advertencia</p>
                                            <p class="text-xs text-red-700 mt-2">
                                                El rechazo es <strong>PERMANENTE</strong> y <strong>NO SE PUEDE DESHACER</strong>. 
                                                El beneficiario será notificado inmediatamente por correo. 
                                                Esta acción será registrada en auditoría.
                                            </p>
                                        </div>

                                        <!-- Botones de Acción -->
                                        <div class="flex gap-3 mt-6 pt-4 border-t border-slate-200">
                                            <button type="button" 
                                                    onclick="document.getElementById('modalRechazo').classList.add('hidden')"
                                                    class="flex-1 rounded-lg bg-slate-300 text-slate-900 px-4 py-2 font-semibold hover:bg-slate-400 transition">
                                                Cancelar
                                            </button>
                                            <button type="submit" 
                                                    class="flex-1 rounded-lg bg-red-700 text-white px-4 py-3 font-bold hover:bg-red-800 transition">
                                                Confirmar Rechazo
                                            </button>
                                        </div>
                                    </form>

                                    <!-- Cierre al hacer click fuera -->
                                    <script>
                                        document.getElementById('modalRechazo').addEventListener('click', function(e) {
                                            if (e.target === this) {
                                                this.classList.add('hidden');
                                            }
                                        });
                                    </script>
                                </div>
                            </div>

                            <!-- MODAL RESUMEN -->
                            <div id="modalResumen" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
                                <div class="bg-white rounded-lg max-w-2xl w-full p-8 max-h-96 overflow-y-auto">
                                    <h3 class="text-2xl font-bold text-slate-900 mb-6">Resumen de Autorización</h3>
                                    
                                    <div class="space-y-4">
                                        <div class="grid grid-cols-2 gap-4">
                                            <div class="bg-slate-50 p-3 rounded text-sm">
                                                <p class="text-slate-600 font-semibold">Folio</p>
                                                <p class="text-lg font-bold text-slate-900 mt-1">#{{ $solicitud->folio }}</p>
                                            </div>
                                            <div class="bg-slate-50 p-3 rounded text-sm">
                                                <p class="text-slate-600 font-semibold">Fecha</p>
                                                <p class="text-lg font-bold text-slate-900 mt-1">{{ now()->format('d/m/Y H:i') }}</p>
                                            </div>
                                        </div>

                                        <div class="bg-slate-50 p-3 rounded">
                                            <p class="text-sm text-slate-600 font-semibold">Beneficiario</p>
                                            <p class="text-lg font-bold text-slate-900 mt-1">
                                                {{ $beneficiario->nombre }} {{ $beneficiario->apellido_paterno }}
                                            </p>
                                            <p class="text-xs text-slate-600 mt-1">{{ $beneficiario->curp }}</p>
                                        </div>

                                        <div class="bg-slate-50 p-3 rounded">
                                            <p class="text-sm text-slate-600 font-semibold">Apoyo</p>
                                            <p class="text-lg font-bold text-slate-900 mt-1">{{ $apoyo->nombre_apoyo }}</p>
                                        </div>

                                        <div class="bg-green-50 border-2 border-green-500 p-4 rounded">
                                            <p class="text-sm text-slate-600 font-semibold">Monto Autorizado</p>
                                            <p class="text-3xl font-bold text-green-600 mt-1">${{ number_format($apoyo->monto_maximo ?? 0, 2) }}</p>
                                        </div>

                                        <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 rounded">
                                            <p class="text-sm text-yellow-900 font-bold">ADVERTENCIA</p>
                                            <p class="text-xs text-yellow-800 mt-2">
                                                Al firmar, está autorizando IRREVOCABLEMENTE el desembolso de 
                                                <strong>${{ number_format($apoyo->monto_maximo ?? 0, 2) }}</strong>. 
                                                Esta acción será auditada permanentemente.
                                            </p>
                                        </div>
                                    </div>

                                    <div class="flex gap-3 mt-6 pt-4 border-t border-slate-200">
                                        <button type="button" 
                                                onclick="document.getElementById('modalResumen').classList.add('hidden')"
                                                class="flex-1 rounded-lg bg-slate-300 text-slate-900 px-4 py-2 font-semibold hover:bg-slate-400 transition">
                                            Cerrar
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="bg-red-50 border-l-4 border-red-500 p-4 rounded">
                                <p class="text-red-900 font-bold">No Disponible</p>
                                <p class="text-xs text-red-700 mt-2">Presupuesto insuficiente para autorizar</p>
                            </div>
                        @endif
                    </div>
                @elseif(($estadoActual->nombre_estado === 'Aprobado' || $estadoActual->nombre_estado === 'APROBADA') && $solicitud->cuv)
                    <!-- FIRMADO EXITOSAMENTE -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="bg-green-50 border-l-4 border-green-500 p-6 rounded-lg">
                            <p class="text-green-900 font-bold text-lg">Solicitud Firmada Exitosamente</p>
                            <p class="text-sm text-green-700 mt-2">La solicitud ha sido aprobada y el CUV ha sido generado.</p>
                            
                            <div class="mt-6 pt-6 border-t border-green-300">
                                <p class="text-sm text-green-900 font-semibold mb-2">Código Único de Verificación (CUV)</p>
                                <p class="text-base font-mono text-green-800 bg-green-100 px-4 py-3 rounded font-bold break-all">
                                    {{ $solicitud->cuv }}
                                </p>
                            </div>
                        </div>
                    </div>
                @elseif($estadoActual->nombre_estado === 'Aprobado' || $estadoActual->nombre_estado === 'APROBADA')
                    <!-- APROBADO - HABILITADO PARA FIRMA -->
                    <div class="bg-white rounded-lg shadow p-6">
                        <div class="bg-amber-50 border-l-4 border-amber-500 p-6 rounded-lg">
                            <p class="text-amber-900 font-bold text-lg">Solicitud Aprobada - Lista para Firma</p>
                            <p class="text-sm text-amber-700 mt-2">La solicitud ha sido aprobada. Haga clic en "Firmar" para completar el proceso y generar el CUV.</p>
                        </div>
                    </div>
                @else
                    <div class="bg-blue-50 border border-blue-300 rounded-lg p-6">
                        <p class="text-blue-900 font-bold">Estado Actual</p>
                        <p class="text-sm text-blue-800 mt-2">
                            Esta solicitud está en estado <strong>{{ $estadoActual->nombre_estado }}</strong>.
                            La fase de firma se habilitará cuando se completen las verificaciones.
                        </p>
                    </div>
                @endif

            </div>

        </div>

    </div>

</div>

<script>
// Close modal on outside click
document.getElementById('modalResumen')?.addEventListener('click', function(e) {
    if (e.target === this) {
        this.classList.add('hidden');
    }
});

// Auto-close success alert
setTimeout(() => {
    const alert = document.querySelector('.alert-success');
    if (alert) {
        alert.style.display = 'none';
    }
}, 5000);
</script>
@endsection
