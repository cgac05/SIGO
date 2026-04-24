@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100">
    {{-- 1. CAPTURAMOS EL ROL REAL: --}}
    {{-- Esto extrae el ID numérico del rol (1, 2 o 3) de la relación con la tabla personal --}}
    @php
        $rolId = (int) optional($user->personal)->fk_rol;
    @endphp

    <div class="bg-white border-b border-slate-200 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900">Bienvenido, {{ $user->display_name }}</h1>
                    <p class="text-slate-600 mt-1">{{ $user->email }}</p>
                </div>
                <div class="text-right">
                    {{-- 2. BADGE DINÁMICO: --}}
                    {{-- Cambia de color y texto según el rol, sin afectar a los demás --}}
                    @php
                        $badgeStyles = [
                            1 => ['class' => 'bg-blue-100 text-blue-800', 'label' => 'Administrativo'],
                            2 => ['class' => 'bg-purple-100 text-purple-800', 'label' => 'Directivo'],
                            3 => ['class' => 'bg-green-100 text-green-800', 'label' => 'Recursos Financieros'],
                        ];
                        $style = $badgeStyles[$rolId] ?? ['class' => 'bg-slate-100 text-slate-800', 'label' => ucfirst($user->tipo_usuario)];
                    @endphp
                    <span class="inline-block px-4 py-2 {{ $style['class'] }} rounded-full text-sm font-semibold">
                        {{ $style['label'] }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        {{-- 3. EL FILTRO DE PANELES (EL "CEREBRO"): --}}
        {{-- Aquí cada rol se va a su propia vista. Si no es 3, Valeria no entra aquí --}}
        @switch($rolId)
            @case(1)
                {{-- Solo Administrativos ven esto --}}
                @include('dashboard.roles.personal')
                @break

            @case(2)
                {{-- Solo Directivos ven esto --}}
                @include('dashboard.roles.directivo')
                @break

            @case(3)
                {{-- SOLO VALERIA Y FINANZAS ven esto --}}
                @include('dashboard/roles/financiero')
                @break

            @default
                {{-- Si no es personal (es beneficiario), cargamos su panel normal --}}
                @if ($user->isBeneficiario())
                    @include('dashboard.roles.beneficiario')
                @else
                    @include('dashboard.roles.default')
                @endif
        @endswitch
    </main>
</div>
@endsection