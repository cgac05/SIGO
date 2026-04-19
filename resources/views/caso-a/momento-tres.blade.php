@extends('layouts.app')

@section('title', 'Consulta de Estado - INJUVE')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-blue-50 to-indigo-100 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg p-8">
        
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-indigo-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <svg class="w-8 h-8 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Consulta Privada</h1>
            <p class="text-gray-600">Estado de tu expediente sin necesidad de iniciar sesión</p>
        </div>

        <!-- Formulario de Validación -->
        <form action="{{ route('caso-a.validar-momento-tres') }}" method="POST" class="space-y-6">
            @csrf

            <!-- Campo Folio -->
            <div>
                <label for="folio" class="block text-sm font-medium text-gray-700 mb-2">
                    📋 Número de Folio
                </label>
                <input 
                    type="text" 
                    name="folio" 
                    id="folio"
                    placeholder="ej. SIGO-2026-CASO-A-12345-xyz"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('folio') border-red-500 @enderror"
                    required
                >
                @error('folio')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Campo Clave Privada -->
            <div>
                <label for="clave" class="block text-sm font-medium text-gray-700 mb-2">
                    🔑 Clave Privada de Acceso
                </label>
                <input 
                    type="password" 
                    name="clave" 
                    id="clave"
                    placeholder="ej. KX7M-9P2W-5LQ8-9K3X"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('clave') border-red-500 @enderror"
                    required
                    autocomplete="off"
                >
                <p class="text-gray-500 text-xs mt-1">La clave te fue entregada durante la carga presencial de documentos</p>
                @error('clave')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <!-- Mensajes de Error General -->
            @if($errors->has('general'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    {{ $errors->first('general') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                    {{ session('error') }}
                </div>
            @endif

            <!-- Botón Consultar -->
            <button 
                type="submit"
                class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition duration-200 flex items-center justify-center gap-2"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                Consultar Estado
            </button>
        </form>

        <!-- Información Adicional -->
        <div class="mt-8 pt-8 border-t border-gray-200">
            <div class="bg-blue-50 p-4 rounded-lg">
                <p class="text-sm text-gray-700">
                    <strong class="text-blue-900">📌 ¿Qué información veré?</strong> <br>
                    Estado de tus documentos, fechas de hitos, y detalles del apoyo que solicitaste.
                </p>
            </div>

            <div class="bg-green-50 p-4 rounded-lg mt-3">
                <p class="text-sm text-gray-700">
                    <strong class="text-green-900">🔒 Privacidad</strong> <br>
                    Esta consulta es completamente privada y no requiere iniciar sesión.
                </p>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-8 text-center text-sm text-gray-600">
            <p>¿Tienes preguntas? Contacta a INJUVE</p>
            <p class="text-gray-500 mt-1">
                📞 <a href="tel:+5218110000000" class="text-indigo-600 hover:underline">Call Center</a> | 
                📧 <a href="mailto:info@injuve.gob.mx" class="text-indigo-600 hover:underline">info@injuve.gob.mx</a>
            </p>
        </div>

    </div>
</div>
@endsection
