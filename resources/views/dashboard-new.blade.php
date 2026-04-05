@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    <!-- Header -->
    <div class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Bienvenido, {{ $user->display_name }}</h1>
                    <p class="text-slate-600 mt-1">{{ $user->email }}</p>
                </div>
                <div class="text-right">
                    <span class="inline-block px-4 py-2 bg-blue-100 text-blue-800 rounded-full text-sm font-semibold">
                        {{ ucfirst($user->tipo_usuario) }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alerts -->
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        @if (session('status') === 'profile-completed')
            <div class="mb-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-700">
                ✅ Tu perfil de beneficiario fue completado correctamente.
            </div>
        @endif

        @if ($user->isBeneficiario() && !$user->hasCompleteBeneficiarioProfile())
            <div class="mb-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                ⚠️ Debes completar tu perfil antes de registrar solicitudes de apoyo.
                <a href="{{ route('registro.completar-perfil.show') }}" class="ml-2 font-semibold underline hover:text-amber-900">Completar perfil →</a>
            </div>
        @endif

        <!-- Main Menu by Role -->
        @if ($user->isBeneficiario())
            @include('dashboard.roles.beneficiario')
        @elseif ($user->isPersonal())
            @include('dashboard.roles.personal')
        @else
            @include('dashboard.roles.default')
        @endif
    </main>
</div>
@endsection
