@php
    $user = auth()->user();
    $isBeneficiario = $user && $user->isBeneficiario();
    $isAdmin = $user && $user->personal && (int) $user->personal->fk_rol === 1;
    $isDirector = $user && $user->personal && (int) $user->personal->fk_rol === 2;
    $canEdit = $isAdmin || $isDirector;
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Apoyos</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Debug Panel -->
        <div class="bg-white rounded-lg shadow p-6 mb-6 border-2 border-blue-500">
            <h2 class="text-xl font-bold text-blue-700 mb-4">Debug Info</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-sm">
                <div class="bg-blue-50 p-3 rounded">
                    <p class="font-semibold">Usuario</p>
                    <p>{{ $user->name ?? 'N/A' }}</p>
                </div>
                <div class="bg-green-50 p-3 rounded">
                    <p class="font-semibold">Total Apoyos</p>
                    <p>{{ count($apoyos ?? []) }}</p>
                </div>
                <div class="bg-yellow-50 p-3 rounded">
                    <p class="font-semibold">Beneficiario</p>
                    <p>{{ $isBeneficiario ? '✓ Sí' : '✗ No' }}</p>
                </div>
                <div class="bg-purple-50 p-3 rounded">
                    <p class="font-semibold">Puede Editar</p>
                    <p>{{ $canEdit ? '✓ Sí' : '✗ No' }}</p>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        @if(empty($apoyos) || count($apoyos) === 0)
            <div class="bg-yellow-50 border-2 border-yellow-400 rounded-lg p-8 text-center">
                <p class="text-2xl font-bold text-yellow-800">⚠ Sin Apoyos</p>
                <p class="text-yellow-700 mt-2">No hay datos en la variable $apoyos</p>
            </div>
        @else
            <div class="mb-6">
                <h3 class="text-lg font-bold text-slate-900 mb-4">Apoyos Disponibles ({{ count($apoyos) }})</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    @foreach($apoyos as $apoyo)
                        <div class="bg-white rounded-lg border border-slate-300 p-4 hover:shadow-md transition">
                            <h4 class="font-bold text-slate-900">{{ $apoyo->nombre_apoyo }}</h4>
                            <p class="text-sm text-slate-600 mt-1">{{ $apoyo->tipo_apoyo }}</p>
                            <p class="text-xs text-slate-500 mt-2">Vigencia: {{ $apoyo->fechaInicio ?? '-' }} al {{ $apoyo->fechafin ?? '-' }}</p>
                            <a href="{{ route('apoyos.comments', $apoyo->id_apoyo) }}" class="mt-3 inline-block w-full text-center bg-blue-600 text-white rounded py-2 text-sm font-semibold hover:bg-blue-700">
                                Ver Detalle
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
