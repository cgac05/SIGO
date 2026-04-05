@extends('layouts.app')

@section('title', 'Validar Certificado Digital')

@section('content')
<div class="container mx-auto p-6">
    <!-- Encabezado -->
    <div class="mb-8">
        <a href="{{ route('certificacion.ver', $desembolso->id_historico) }}" class="text-blue-600 hover:text-blue-800 font-medium">← Volver</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">🔍 Validar Certificado Digital</h1>
    </div>

    <!-- Información del Certificado -->
    <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
        <h2 class="text-xl font-bold text-gray-800 mb-6">📋 Certificado a Validar</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
            <div>
                <p class="text-gray-600 text-sm font-medium">Folio</p>
                <p class="text-lg font-bold text-blue-600">{{ $desembolso->fk_folio }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-medium">Monto</p>
                <p class="text-lg font-bold text-green-600">${{ number_format($desembolso->monto_entregado, 2) }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-medium">Fecha Entrega</p>
                <p class="text-sm font-mono">{{ $desembolso->fecha_entrega->format('d/m/Y H:i') }}</p>
            </div>

            <div>
                <p class="text-gray-600 text-sm font-medium">Estado</p>
                <p class="text-sm">
                    <span class="bg-green-100 text-green-800 px-3 py-1 rounded font-bold">
                        {{ $desembolso->estado_certificacion }}
                    </span>
                </p>
            </div>
        </div>

        <!-- Hash del Certificado -->
        <div class="mt-6 pt-6 border-t">
            <p class="text-gray-600 text-sm font-medium mb-2">Hash SHA-256 del Certificado</p>
            <div class="bg-gray-100 p-4 rounded font-mono text-xs break-all text-gray-700 border-l-4 border-blue-500">
                {{ $desembolso->hash_certificado }}
            </div>
        </div>
    </div>

    <!-- Información de Validación -->
    <div class="bg-blue-50 rounded-lg border-l-4 border-blue-500 p-6 mb-8">
        <h3 class="font-bold text-blue-900 mb-4">✅ Información de Validación</h3>
        <ul class="space-y-3 text-blue-800">
            <li class="flex items-start">
                <span class="text-blue-600 mr-3 font-bold">ℹ️</span>
                <span>Al validar este certificado, se registrará el evento en la cadena de custodia</span>
            </li>
            <li class="flex items-start">
                <span class="text-blue-600 mr-3 font-bold">ℹ️</span>
                <span>Se guardará tu nombre de usuario, IP, y hora exacta de la validación</span>
            </li>
            <li class="flex items-start">
                <span class="text-blue-600 mr-3 font-bold">ℹ️</span>
                <span>El estado pasará de "CERTIFICADO" a "VALIDADO"</span>
            </li>
            <li class="flex items-start">
                <span class="text-blue-600 mr-3 font-bold">ℹ️</span>
                <span>Este registro es auditable y permanente (LGPDP compliance)</span>
            </li>
        </ul>
    </div>

    <!-- Formulario de Validación -->
    <form action="{{ route('certificacion.validar', $desembolso->id_historico) }}" method="POST" class="bg-white rounded-lg shadow-lg p-8">
        @csrf

        <!-- Notas / Observaciones -->
        <div class="mb-6">
            <label for="notas" class="block text-gray-800 font-bold mb-2">
                📝 Notas de Validación (Opcional)
            </label>
            <textarea 
                name="notas" 
                id="notas"
                rows="4"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                placeholder="Ej: Verificado con beneficiario en terreno, identidad confirmada...">{{ old('notas') }}</textarea>
            <p class="text-gray-600 text-sm mt-2">Máximo 500 caracteres</p>
            @error('notas')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Validación de Integridad -->
        <div class="bg-gray-50 rounded-lg border-2 border-gray-300 p-6 mb-8">
            <h3 class="font-bold text-gray-800 mb-4">🔐 Validación de Integridad</h3>
            <p class="text-gray-700 mb-4">
                Antes de validar, se verificará que el certificado no haya sido modificado.
                Las siguientes verificaciones se realizarán automáticamente:
            </p>
            <ul class="space-y-2 text-gray-700 text-sm">
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Verificar que el hash NO haya sido alterado</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Validar integridad de datos (folio, monto, fecha)</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Confirmar firma digital HMAC-SHA256</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span>Registrar evento en cadena de custodia</span>
                </li>
            </ul>
        </div>

        <!-- Checkbox de Confirmación -->
        <div class="mb-8">
            <label class="flex items-start cursor-pointer">
                <input type="checkbox" name="confirmacion_validacion" value="on" required 
                       class="mt-1 w-5 h-5 text-blue-600 rounded focus:ring-2 focus:ring-blue-500">
                <span class="ml-4 text-gray-800">
                    <strong>Confirmo que he verificado la integridad del certificado</strong>
                    <p class="text-sm text-gray-600 mt-1">
                        Entiendo que al validar este certificado, estoy confirmando que la entrega 
                        fue realizada correctamente según la información registrada.
                    </p>
                </span>
            </label>
            @error('confirmacion_validacion')
            <p class="text-red-600 text-sm mt-2">{{ $message }}</p>
            @enderror
        </div>

        <!-- Botones de Acción -->
        <div class="flex gap-4">
            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-8 rounded-lg transition flex items-center">
                <span class="mr-2">✅</span> Validar Certificado
            </button>
            <a href="{{ route('certificacion.ver', $desembolso->id_historico) }}" 
               class="bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-8 rounded-lg transition flex items-center">
                <span class="mr-2">❌</span> Cancelar
            </a>
        </div>
    </form>

    <!-- Nota de Seguridad -->
    <div class="mt-8 p-6 bg-purple-50 rounded-lg border-l-4 border-purple-500 text-purple-800">
        <p class="font-bold mb-2">🔐 Nota de Seguridad</p>
        <p class="text-sm">
            La validación de certificados es un proceso que quedar registrado permanentemente.
            Asegúrate de que hayas verificado físicamente la entrega antes de proceder con la validación.
            Este evento será auditable y podrá ser consultado por autoridades si se solicita.
        </p>
    </div>
</div>
@endsection
