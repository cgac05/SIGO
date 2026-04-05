@extends('layouts.app')

@section('title', 'Generar Certificado Digital')

@section('content')
<div class="container mx-auto p-6">
    <!-- Encabezado -->
    <div class="mb-8">
        <a href="{{ route('certificacion.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">← Volver</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">🔐 Generar Certificado Digital</h1>
    </div>

    <!-- Información del Desembolso -->
    <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">📋 Información del Desembolso</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <!-- Folio -->
            <div>
                <p class="text-gray-600 text-sm font-medium">Folio</p>
                <p class="text-lg font-bold text-blue-600 mt-1">{{ $desembolso->fk_folio }}</p>
            </div>

            <!-- ID Histórico -->
            <div>
                <p class="text-gray-600 text-sm font-medium">ID Histórico</p>
                <p class="text-lg font-bold text-gray-800 mt-1">{{ $desembolso->id_historico }}</p>
            </div>

            <!-- Monto -->
            <div>
                <p class="text-gray-600 text-sm font-medium">Monto</p>
                <p class="text-lg font-bold text-green-600 mt-1">${{ number_format($desembolso->monto_entregado, 2) }}</p>
            </div>

            <!-- Fecha Entrega -->
            <div>
                <p class="text-gray-600 text-sm font-medium">Fecha Entrega</p>
                <p class="text-lg font-bold text-gray-800 mt-1">{{ $desembolso->fecha_entrega->format('d/m/Y H:i') }}</p>
            </div>

            <!-- Beneficiario -->
            <div class="md:col-span-2">
                <p class="text-gray-600 text-sm font-medium">Beneficiario</p>
                <p class="text-lg font-bold text-gray-800 mt-1">{{ $desembolso->solicitud->beneficiario->display_name ?? 'N/A' }}</p>
            </div>

            <!-- Usuario Registrador -->
            <div class="md:col-span-2">
                <p class="text-gray-600 text-sm font-medium">Usuario Registrador</p>
                <p class="text-lg font-bold text-gray-800 mt-1">{{ $desembolso->usuario->display_name ?? 'N/A' }}</p>
            </div>
        </div>
    </div>

    <!-- Información del Apoyo -->
    <div class="bg-blue-50 rounded-lg border-l-4 border-blue-500 p-6 mb-8">
        <h3 class="font-bold text-blue-900">📌 Programa de Apoyo</h3>
        <p class="text-blue-700 mt-2">{{ $desembolso->solicitud->apoyo->nombre_apoyo ?? 'N/A' }}</p>
        @if($desembolso->descripcion)
        <p class="text-blue-700 mt-2"><strong>Descripción:</strong> {{ $desembolso->descripcion }}</p>
        @endif
    </div>

    <!-- Datos que se Certificarán -->
    <div class="bg-gray-50 rounded-lg border-2 border-gray-300 p-6 mb-8">
        <h3 class="font-bold text-gray-800 mb-4">🔐 Datos que se incluirán en el certificado:</h3>
        <ul class="space-y-3 text-gray-700">
            <li class="flex items-start">
                <span class="text-green-500 mr-3 font-bold">✓</span>
                <span><strong>Folio:</strong> {{ $desembolso->fk_folio }}</span>
            </li>
            <li class="flex items-start">
                <span class="text-green-500 mr-3 font-bold">✓</span>
                <span><strong>Monto:</strong> ${{ number_format($desembolso->monto_entregado, 2) }}</span>
            </li>
            <li class="flex items-start">
                <span class="text-green-500 mr-3 font-bold">✓</span>
                <span><strong>Fecha:</strong> {{ $desembolso->fecha_entrega->format('d/m/Y H:i:s') }}</span>
            </li>
            <li class="flex items-start">
                <span class="text-green-500 mr-3 font-bold">✓</span>
                <span><strong>Hash SHA-256:</strong> Se generará automáticamente</span>
            </li>
            <li class="flex items-start">
                <span class="text-green-500 mr-3 font-bold">✓</span>
                <span><strong>Código QR:</strong> Con todos los datos anteriores</span>
            </li>
            <li class="flex items-start">
                <span class="text-green-500 mr-3 font-bold">✓</span>
                <span><strong>Firma Digital:</strong> Certificado electrónicamente</span>
            </li>
            <li class="flex items-start">
                <span class="text-green-500 mr-3 font-bold">✓</span>
                <span><strong>Cadena de Custodia:</strong> Auditoria de validaciones</span>
            </li>
        </ul>
    </div>

    <!-- Advertencia de LGPDP -->
    <div class="bg-yellow-50 rounded-lg border-l-4 border-yellow-500 p-6 mb-8">
        <div class="flex items-start">
            <span class="text-2xl mr-4">⚠️</span>
            <div>
                <h3 class="font-bold text-yellow-900">Cumplimiento LGPDP</h3>
                <p class="text-yellow-800 mt-2">
                    Al generar este certificado, se estará creando una prueba electrónica irrevocable de la entrega del desembolso.
                    Este certificado se mantendrá de acuerdo con la Ley General de Protección de Datos Personales (LGPDP).
                </p>
            </div>
        </div>
    </div>

    <!-- Formulario de Confirmación -->
    <form action="{{ route('certificacion.generar', $desembolso->id_historico) }}" method="POST" class="bg-white rounded-lg shadow-lg p-8">
        @csrf

        <!-- Checkbox de Confirmación -->
        <div class="mb-8">
            <label class="flex items-start cursor-pointer">
                <input type="checkbox" name="confirmacion" value="on" required class="mt-1 w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                <span class="ml-4 text-gray-800">
                    <strong>Confirmo que autorizo generar el certificado digital</strong>
                    <p class="text-sm text-gray-600 mt-1">
                        Entiendo que este certificado será una prueba electrónica formal de la entrega
                        y que será difícil modificarlo o revocarlo después de su generación.
                    </p>
                </span>
            </label>
            @error('confirmacion')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-4">
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg transition flex items-center">
                <span class="mr-2">✅</span> Generar Certificado Digital
            </button>
            <a href="{{ route('certificacion.index') }}" class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-lg transition flex items-center">
                <span class="mr-2">❌</span> Cancelar
            </a>
        </div>
    </form>

    <!-- Nota Legal -->
    <div class="mt-8 p-4 bg-gray-100 rounded-lg text-sm text-gray-700 border-l-4 border-gray-400">
        <p class="font-bold mb-2">📜 Nota Legal</p>
        <p>
            Este certificado digital es una prueba electrónica oficializada. 
            Cumple con estándares de seguridad criptográfica y es válido como comprobante de entrega ante autoridades.
            El certificado incluye: hash SHA-256, firma digital HMAC-SHA256, código QR, y cadena de custodia completa.
        </p>
    </div>
</div>
@endsection
