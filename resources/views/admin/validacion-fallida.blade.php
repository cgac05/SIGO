@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gradient-to-br from-red-50 to-pink-50 px-4">
    <div class="w-full max-w-md">
        <!-- Error Card -->
        <div class="rounded-lg border border-red-200 bg-white shadow-lg overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-red-500 to-rose-600 px-6 py-8 text-center">
                <svg class="h-16 w-16 text-white mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4v.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <h1 class="text-2xl font-bold text-white">Validación Fallida</h1>
                <p class="text-red-100 text-sm mt-1">No se pudo verificar el documento</p>
            </div>

            <!-- Content -->
            <div class="px-6 py-8 space-y-4">
                <div class="rounded-lg bg-red-50 border border-red-200 p-4">
                    <p class="text-sm text-red-900">
                        <strong>Error:</strong> {{ $mensaje }}
                    </p>
                </div>

                <p class="text-sm text-slate-600">
                    Los motivos pueden ser:
                </p>

                <ul class="text-sm text-slate-700 space-y-2 list-disc list-inside">
                    <li>El token ha expirado</li>
                    <li>El token es inválido</li>
                    <li>El documento fue rechazado</li>
                    <li>El documento aún no ha sido verificado</li>
                </ul>

                <p class="text-xs text-slate-600 pt-4">
                    Si considera que esto es un error, por favor contacte al administrador del sistema.
                </p>
            </div>

            <!-- Footer -->
            <div class="bg-slate-50 border-t border-slate-200 px-6 py-4 text-center">
                <a href="{{ route('dashboard') }}" class="text-sm text-blue-600 hover:text-blue-700 font-medium">
                    ← Volver al panel
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
