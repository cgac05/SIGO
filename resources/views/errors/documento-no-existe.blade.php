@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-slate-50 to-slate-100 flex items-center justify-center p-4">
    <div class="bg-white rounded-xl shadow-lg p-8 text-center max-w-md">
        <div class="text-4xl mb-4">📄</div>
        <h1 class="text-2xl font-bold text-slate-900 mb-2">Documento No Encontrado</h1>
        <p class="text-slate-600 mb-6">{{ $mensaje ?? 'El archivo solicitado no se encuentra disponible.' }}</p>
        
        <div class="bg-slate-50 rounded-lg p-4 mb-6 text-left">
            <p class="text-xs text-slate-500 font-mono">{{ $path ?? 'desconocida' }}</p>
        </div>
        
        <a href="{{ route('solicitudes.proceso.index') }}" class="inline-block px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            ← Volver a Solicitudes
        </a>
    </div>
</div>
@endsection
