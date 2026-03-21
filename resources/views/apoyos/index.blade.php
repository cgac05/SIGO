@php
    $isBeneficiario = $user && $user->isBeneficiario();
    $isAdmin = $user && $user->personal && (int) $user->personal->fk_rol === 1;
    $isDirector = $user && $user->personal && (int) $user->personal->fk_rol === 2;
    $canEdit = $isAdmin || $isDirector;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Apoyos</h2>
    </x-slot>

    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto max-w-[1500px] px-4 py-6 md:px-6">
            <section class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 md:p-6">
                <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between mb-5">
                    <div>
                        <h3 class="text-xl font-extrabold text-slate-800">Listado de apoyos</h3>
                        @if($isBeneficiario)
                            <p class="text-sm text-slate-500">Explora los apoyos disponibles y abre cada convocatoria para ver su detalle completo.</p>
                        @else
                            <p class="text-sm text-slate-500">Administra convocatorias y abre su vista detallada en una ventana dedicada.</p>
                        @endif
                    </div>
                    @if($canEdit)
                        <a href="{{ route('apoyos.create') }}" class="inline-flex items-center justify-center rounded-lg bg-slate-900 text-white px-4 py-2 text-sm font-semibold hover:bg-slate-700 transition">
                            + Nuevo apoyo
                        </a>
                    @endif
                </div>

                @if($isBeneficiario)
                    <div class="mb-5 bg-slate-100 rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-bold text-slate-700 mb-2">Mis solicitudes recientes</h4>
                        @if($misSolicitudes->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-slate-500 text-left">
                                            <th class="py-2 pr-4">Folio</th>
                                            <th class="py-2 pr-4">Apoyo</th>
                                            <th class="py-2 pr-4">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($misSolicitudes as $solicitud)
                                            <tr class="border-b border-slate-100 text-slate-700">
                                                <td class="py-2 pr-4 font-semibold">{{ $solicitud->folio }}</td>
                                                <td class="py-2 pr-4">{{ $solicitud->nombre_apoyo ?? 'Sin nombre' }}</td>
                                                <td class="py-2 pr-4">{{ $solicitud->estado ?? 'Pendiente' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">Aun no registras solicitudes.</p>
                        @endif
                    </div>
                @elseif($canEdit)
                    <div class="mb-5 bg-slate-100 rounded-xl border border-slate-200 p-4">
                        <h4 class="text-sm font-bold text-slate-700 mb-2">Solicitudes recibidas (ultimas 12)</h4>
                        @if($solicitudesRecientes->count() > 0)
                            <div class="overflow-x-auto">
                                <table class="min-w-full text-sm">
                                    <thead>
                                        <tr class="border-b border-slate-200 text-slate-500 text-left">
                                            <th class="py-2 pr-4">Folio</th>
                                            <th class="py-2 pr-4">CURP</th>
                                            <th class="py-2 pr-4">Apoyo</th>
                                            <th class="py-2 pr-4">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($solicitudesRecientes as $solicitud)
                                            <tr class="border-b border-slate-100 text-slate-700">
                                                <td class="py-2 pr-4 font-semibold">{{ $solicitud->folio }}</td>
                                                <td class="py-2 pr-4">{{ $solicitud->fk_curp }}</td>
                                                <td class="py-2 pr-4">{{ $solicitud->nombre_apoyo ?? 'Sin nombre' }}</td>
                                                <td class="py-2 pr-4">{{ $solicitud->estado ?? 'Pendiente' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @else
                            <p class="text-sm text-slate-500">No hay solicitudes registradas todavia.</p>
                        @endif
                    </div>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4 gap-4">
                    @forelse($apoyos as $apoyo)
                        <article class="rounded-xl border border-slate-200 overflow-hidden hover:shadow-md transition bg-white">
                            @if(!empty($apoyo->foto_url))
                                <img src="{{ $apoyo->foto_url }}" alt="{{ $apoyo->nombre_apoyo }}" class="w-full h-44 object-cover">
                            @else
                                <div class="h-44 bg-slate-200"></div>
                            @endif
                            <div class="p-4">
                                <div class="flex items-start justify-between gap-2">
                                    <h4 class="font-bold text-slate-800">{{ $apoyo->nombre_apoyo }}</h4>
                                    <span class="text-[10px] font-bold rounded-full px-2 py-1 {{ $apoyo->tipo_apoyo === 'Económico' ? 'bg-amber-100 text-amber-700' : 'bg-emerald-100 text-emerald-700' }}">{{ $apoyo->tipo_apoyo }}</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-1">Vigencia: {{ $apoyo->fechaInicio ?? '-' }} al {{ $apoyo->fechafin ?? '-' }}</p>
                                @if($isBeneficiario && !empty($apoyo->solicitud_activa))
                                    <p class="mt-2 text-[11px] text-blue-700 font-semibold">Tu solicitud está en proceso ({{ $apoyo->solicitud_activa->estado }})</p>
                                @endif
                                <a href="{{ route('apoyos.comments', $apoyo->id_apoyo) }}" class="mt-3 inline-flex w-full items-center justify-center rounded-lg bg-slate-900 text-white text-sm py-2 font-semibold hover:bg-slate-700 transition">
                                    Abrir detalle
                                </a>
                            </div>
                        </article>
                    @empty
                        <div class="col-span-full rounded-xl border border-dashed border-slate-300 text-slate-500 text-center py-10">
                            No hay apoyos disponibles por ahora.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
