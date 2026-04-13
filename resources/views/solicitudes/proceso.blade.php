<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Proceso de Cierre y Validación - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100 flex flex-col">
        @include('layouts.navigation')

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proceso de Cierre y Validacion</h2>
                    <div class="flex gap-2">
                        <a href="{{ route('solicitudes.padron.export', ['format' => 'csv']) }}" class="px-3 py-2 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700">Exportar CSV</a>
                        <a href="{{ route('solicitudes.padron.export', ['format' => 'xls']) }}" class="px-3 py-2 rounded-lg text-sm font-semibold bg-sky-700 text-white hover:bg-sky-800">Exportar XLS</a>
                    </div>
                </div>
            </div>
        </header>

        <main class="flex-1">
            <div class="py-10">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-emerald-800 text-sm">{{ session('status') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-red-700 text-sm">{{ session('error') }}</div>
            @endif

            @foreach($solicitudes as $solicitud)
                <div class="bg-white shadow-sm rounded-2xl border border-gray-200 overflow-hidden">
                    <div class="p-5 border-b border-gray-100">
                        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-2">
                            <div>
                                <h3 class="text-lg font-extrabold text-slate-900">Folio #{{ $solicitud->folio }} - {{ $solicitud->nombre_apoyo }}</h3>
                                <p class="text-sm text-slate-600">
                                    Beneficiario: {{ trim($solicitud->nombre . ' ' . $solicitud->apellido_paterno . ' ' . $solicitud->apellido_materno) }}
                                    | Estado: {{ $solicitud->estado ?? 'Sin estado' }}
                                </p>
                                @if(!empty($solicitud->cuv))
                                    <p class="text-xs text-slate-500 mt-1">CUV: <span class="font-semibold">{{ $solicitud->cuv }}</span></p>
                                @endif
                            </div>
                            <div class="text-xs text-slate-500">Hito actual: <span class="font-semibold text-slate-700">{{ $solicitud->hito_actual->clave_hito }}</span></div>
                        </div>
                    </div>

                    <div class="grid lg:grid-cols-2 gap-0">
                        <div class="p-5 border-r border-gray-100">
                            <h4 class="font-semibold text-slate-900 mb-4">Timeline de hitos</h4>
                            <div class="space-y-8">
                                @foreach($solicitud->timeline as $hito)
                                    @php
                                        $isCurrent = $hito['status'] === 'current';
                                        $isCompleted = $hito['status'] === 'completed';
                                        $isFuture = $hito['status'] === 'future';
                                    @endphp
                                    <div class="relative pl-8">
                                        <span class="absolute left-0 top-1.5 inline-flex h-4 w-4 rounded-full {{ $isCompleted ? 'bg-green-500' : ($isCurrent ? 'bg-blue-500 animate-pulse' : 'bg-gray-300') }}"></span>
                                        <div class="text-sm font-semibold {{ $isCompleted ? 'text-green-600' : ($isCurrent ? 'text-blue-600' : 'text-gray-500') }}">{{ $hito['clave_hito'] }}</div>
                                        <div class="text-xs text-slate-500">{{ $hito['nombre_hito'] }}</div>
                                        <div class="text-xs text-slate-400">{{ $hito['fecha_inicio'] ?: 'Sin inicio' }} - {{ $hito['fecha_fin'] ?: 'Sin fin' }}</div>
                                    </div>
                                @endforeach
                            </div>
                        </div>

                        <div class="p-5 space-y-6 bg-slate-50">
                            @php
                                // Flujo secuencial de FASES - cada una depende de la anterior
                                
                                // FASE 1: Revisión administrativa
                                // ✓ Completa SOLO cuando: presupuesto_confirmado = 1
                                $fase1Completada = (bool) $solicitud->presupuesto_confirmado;
                                
                                // FASE 2: Firma directiva (genera CUV)
                                // ✓ Completa SOLO cuando: cuv ≠ NULL (requiere Fase 1 completa)
                                $fase2Completada = !is_null($solicitud->cuv) && $fase1Completada;
                                
                                // FASE 3: Cierre financiero
                                // ✓ Completa SOLO cuando: monto_entregado ≠ NULL (requiere Fase 2 completa)
                                $fase3Completada = !is_null($solicitud->monto_entregado) && $fase2Completada;
                                
                                // Fase activa = primera no completada
                                $faseActiva = !$fase1Completada ? 1 : (!$fase2Completada ? 2 : 3);
                                
                                // DEBUG: descomentar para ver estado en browser console
                                // @if(false) console.log({!! json_encode([
                                //     'folio' => $solicitud->folio,
                                //     'presupuesto' => $solicitud->presupuesto_confirmado,
                                //     'cuv' => $solicitud->cuv,
                                //     'monto' => $solicitud->monto_entregado,
                                //     'f1' => $fase1Completada,
                                //     'f2' => $fase2Completada,
                                //     'f3' => $fase3Completada,
                                // ]) !!}); @endif
                            @endphp

                            <!-- FASE 1: REVISIÓN ADMINISTRATIVA -->
                            <div x-data="{ expanded: {{ $faseActiva === 1 ? 'true' : 'false' }} }" class="border-l-4 {{ $fase1Completada ? 'border-green-500 bg-green-50' : ($faseActiva === 1 ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50') }}">
                                <div class="p-3 cursor-pointer hover:bg-opacity-75 transition" @click="expanded = !expanded">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-semibold text-slate-900">
                                            @if($fase1Completada)
                                                <span class="text-green-600">✓ Fase 1: Revisión administrativa</span>
                                            @else
                                                <span class="text-blue-600">◉ Fase 1: Revisión administrativa</span>
                                            @endif
                                        </h4>
                                        <span x-show="!expanded" class="text-sm text-slate-500">Expandir</span>
                                        <span x-show="expanded" class="text-sm text-slate-500">Contraer</span>
                                    </div>
                                </div>

                                <div x-show="expanded" class="p-3 border-t border-gray-300">
                                    @if($faseActiva === 1 || $fase1Completada)
                                        <form @submit.prevent="submitRevisarDocumento($el)" class="space-y-2" x-data="{ 
                                            async submitRevisarDocumento(form) {
                                                const formData = new FormData(form);
                                                try {
                                                    console.log('📤 Enviando formulario...');
                                                    const response = await fetch('{{ route('solicitudes.proceso.revisar-documento') }}', {
                                                        method: 'POST',
                                                        headers: {
                                                            'Accept': 'application/json',
                                                        },
                                                        body: formData
                                                    });
                                                    
                                                    console.log('📥 Status:', response.status);
                                                    
                                                    let data;
                                                    try {
                                                        data = await response.json();
                                                    } catch(e) {
                                                        console.error('❌ JSON Parse Error:', e);
                                                        data = { exito: false, mensaje: 'Error al procesar respuesta del servidor' };
                                                    }
                                                    
                                                    console.log('📦 Respuesta:', data);
                                                    
                                                    if (!response.ok) {
                                                        const mensaje = data?.mensaje || 'Error en el servidor (Status: ' + response.status + ')';
                                                        console.error('Error completo:', data);
                                                        alert('❌ Error: ' + mensaje);
                                                        return;
                                                    }
                                                    
                                                    if (!data?.exito) {
                                                        const mensaje = data?.mensaje || 'Error desconocido';
                                                        alert('❌ Error: ' + mensaje);
                                                        return;
                                                    }
                                                    
                                                    // Mostrar éxito
                                                    alert('✅ ' + data.mensaje);
                                                    // Limpiar formulario
                                                    form.reset();
                                                    // Si Fase 1 se completó, recargar
                                                    if (data.fase1_completada) {
                                                        alert('🔄 Recargando página para mostrar Fase 2...');
                                                        setTimeout(() => location.reload(), 800);
                                                    }
                                                } catch (error) {
                                                    console.error('🔥 Error completo:', error);
                                                    alert('🔥 Error: ' + (error.message || 'Error desconocido'));
                                                }
                                            }
                                        }">
                                            @csrf
                                            <input type="number" name="id_documento" placeholder="ID documento" class="w-full rounded-lg border-gray-300 text-sm" required>
                                            <select name="accion" class="w-full rounded-lg border-gray-300 text-sm" required>
                                                <option value="aprobar">Aprobar</option>
                                                <option value="observar">Observar</option>
                                                <option value="rechazar">Rechazar</option>
                                            </select>
                                            <textarea name="observaciones" rows="2" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Observaciones"></textarea>
                                            <div class="flex items-center gap-2 text-xs text-slate-600">
                                                <input type="hidden" name="permite_correcciones" value="0">
                                                <input id="corr-{{ $solicitud->folio }}" type="checkbox" name="permite_correcciones" value="1" class="rounded border-gray-300" checked>
                                                <label for="corr-{{ $solicitud->folio }}">Permite correcciones</label>
                                            </div>
                                            <input type="text" name="webview_link" placeholder="Google Drive webViewLink (opcional)" class="w-full rounded-lg border-gray-300 text-sm">
                                            <input type="text" name="source_file_id" placeholder="source_file_id (opcional)" class="w-full rounded-lg border-gray-300 text-sm">
                                            <input type="text" name="official_file_id" placeholder="official_file_id - expediente oficial (opcional)" class="w-full rounded-lg border-gray-300 text-sm">
                                            <button type="submit" class="w-full rounded-lg bg-slate-900 text-white py-2 text-sm font-semibold hover:bg-slate-800 transition">Guardar revision</button>
                                        </form>
                                    @else
                                        <p class="text-sm text-slate-600 text-center py-4">⏸ Completa la Fase 1 para continuar</p>
                                    @endif
                                </div>
                            </div>

                            <!-- FASE 2: FIRMA DIRECTIVA -->
                            <div x-data="{ expanded: {{ $faseActiva === 2 ? 'true' : 'false' }} }" class="border-l-4 {{ $fase2Completada ? 'border-green-500 bg-green-50' : ($faseActiva === 2 ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50') }}">
                                <div class="p-3 cursor-pointer hover:bg-opacity-75 transition" @click="expanded = !expanded">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-semibold text-slate-900">
                                            @if($fase2Completada)
                                                <span class="text-green-600">✓ Fase 2: Firma directiva (CUV: {{ $solicitud->cuv ?? 'N/A' }})</span>
                                            @elseif($faseActiva === 2)
                                                <span class="text-blue-600">◉ Fase 2: Firma directiva</span>
                                            @else
                                                <span class="text-gray-500">◯ Fase 2: Firma directiva (Bloqueada)</span>
                                            @endif
                                        </h4>
                                        @if($faseActiva === 2 || $fase2Completada)
                                            <span x-show="!expanded" class="text-sm text-slate-500">Expandir</span>
                                            <span x-show="expanded" class="text-sm text-slate-500">Contraer</span>
                                        @endif
                                    </div>
                                </div>

                                <div x-show="expanded" class="p-3 border-t border-gray-300">
                                    @if($faseActiva === 2 || $fase2Completada)
                                        @if($fase2Completada)
                                            <div class="p-4 bg-green-50 border border-green-200 rounded-lg text-center">
                                                <p class="text-sm text-green-800 font-semibold">✓ Solicitud firmada correctamente</p>
                                                <p class="text-xs text-green-700 mt-1">CUV: {{ $solicitud->cuv }}</p>
                                            </div>
                                        @else
                                            <a href="{{ route('solicitudes.firma.show', ['folio' => $solicitud->folio]) }}" class="w-full block rounded-lg bg-blue-700 hover:bg-blue-800 text-white py-3 text-sm font-semibold transition text-center">
                                                🖊️ Proceder a Firmar Solicitud
                                            </a>
                                            <p class="text-xs text-slate-600 text-center mt-2">Haz click para revisar y firmar la solicitud con la nueva interfaz</p>
                                        @endif
                                    @else
                                        <p class="text-sm text-slate-600 text-center py-4">⏸ Completa la Fase 1 para continuar</p>
                                    @endif
                                </div>
                            </div>

                            <!-- FASE 3: CIERRE FINANCIERO -->
                            <div x-data="{ expanded: {{ $faseActiva === 3 ? 'true' : 'false' }} }" class="border-l-4 {{ $fase3Completada ? 'border-green-500 bg-green-50' : ($faseActiva === 3 ? 'border-blue-500 bg-blue-50' : 'border-gray-300 bg-gray-50') }}">
                                <div class="p-3 cursor-pointer hover:bg-opacity-75 transition" @click="expanded = !expanded">
                                    <div class="flex items-center justify-between">
                                        <h4 class="font-semibold text-slate-900">
                                            @if($fase3Completada)
                                                <span class="text-green-600">✓ Fase 3: Cierre financiero</span>
                                            @elseif($faseActiva === 3)
                                                <span class="text-blue-600">◉ Fase 3: Cierre financiero</span>
                                            @else
                                                <span class="text-gray-500">◯ Fase 3: Cierre financiero (Bloqueada)</span>
                                            @endif
                                        </h4>
                                        @if($faseActiva === 3 || $fase3Completada)
                                            <span x-show="!expanded" class="text-sm text-slate-500">Expandir</span>
                                            <span x-show="expanded" class="text-sm text-slate-500">Contraer</span>
                                        @endif
                                    </div>
                                </div>

                                <div x-show="expanded" class="p-3 border-t border-gray-300">
                                    @if($faseActiva === 3 || $fase3Completada)
                                        <form method="POST" action="{{ route('solicitudes.proceso.cierre-financiero') }}" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="folio" value="{{ $solicitud->folio }}">
                                            <input type="number" step="0.01" min="0" name="monto_entregado" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Monto entregado" required>
                                            <input type="date" name="fecha_entrega_recurso" class="w-full rounded-lg border-gray-300 text-sm" required>
                                            <input type="text" name="ruta_pdf_final" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Ruta PDF final con QR">
                                            <button {{ $fase3Completada ? 'disabled' : '' }} class="w-full rounded-lg {{ $fase3Completada ? 'bg-gray-400 cursor-not-allowed' : 'bg-emerald-700 hover:bg-emerald-800' }} text-white py-2 text-sm font-semibold transition">
                                                {{ $fase3Completada ? '✓ Solicitud Cerrada' : 'Cerrar solicitud' }}
                                            </button>
                                        </form>
                                    @else
                                        <p class="text-sm text-slate-600 text-center py-4">⏸ Completa la Fase 2 para continuar</p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
            </div>
            </div>
        </main>

        <x-site-footer class="mt-16" />
    </div>
</body>
</html>
