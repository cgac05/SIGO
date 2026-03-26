@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-slate-50">
    <!-- Header -->
    <div class="bg-white shadow-sm border-b border-slate-200 sticky top-0 z-40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Verificación de Documentos</h1>
                    <p class="text-sm text-slate-500 mt-1">Revisa y aprueba los documentos de los beneficiarios</p>
                </div>
                <div class="flex items-center gap-4">
                    <div class="grid grid-cols-3 gap-3">
                        <div class="text-center px-3 py-2 bg-amber-50 border border-amber-200 rounded-lg">
                            <p class="text-2xl font-bold text-amber-700">{{ $stats['pendientes'] ?? 0 }}</p>
                            <p class="text-xs text-amber-600 font-medium">Pendientes</p>
                        </div>
                        <div class="text-center px-3 py-2 bg-green-50 border border-green-200 rounded-lg">
                            <p class="text-2xl font-bold text-green-700">{{ $stats['aceptados'] ?? 0 }}</p>
                            <p class="text-xs text-green-600 font-medium">Aceptados</p>
                        </div>
                        <div class="text-center px-3 py-2 bg-red-50 border border-red-200 rounded-lg">
                            <p class="text-2xl font-bold text-red-700">{{ $stats['rechazados'] ?? 0 }}</p>
                            <p class="text-xs text-red-600 font-medium">Rechazados</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filtro por Apoyo -->
            @if(count($apoyosFiltros) > 0)
            <div class="mt-4 flex items-center gap-2">
                <label class="text-sm font-medium text-slate-600">Filtrar por Apoyo:</label>
                <select id="apoyoFilter" class="px-3 py-2 border border-slate-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos los apoyos</option>
                    @foreach($apoyosFiltros as $id => $nombre)
                    <option value="?apoyo={{ $id }}" {{ $apoyoFilter == $id ? 'selected' : '' }}>{{ $nombre }}</option>
                    @endforeach
                </select>
            </div>
            @endif
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if(count($solicitudesPendientes) === 0)
        <div class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-slate-900">No hay solicitudes pendientes</h3>
            <p class="mt-1 text-sm text-slate-500">Todos los documentos han sido verificados.</p>
        </div>
        @else
        <div class="grid grid-cols-1 gap-4">
            @foreach($solicitudesPendientes as $item)
            <a href="{{ route('admin.solicitudes.show', ['folio' => $item['solicitud']->folio, 'apoyo' => $apoyoFilter]) }}" 
               class="group rounded-lg border border-slate-200 bg-white hover:border-blue-400 hover:shadow-md transition-all duration-200 overflow-hidden">
                <div class="px-6 py-4 flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                Folio {{ $item['solicitud']->folio }}
                            </span>
                            <span class="text-xs font-medium text-slate-500">
                                {{ $item['solicitud']->fecha_creacion?->format('d/m/Y H:i') }}
                            </span>
                        </div>
                        
                        <div class="mt-2 grid grid-cols-3 gap-4">
                            <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">Beneficiario</p>
                                <p class="text-sm font-semibold text-slate-900">
                                    {{ $item['solicitud']->beneficiario->nombre }} 
                                    {{ $item['solicitud']->beneficiario->apellido_paterno }}
                                </p>
                                <p class="text-xs text-slate-600">{{ $item['solicitud']->beneficiario->curp }}</p>
                            </div>
                            
                            <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">Apoyo</p>
                                <p class="text-sm font-semibold text-slate-900">{{ $item['apoyo']->nombre_apoyo }}</p>
                            </div>
                            
                            <div>
                                <p class="text-xs text-slate-500 uppercase tracking-wide">Documentos Pendientes</p>
                                <p class="text-sm font-bold text-amber-600">{{ count($item['documentos']) }}</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="ml-4 flex items-center">
                        <svg class="h-5 w-5 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                </div>
            </a>
            @endforeach
        </div>
        @endif
    </div>
</div>

<script>
document.getElementById('apoyoFilter').addEventListener('change', function(e) {
    if(this.value) {
        window.location.href = this.value;
    } else {
        window.location.href = '{{ route("admin.solicitudes.index") }}';
    }
});
</script>
@endsection
