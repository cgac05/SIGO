@php
    $user = auth()->user();
@endphp

<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">Test de Componente</h2>
    </x-slot>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="bg-white rounded-lg shadow p-6">
            <h1 class="text-2xl font-bold mb-4">✓ El componente app-layout está funcionando</h1>
            <p class="text-gray-600">Usuario autenticado: {{ $user->name ?? 'No autenticado' }}</p>
        </div>
    </div>
</x-app-layout>
