@php
    $isBeneficiario = $user && $user->isBeneficiario();
    $isAdmin = $user && $user->personal && (int) $user->personal->fk_rol === 1;
    $isDirector = $user && $user->personal && (int) $user->personal->fk_rol === 2;
    $canEdit = $isAdmin || $isDirector;
    $apoyosCount = count($apoyos);
    $solicitudesRelacionadasCount = $isBeneficiario
        ? $apoyos->whereNotNull('solicitud_activa')->count()
        : $solicitudesRecientes->count();
    $panelLabel = $isBeneficiario
        ? 'Panel del beneficiario'
        : ($isAdmin
            ? 'Panel administrativo'
            : ($isDirector ? 'Panel directivo' : 'Vista general'));
    $panelSubtitle = $isBeneficiario
        ? 'Explora los apoyos disponibles y revisa el seguimiento de tus solicitudes desde una sola pantalla.'
        : 'Administra convocatorias y revisa solicitudes recientes desde una vista unificada.';
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Apoyos - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <main>
            <div class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-sky-50">
                <div class="mx-auto max-w-[1500px] px-4 py-6 md:px-6">
                    <section class="relative overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 px-6 py-7 text-white shadow-2xl md:px-8">
                        <div class="pointer-events-none absolute inset-0">
                            <div class="absolute -top-20 right-[-5rem] h-64 w-64 rounded-full bg-sky-400/20 blur-3xl"></div>
                            <div class="absolute bottom-[-5rem] left-[-4rem] h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl"></div>
                        </div>

                        <div class="relative flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                            <div class="max-w-3xl">
                                <p class="text-xs font-bold uppercase tracking-[0.35em] text-sky-200">{{ $panelLabel }}</p>
                                <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">📚 Apoyos</h1>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                                    {{ $panelSubtitle }}
                                </p>
                                <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-white ring-1 ring-white/20">{{ $apoyosCount }} apoyos visibles</span>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-white ring-1 ring-white/20">{{ $solicitudesRelacionadasCount }} solicitudes relacionadas</span>
                                    <span class="rounded-full bg-white/10 px-3 py-1 text-white ring-1 ring-white/20">
                                        {{ $isBeneficiario ? 'Acceso beneficiario' : ($canEdit ? 'Gestión interna' : 'Acceso general') }}
                                    </span>
                                </div>
                            </div>

                            <div class="flex flex-wrap gap-3">
                                @if($isBeneficiario)
                                    <a href="{{ route('solicitudes.historial') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100">
                                        📑 Mis Solicitudes
                                    </a>
                                @endif

                                @if($canEdit)
                                    <a href="{{ route('apoyos.create') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100">
                                        ➕ Nuevo apoyo
                                    </a>
                                @endif

                                <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                                    Volver al dashboard
                                </a>
                            </div>
                        </div>
                    </section>

                    <div class="mt-8">
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
                                        <p class="text-xs text-slate-500 mt-1">Vigencia: {{ $apoyo->fecha_inicio ?? '-' }} al {{ $apoyo->fecha_fin ?? '-' }}</p>
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
            </div>
        </main>
        <x-site-footer class="mt-16" />
    </div>
</body>
</html>
