<!-- Panel Directivo -->
@php
    // Calcular estadísticas
    use Illuminate\Support\Facades\DB;
    
    $hoy = now()->startOfDay();
    
    // Pendientes de Firma
    $pendientesQuery = DB::table('Solicitudes')
        ->whereNull('Solicitudes.cuv')
        ->where('Solicitudes.fk_id_estado', '!=', 5)
        ->whereExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('Documentos_Expediente')
                ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
                ->where('Documentos_Expediente.admin_status', 'aceptado');
        })
        ->whereNotExists(function ($query) {
            $query->select(DB::raw(1))
                ->from('Documentos_Expediente')
                ->whereColumn('Documentos_Expediente.fk_folio', 'Solicitudes.folio')
                ->where('admin_status', '!=', 'aceptado')
                ->whereNotNull('admin_status');
        });
    
    $stats = [
        'pendientes' => $pendientesQuery->count(),
        'firmadas' => DB::table('Solicitudes')->whereNotNull('Solicitudes.cuv')->count(),
        'aprobadas_hoy' => DB::table('Solicitudes')
            ->where('Solicitudes.cuv', '!=', null)
            ->whereDate('Solicitudes.fecha_creacion', $hoy)
            ->count(),
        'rechazadas_hoy' => DB::table('Solicitudes')
            ->where('Solicitudes.fk_id_estado', 5)
            ->whereDate('Solicitudes.fecha_actualizacion', $hoy)
            ->count(),
    ];
@endphp

<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-r from-red-600 to-red-700 rounded-2xl p-8 text-white shadow-lg">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold">Panel Directivo</h2>
                <p class="mt-2 text-red-100 italic">Gestiona solicitudes, supervisa el proceso y autoriza aprobaciones</p>
            </div>
            <div class="hidden lg:block">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-16 w-16 opacity-30" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
        </div>
    </div>

    <!-- ESTADÍSTICAS RÁPIDAS -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-yellow-500">
            <p class="text-sm text-slate-600">Pendientes de Firma</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['pendientes'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-green-500">
            <p class="text-sm text-slate-600">Firmadas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['firmadas'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-blue-500">
            <p class="text-sm text-slate-600">Aprobadas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['aprobadas_hoy'] }}</p>
        </div>
        <div class="bg-white rounded-lg shadow p-4 border-l-4 border-red-500">
            <p class="text-sm text-slate-600">Rechazadas</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $stats['rechazadas_hoy'] }}</p>
        </div>
    </div>

    <!-- Accesos Rápidos Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        
        <!-- Solicitudes -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-red-500 transition-all duration-300">
            <div class="h-12 w-12 bg-red-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m7 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Solicitudes</h3>
            <p class="text-sm text-slate-500 mt-2">Gestiona y autoriza solicitudes de beneficiarios. Revisa documentos y firmas digitales.</p>
            <a href="{{ route('solicitudes.proceso.index') }}" class="mt-4 inline-block w-full text-center py-2 bg-red-600 text-white rounded-lg font-semibold hover:bg-red-700 shadow-sm transition-colors">
                Acceder
            </a>
        </div>

        <!-- Ciclos -->
        <div class="bg-white p-6 rounded-xl shadow-sm border border-slate-100 hover:border-red-500 transition-all duration-300">
            <div class="h-12 w-12 bg-slate-100 rounded-lg flex items-center justify-center mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 text-slate-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
            </div>
            <h3 class="font-bold text-slate-800 text-lg">Ciclos Presupuestarios</h3>
            <p class="text-sm text-slate-500 mt-2">Administra los ciclos de presupuesto. Consulta disponibilidad y límites de apoyos.</p>
            <a href="/admin/ciclos/1" class="mt-4 inline-block w-full text-center py-2 border border-red-600 text-red-600 rounded-lg font-semibold hover:bg-red-50 transition-colors">
                Ver Ciclos
            </a>
        </div>

    </div>
</div>