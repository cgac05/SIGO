<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Panel Principal - {{ config('app.name', 'SIGO') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased">
    <div class="min-h-screen bg-gray-100">
        @include('layouts.navigation')

        <header class="bg-white shadow">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                    {{ __('Panel principal') }}
                </h2>
            </div>
        </header>

        <main>
            <div class="py-12">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6 text-gray-900 space-y-4">
                    @if (session('error'))
                        <div class="rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if (session('status') === 'profile-completed')
                        <div class="rounded-md border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                            Tu perfil de beneficiario fue completado correctamente.
                        </div>
                    @endif

                    <div>
                        <p class="text-lg font-semibold">{{ $user->display_name }}</p>
                        <p class="text-sm text-gray-600">{{ $user->email }}</p>
                        <p class="mt-2 text-sm text-gray-500">Tipo de usuario: {{ $tipo }}</p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        @if ($user->isBeneficiario())
                            <a href="{{ route('solicitudes.registrar') }}" class="inline-flex items-center rounded-md bg-slate-900 px-3 py-2 text-sm font-semibold text-white hover:bg-slate-800">
                                Registrar solicitud
                            </a>
                        @endif

                        @if ($user->isPersonal())
                            <a href="{{ route('solicitudes.proceso.index') }}" class="inline-flex items-center rounded-md bg-blue-700 px-3 py-2 text-sm font-semibold text-white hover:bg-blue-600">
                                Proceso de cierre y validacion
                            </a>
                        @endif

                        <a href="{{ route('solicitudes.publico.validar') }}" class="inline-flex items-center rounded-md border border-slate-300 px-3 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                            Portal publico CUV
                        </a>
                    </div>

                    @if ($user->isBeneficiario() && ! $user->hasCompleteBeneficiarioProfile())
                        <div class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            Debes completar tu perfil antes de registrar solicitudes de apoyo.
                            <a href="{{ route('registro.completar-perfil.show') }}" class="ml-2 font-semibold underline">Completar perfil</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
        </main>
    </div>
</body>
</html>
