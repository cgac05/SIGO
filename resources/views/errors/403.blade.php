@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-gray-100">
    <div class="max-w-md w-full bg-white shadow-md rounded-lg p-8 text-center">
        <div class="mb-4">
            <h1 class="text-6xl font-bold text-red-600">403</h1>
            <h2 class="text-2xl font-semibold text-gray-800 mt-2">Acceso Denegado</h2>
        </div>
        
        <p class="text-gray-600 mb-6">
            {{ $message ?? 'No tienes permiso para acceder a este recurso.' }}
        </p>
        
        <div class="space-y-3">
            <a href="{{ route('dashboard') }}" class="block w-full py-2 px-4 bg-blue-600 text-white rounded hover:bg-blue-700 transition">
                Ir al Dashboard
            </a>
            <a href="{{ url('/') }}" class="block w-full py-2 px-4 bg-gray-600 text-white rounded hover:bg-gray-700 transition">
                Ir al Inicio
            </a>
        </div>
    </div>
</div>
@endsection
