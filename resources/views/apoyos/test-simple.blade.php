@php
    $user = auth()->user();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Apoyos - Debug</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-3xl font-bold text-blue-600 mb-4">✓ El componente funciona!</h1>
            <p class="text-gray-600 mb-4">Usuario: <strong>{{ $user->name ?? 'No autenticado' }}</strong></p>
            
            <div class="bg-blue-50 border border-blue-300 rounded p-4 mb-4">
                <p>✓ Slot renderizado correctamente</p>
                <p>✓ Componente app-layout está funcionando</p>
                <p>✓ CSS Tailwind está aplicado</p>
            </div>
        </div>
    </div>
</x-app-layout>
