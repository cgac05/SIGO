@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-12">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-md p-8 text-center">
        <div class="text-red-500 text-6xl font-bold mb-4">405</div>
        <h1 class="text-2xl font-bold text-gray-800 mb-4">No se puede completar esta acción</h1>
        <p class="text-gray-600 mb-6">{{ $message ?? 'El método utilizado no es válido para esta operación.' }}</p>
        <a href="{{ route('apoyos.index') }}" class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition">
            Volver a Apoyos
        </a>
    </div>
</div>
@endsection
