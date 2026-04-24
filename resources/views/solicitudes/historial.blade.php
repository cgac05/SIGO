@extends('layouts.app')

@section('title', 'Mis Solicitudes')

@section('content')
@php($estadoActual = $estadoFiltro ?? 'total')

<div class="relative overflow-hidden bg-gradient-to-br from-slate-50 via-white to-emerald-50">
    <div class="pointer-events-none absolute inset-0">
        <div class="absolute -top-24 right-[-6rem] h-72 w-72 rounded-full bg-emerald-200/40 blur-3xl"></div>
        <div class="absolute bottom-8 left-[-5rem] h-80 w-80 rounded-full bg-amber-200/30 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8 lg:py-10">
        <section class="rounded-3xl border border-slate-200 bg-slate-900 px-6 py-7 text-white shadow-2xl sm:px-8">
            <div class="flex flex-col gap-6 lg:flex-row lg:items-end lg:justify-between">
                <div class="max-w-3xl">
                    <p class="text-xs font-bold uppercase tracking-[0.35em] text-emerald-300">Panel personal</p>
                    <h1 class="mt-2 text-3xl font-black tracking-tight sm:text-4xl">📑 Mis Solicitudes</h1>
                    <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-300 sm:text-base">
                        Consulta el estado, los montos y la información completa de cada solicitud registrada a tu nombre.
                    </p>
                    <div class="mt-5 flex flex-wrap gap-2 text-xs font-semibold">
                        <span class="rounded-full bg-emerald-500/15 px-3 py-1 text-emerald-200 ring-1 ring-emerald-400/30">Verde: aprobada</span>
                        <span class="rounded-full bg-amber-500/15 px-3 py-1 text-amber-200 ring-1 ring-amber-400/30">Amarillo: en proceso</span>
                        <span class="rounded-full bg-rose-500/15 px-3 py-1 text-rose-200 ring-1 ring-rose-400/30">Rojo: rechazada</span>
                    </div>
                </div>

                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('solicitudes.registrar') }}" class="inline-flex items-center justify-center rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-slate-900 shadow-lg transition hover:bg-slate-100">
                        ➕ Nueva Solicitud
                    </a>
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-white/20 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-white/10">
                        Volver al dashboard
                    </a>
                </div>
            </div>
        </section>

        <section class="mt-8 space-y-3">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h2 class="text-xl font-black text-slate-900">Filtra por estado</h2>
                    <p class="mt-1 text-sm text-slate-600">Pulsa un recuadro para mostrar solo las solicitudes de ese estado. Total vuelve a mostrar todas.</p>
                </div>
                <div class="inline-flex items-center gap-2 rounded-full bg-white/80 px-3 py-2 text-sm font-semibold text-slate-700 ring-1 ring-slate-200">
                    <span class="text-slate-500">Filtro activo:</span>
                    <span>{{ $estadoFiltroEtiqueta ?? 'Todas las solicitudes' }}</span>
                </div>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('solicitudes.historial') }}" aria-current="{{ $estadoActual === 'total' ? 'page' : 'false' }}" class="group rounded-2xl border p-5 shadow-sm backdrop-blur transition duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $estadoActual === 'total' ? 'border-slate-900 bg-slate-900 text-white ring-2 ring-slate-900' : 'border-slate-200 bg-white/90 text-slate-900' }}">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] {{ $estadoActual === 'total' ? 'text-slate-300' : 'text-slate-500' }}">Total</p>
                    <p class="mt-3 text-3xl font-black {{ $estadoActual === 'total' ? 'text-white' : 'text-slate-900' }}">{{ $resumen['total'] ?? 0 }}</p>
                    <p class="mt-2 text-sm {{ $estadoActual === 'total' ? 'text-slate-200' : 'text-slate-600' }}">Ver todas mis solicitudes</p>
                </a>

                <a href="{{ route('solicitudes.historial', ['estado' => 'aprobada']) }}" aria-current="{{ $estadoActual === 'aprobada' ? 'page' : 'false' }}" class="group rounded-2xl border p-5 shadow-sm backdrop-blur transition duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $estadoActual === 'aprobada' ? 'border-emerald-600 bg-emerald-600 text-white ring-2 ring-emerald-500' : 'border-emerald-200 bg-emerald-50/90 text-slate-900' }}">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] {{ $estadoActual === 'aprobada' ? 'text-emerald-100' : 'text-emerald-700' }}">Aprobadas</p>
                    <p class="mt-3 text-3xl font-black {{ $estadoActual === 'aprobada' ? 'text-white' : 'text-emerald-900' }}">{{ $resumen['aprobadas'] ?? 0 }}</p>
                    <p class="mt-2 text-sm {{ $estadoActual === 'aprobada' ? 'text-emerald-50' : 'text-emerald-800' }}">Solo solicitudes aprobadas</p>
                </a>

                <a href="{{ route('solicitudes.historial', ['estado' => 'proceso']) }}" aria-current="{{ $estadoActual === 'proceso' ? 'page' : 'false' }}" class="group rounded-2xl border p-5 shadow-sm backdrop-blur transition duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $estadoActual === 'proceso' ? 'border-amber-500 bg-amber-500 text-white ring-2 ring-amber-400' : 'border-amber-200 bg-amber-50/90 text-slate-900' }}">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] {{ $estadoActual === 'proceso' ? 'text-amber-100' : 'text-amber-700' }}">En proceso</p>
                    <p class="mt-3 text-3xl font-black {{ $estadoActual === 'proceso' ? 'text-white' : 'text-amber-900' }}">{{ $resumen['proceso'] ?? 0 }}</p>
                    <p class="mt-2 text-sm {{ $estadoActual === 'proceso' ? 'text-amber-50' : 'text-amber-800' }}">En revisión o integración</p>
                </a>

                <a href="{{ route('solicitudes.historial', ['estado' => 'rechazada']) }}" aria-current="{{ $estadoActual === 'rechazada' ? 'page' : 'false' }}" class="group rounded-2xl border p-5 shadow-sm backdrop-blur transition duration-200 hover:-translate-y-0.5 hover:shadow-lg {{ $estadoActual === 'rechazada' ? 'border-rose-600 bg-rose-600 text-white ring-2 ring-rose-500' : 'border-rose-200 bg-rose-50/90 text-slate-900' }}">
                    <p class="text-xs font-bold uppercase tracking-[0.25em] {{ $estadoActual === 'rechazada' ? 'text-rose-100' : 'text-rose-700' }}">Rechazadas</p>
                    <p class="mt-3 text-3xl font-black {{ $estadoActual === 'rechazada' ? 'text-white' : 'text-rose-900' }}">{{ $resumen['rechazadas'] ?? 0 }}</p>
                    <p class="mt-2 text-sm {{ $estadoActual === 'rechazada' ? 'text-rose-50' : 'text-rose-800' }}">Solicitudes no aprobadas</p>
                </a>
            </div>
        </section>

        <div class="mt-4 flex flex-wrap items-center gap-2 text-sm text-slate-600">
            <span>Mostrando</span>
            <span class="rounded-full bg-white/80 px-3 py-1 font-semibold text-slate-800 ring-1 ring-slate-200">{{ $solicitudes->count() }}</span>
            <span>de</span>
            <span class="rounded-full bg-white/80 px-3 py-1 font-semibold text-slate-800 ring-1 ring-slate-200">{{ $resumen['total'] ?? 0 }}</span>
            <span>solicitudes.</span>
        </div>

        @if($solicitudes->isEmpty())
            <section class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white/90 px-6 py-12 text-center shadow-sm">
                <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-2xl bg-slate-100 text-3xl">📄</div>
                @if($estadoActual === 'total')
                    <h2 class="mt-4 text-2xl font-black text-slate-900">Aún no tienes solicitudes</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Cuando registres tu primera solicitud aparecerá aquí con su estado y seguimiento.
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('solicitudes.registrar') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Crear mi primera solicitud
                        </a>
                        <a href="{{ route('dashboard') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Volver al dashboard
                        </a>
                    </div>
                @else
                    <h2 class="mt-4 text-2xl font-black text-slate-900">No hay solicitudes en este filtro</h2>
                    <p class="mt-2 text-sm text-slate-600">
                        Prueba otro recuadro o regresa a Total para ver todo tu historial.
                    </p>
                    <div class="mt-6 flex flex-wrap justify-center gap-3">
                        <a href="{{ route('solicitudes.historial') }}" class="inline-flex items-center justify-center rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800">
                            Ver todas las solicitudes
                        </a>
                        <a href="{{ route('solicitudes.registrar') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                            Crear nueva solicitud
                        </a>
                    </div>
                @endif
            </section>
        @else
            <section class="mt-8 space-y-4">
                @foreach($solicitudes as $solicitud)
                    <article class="relative overflow-hidden rounded-3xl border shadow-lg transition-all duration-300 hover:-translate-y-0.5 hover:shadow-2xl {{ $solicitud->card_classes }}">
                        <div class="absolute inset-x-0 top-0 h-1 {{ $solicitud->accent_classes }}"></div>

                        <div class="p-5 sm:p-6 lg:p-7">
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                <div class="max-w-3xl">
                                    <div class="flex flex-wrap items-center gap-3">
                                        <span class="inline-flex items-center rounded-full bg-slate-900 px-3 py-1 text-xs font-bold uppercase tracking-[0.22em] text-white">
                                            Folio {{ $solicitud->folio }}
                                        </span>
                                        <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-bold ring-1 {{ $solicitud->badge_classes }}">
                                            <span>{{ $solicitud->badge_icon }}</span>
                                            <span>{{ $solicitud->estado_etiqueta ?? 'Pendiente' }}</span>
                                        </span>
                                    </div>

                                    <h2 class="mt-3 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                                        {{ $solicitud->nombre_apoyo ?? 'Sin nombre de apoyo' }}
                                    </h2>
                                </div>
                            </div>

                            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                                <section class="rounded-2xl bg-white/70 p-4 shadow-sm ring-1 ring-white/60">
                                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Datos de la solicitud</p>
                                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl bg-slate-50/90 px-3 py-2">
                                            <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Estado actual</dt>
                                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $solicitud->estado_etiqueta ?? 'Pendiente' }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50/90 px-3 py-2">
                                            <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Fecha de solicitud</dt>
                                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $solicitud->fecha_solicitud_formatted ?? '—' }}</dd>
                                        </div>
                                    </dl>
                                </section>

                                <section class="rounded-2xl bg-white/70 p-4 shadow-sm ring-1 ring-white/60">
                                    <p class="text-xs font-bold uppercase tracking-[0.22em] text-slate-500">Datos del apoyo</p>
                                    <dl class="mt-3 grid gap-3 sm:grid-cols-2">
                                        <div class="rounded-xl bg-slate-50/90 px-3 py-2">
                                            <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Tipo</dt>
                                            <dd class="mt-1 text-sm font-semibold text-slate-900">{{ $solicitud->tipo_apoyo ?? '—' }}</dd>
                                        </div>
                                        <div class="rounded-xl bg-slate-50/90 px-3 py-2">
                                            <dt class="text-[11px] font-bold uppercase tracking-[0.18em] text-slate-500">Vigencia del apoyo</dt>
                                            <dd class="mt-1 text-sm font-semibold text-slate-900">
                                                {{ $solicitud->apoyo_vigencia_formatted ?? '— al —' }}
                                            </dd>
                                        </div>
                                    </dl>
                                </section>
                            </div>

                            <div class="mt-5 flex justify-end">
                                <a href="{{ route('apoyos.comments', ['id' => $solicitud->fk_id_apoyo, 'origen' => 'solicitud', 'folio' => $solicitud->folio]) }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-amber-500 bg-amber-500 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:border-amber-600 hover:bg-amber-600 hover:shadow-md focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-transparent">
                                    Ver detalles completos
                                    <span aria-hidden="true">→</span>
                                </a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>
        @endif
    </div>
</div>
@endsection