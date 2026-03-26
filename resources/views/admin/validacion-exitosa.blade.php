@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-50 px-4">
    <div class="w-full max-w-md">
        <!-- Success Card -->
        <div class="rounded-lg border border-green-200 bg-white shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-green-500 to-emerald-600 px-6 py-8 text-center">
                <svg class="h-16 w-16 text-white mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h1 class="text-2xl font-bold text-white">Documento Verificado</h1>
                <p class="text-green-100 text-sm mt-1">Validación exitosa</p>
            </div>

            <!-- Content -->
            <div class="px-6 py-8 space-y-6">
                <!-- Solicitud Info -->
                <div class="rounded-lg bg-blue-50 border border-blue-200 p-4 space-y-2">
                    <p class="text-xs text-blue-600 font-semibold uppercase tracking-wide">Solicitud</p>
                    <p class="text-lg font-bold text-slate-900">Folio {{ $solicitud->folio }}</p>
                    <p class="text-sm text-slate-600">{{ $solicitud->apoyo->nombre_apoyo }}</p>
                </div>

                <!-- Beneficiary Info -->
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide">Información del Beneficiario</p>
                    <div class="space-y-1 text-sm">
                        <p><span class="text-slate-600">Nombre:</span> <strong>{{ $solicitud->beneficiario->nombre }}</strong></p>
                        <p><span class="text-slate-600">Apellidos:</span> <strong>{{ $solicitud->beneficiario->apellido_paterno }} {{ $solicitud->beneficiario->apellido_materno }}</strong></p>
                        <p><span class="text-slate-600">CURP:</span> <strong class="font-mono">{{ $solicitud->beneficiario->curp }}</strong></p>
                    </div>
                </div>

                <!-- Document Info -->
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide">Información del Documento</p>
                    <div class="space-y-1 text-sm">
                        <p><span class="text-slate-600">Tipo:</span> <strong>{{ $documento->tipoDocumento->nombre_documento }}</strong></p>
                        <p><span class="text-slate-600">Cargado:</span> <strong>{{ $documento->fecha_carga->format('d/m/Y H:i') }}</strong></p>
                        <p><span class="text-slate-600">Verificado:</span> <strong>{{ $documento->fecha_verificacion->format('d/m/Y H:i') }}</strong></p>
                    </div>
                </div>

                <!-- Admin Info -->
                <div class="rounded-lg bg-slate-50 border border-slate-200 p-4 space-y-3">
                    <p class="text-xs text-slate-600 font-semibold uppercase tracking-wide">Verificado por</p>
                    <div class="space-y-1 text-sm">
                        <p><span class="text-slate-600">Administrador:</span> <strong>{{ $admin?->email ?? 'Sistema' }}</strong></p>
                    </div>
                </div>

                <!-- Observations (if any) -->
                @if($documento->admin_observations)
                <div class="rounded-lg bg-amber-50 border border-amber-200 p-4">
                    <p class="text-xs text-amber-600 font-semibold uppercase tracking-wide mb-2">Observaciones</p>
                    <p class="text-sm text-amber-900">{{ $documento->admin_observations }}</p>
                </div>
                @endif

                <!-- Token Display -->
                <div class="rounded-lg bg-slate-900 p-4">
                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide mb-2">Token de Verificación</p>
                    <code class="text-xs text-green-400 block break-all font-mono">{{ $documento->verification_token }}</code>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 border-t border-slate-200 px-6 py-4 text-center">
                <p class="text-xs text-slate-600">
                    Este documento ha sido verificado por el sistema SIGO y es válido para presentar como comprobante de trámite
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
