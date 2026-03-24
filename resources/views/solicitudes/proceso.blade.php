<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">Proceso de Cierre y Validacion</h2>
            <div class="flex gap-2">
                <a href="{{ route('solicitudes.padron.export', ['format' => 'csv']) }}" class="px-3 py-2 rounded-lg text-sm font-semibold bg-emerald-600 text-white hover:bg-emerald-700">Exportar CSV</a>
                <a href="{{ route('solicitudes.padron.export', ['format' => 'xls']) }}" class="px-3 py-2 rounded-lg text-sm font-semibold bg-sky-700 text-white hover:bg-sky-800">Exportar XLS</a>
            </div>
        </div>
    </x-slot>

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
                            <div>
                                <h4 class="font-semibold text-slate-900 mb-2">Fase 1: Revision administrativa</h4>
                                <form method="POST" action="{{ route('solicitudes.proceso.revisar-documento') }}" class="space-y-2">
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
                                    <input type="url" name="webview_link" placeholder="Google Drive webViewLink" class="w-full rounded-lg border-gray-300 text-sm">
                                    <input type="text" name="source_file_id" placeholder="source_file_id" class="w-full rounded-lg border-gray-300 text-sm">
                                    <input type="text" name="official_file_id" placeholder="official_file_id (expediente oficial)" class="w-full rounded-lg border-gray-300 text-sm">
                                    <button class="w-full rounded-lg bg-slate-900 text-white py-2 text-sm font-semibold">Guardar revision</button>
                                </form>
                            </div>

                            <div>
                                <h4 class="font-semibold text-slate-900 mb-2">Fase 2: Firma directiva</h4>
                                <form method="POST" action="{{ route('solicitudes.proceso.firma-directiva') }}" class="space-y-2">
                                    @csrf
                                    <input type="hidden" name="folio" value="{{ $solicitud->folio }}">
                                    <input type="password" name="password" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Confirmar contrasena" required>
                                    <button class="w-full rounded-lg bg-blue-700 text-white py-2 text-sm font-semibold">Firmar y generar CUV</button>
                                </form>
                            </div>

                            <div>
                                <h4 class="font-semibold text-slate-900 mb-2">Fase 3: Cierre financiero</h4>
                                <form method="POST" action="{{ route('solicitudes.proceso.cierre-financiero') }}" class="space-y-2">
                                    @csrf
                                    <input type="hidden" name="folio" value="{{ $solicitud->folio }}">
                                    <input type="number" step="0.01" min="0" name="monto_entregado" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Monto entregado" required>
                                    <input type="date" name="fecha_entrega_recurso" class="w-full rounded-lg border-gray-300 text-sm" required>
                                    <input type="text" name="ruta_pdf_final" class="w-full rounded-lg border-gray-300 text-sm" placeholder="Ruta PDF final con QR">
                                    <button class="w-full rounded-lg bg-emerald-700 text-white py-2 text-sm font-semibold">Cerrar solicitud</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
