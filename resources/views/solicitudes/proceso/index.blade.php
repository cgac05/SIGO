@extends('layouts.app')

@section('title', 'Bandeja de Solicitudes')

@section('content')
<div class="min-h-screen bg-gray-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- ========== HEADER ========== -->
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900">📋 Bandeja de Solicitudes</h1>
            <p class="text-gray-600 mt-2">
                Gestiona y autoriza solicitudes de beneficiarios
            </p>
        </div>

        <!-- ========== ALERTAS ========== -->
        @if ($errors->any())
            <div class="mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-lg">
                <p class="text-red-900 font-bold">❌ Error</p>
                @foreach ($errors->all() as $error)
                    <p class="text-red-700 text-sm mt-1">{{ $error }}</p>
                @endforeach
            </div>
        @endif

        @if (session('success'))
            <div class="mb-6 bg-green-50 border-l-4 border-green-500 p-4 rounded-lg">
                <p class="text-green-900 font-bold">✓ Éxito</p>
                <p class="text-green-700 text-sm mt-1">{{ session('success') }}</p>
            </div>
        @endif

        <!-- ========== ESTADÍSTICAS RÁPIDAS ========== -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-yellow-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Pendientes de Firma</p>
                        <p class="text-4xl font-bold text-yellow-600 mt-2">{{ $stats['pendientes'] }}</p>
                    </div>
                    <div class="text-5xl">⏳</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-green-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Firmadas</p>
                        <p class="text-4xl font-bold text-green-600 mt-2">{{ $stats['firmadas'] }}</p>
                    </div>
                    <div class="text-5xl">✓</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-blue-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Aprobadas Hoy</p>
                        <p class="text-4xl font-bold text-blue-600 mt-2">{{ $stats['aprobadas_hoy'] }}</p>
                    </div>
                    <div class="text-5xl">📊</div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6 border-l-4 border-red-500">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-semibold">Rechazadas Hoy</p>
                        <p class="text-4xl font-bold text-red-600 mt-2">{{ $stats['rechazadas_hoy'] }}</p>
                    </div>
                    <div class="text-5xl">✗</div>
                </div>
            </div>
        </div>

        <!-- ========== FILTROS & BÚSQUEDA ========== -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <form method="GET" action="{{ route('solicitudes.proceso.index') }}" class="flex gap-4 items-end">
                <!-- Folio -->
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Buscar por Folio</label>
                    <input type="number" 
                           name="folio" 
                           value="{{ request('folio') }}" 
                           class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ej: 1234">
                </div>

                <!-- Estado -->
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Estado</label>
                    <select name="estado" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos</option>
                        <option value="ANALISIS_ADMIN" {{ request('estado') === 'ANALISIS_ADMIN' ? 'selected' : '' }}>En Análisis</option>
                        <option value="DOCUMENTOS_VERIFICADOS" {{ request('estado') === 'DOCUMENTOS_VERIFICADOS' ? 'selected' : '' }}>Pendiente Firma</option>
                        <option value="APROBADA" {{ request('estado') === 'APROBADA' ? 'selected' : '' }}>Aprobada</option>
                        <option value="RECHAZADA" {{ request('estado') === 'RECHAZADA' ? 'selected' : '' }}>Rechazada</option>
                    </select>
                </div>

                <!-- Apoyo -->
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Apoyo</label>
                    <select name="apoyo" class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">Todos los apoyos</option>
                        @foreach($apoyosDisponibles as $apoyo)
                            <option value="{{ $apoyo->id_apoyo }}" {{ request('apoyo') === (string)$apoyo->id_apoyo ? 'selected' : '' }}>
                                {{ $apoyo->nombre_apoyo }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Beneficiario -->
                <div class="flex-1">
                    <label class="block text-sm font-semibold text-gray-700 mb-2">Beneficiario</label>
                    <input type="text" 
                           name="beneficiario" 
                           value="{{ request('beneficiario') }}" 
                           class="w-full rounded-lg border border-gray-300 px-4 py-2 text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                           placeholder="Ej: Juan Pérez">
                </div>

                <!-- Botones -->
                <div class="flex gap-2">
                    <button type="submit" class="rounded-lg bg-blue-700 text-white px-4 py-2 font-semibold hover:bg-blue-800 transition whitespace-nowrap">
                        🔎 Buscar
                    </button>
                    <a href="{{ route('solicitudes.proceso.index') }}" class="rounded-lg bg-gray-300 text-gray-900 px-4 py-2 font-semibold hover:bg-gray-400 transition whitespace-nowrap">
                        Limpiar
                    </a>
                </div>
            </form>
        </div>

        <!-- ========== MENÚ DE SOLICITUDES (TABS) ========== -->
        <div class="mb-6 bg-white rounded-lg shadow border-b border-slate-200">
            <div class="flex">
                <!-- Tab: Pendientes -->
                <a href="{{ route('solicitudes.proceso.index', array_merge(request()->query(), ['tab' => 'pendientes'])) }}" 
                   class="flex-1 px-6 py-4 font-semibold text-center {{ $tabActual === 'pendientes' ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50' : 'text-slate-600 hover:text-slate-900' }} transition-colors">
                    ⏳ Pendientes de Firma
                    <span class="ml-2 inline-block px-2 py-1 rounded-full bg-yellow-100 text-yellow-800 text-xs font-bold">
                        {{ $stats['pendientes'] }}
                    </span>
                </a>

                <!-- Tab: Firmadas -->
                <a href="{{ route('solicitudes.proceso.index', array_merge(request()->query(), ['tab' => 'firmadas'])) }}" 
                   class="flex-1 px-6 py-4 font-semibold text-center {{ $tabActual === 'firmadas' ? 'text-green-600 border-b-2 border-green-600 bg-green-50' : 'text-slate-600 hover:text-slate-900' }} transition-colors">
                    ✓ Firmadas
                    <span class="ml-2 inline-block px-2 py-1 rounded-full bg-green-100 text-green-800 text-xs font-bold">
                        {{ $stats['firmadas'] }}
                    </span>
                </a>
            </div>
        </div>

        <!-- ========== MENÚ DE SOLICITUDES (CARDS) ========== -->
        <div>
            <h2 class="text-lg font-semibold text-gray-900 mb-4">
                {{ $tabActual === 'pendientes' ? '⏳ Pendientes de Firma' : '✓ Solicitudes Firmadas' }} 
                <span class="text-sm font-normal text-gray-600">({{ $solicitudes->total() }} total)</span>
            </h2>

            @if($solicitudes->count() > 0)
                <div class="grid grid-cols-1 gap-4">
                    @foreach($solicitudes as $sol)
                        <a href="{{ route('solicitudes.proceso.show', $sol->folio) }}" 
                           class="group rounded-lg border border-slate-200 bg-white hover:border-blue-400 hover:shadow-md transition-all duration-200 overflow-hidden">
                            <div class="px-6 py-4 flex items-start justify-between">
                                <div class="flex-1">
                                    <!-- Header: Folio & Timestamp -->
                                    <div class="flex items-center gap-3 mb-3">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                            Folio {{ $sol->folio }}
                                        </span>
                                        <span class="text-xs font-medium text-slate-500">
                                            {{ \Carbon\Carbon::parse($sol->fecha_creacion)->format('d/m/Y H:i') }}
                                        </span>
                                        
                                        <!-- Estado Badge -->
                                        @if($sol->nombre_estado === 'APROBADA')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-green-100 text-green-800 ml-auto">✓ Aprobada</span>
                                        @elseif($sol->nombre_estado === 'RECHAZADA')
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-red-100 text-red-800 ml-auto">✗ Rechazada</span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 ml-auto">⏳ Pendiente</span>
                                        @endif
                                    </div>

                                    <!-- Grid 3 columnas: Beneficiario, Apoyo, Monto -->
                                    <div class="grid grid-cols-3 gap-6">
                                        <!-- Beneficiario -->
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Beneficiario</p>
                                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ $sol->beneficiario_nombre }}</p>
                                        </div>

                                        <!-- Apoyo -->
                                        <div>
                                            <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Apoyo</p>
                                            <p class="text-sm font-semibold text-slate-900 mt-1">{{ $sol->nombre_apoyo }}</p>
                                        </div>

                                        <!-- Monto o CUV -->
                                        <div>
                                            @if($tabActual === 'firmadas' && $sol->cuv)
                                                <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">CUV</p>
                                                <p class="text-sm font-mono font-bold text-green-600 mt-1">{{ $sol->cuv }}</p>
                                            @else
                                                <p class="text-xs text-slate-500 uppercase tracking-wide font-semibold">Monto</p>
                                                <p class="text-sm font-bold text-green-600 mt-1">${{ number_format($sol->monto_maximo ?? 0, 2) }}</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Flecha -->
                                <div class="ml-4 flex items-center">
                                    <svg class="h-5 w-5 text-slate-400 group-hover:text-blue-400 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                    </svg>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                <!-- PAGINACIÓN -->
                <div class="mt-6">
                    {{ $solicitudes->links() }}
                </div>
            @else
                <div class="rounded-lg border border-slate-200 bg-white p-12">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h3 class="mt-2 text-sm font-medium text-slate-900">No hay solicitudes</h3>
                        <p class="mt-1 text-sm text-slate-500">No se encontraron solicitudes con los filtros aplicados.</p>
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection
