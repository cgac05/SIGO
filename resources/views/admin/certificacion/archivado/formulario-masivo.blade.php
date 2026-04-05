@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.archivado.dashboard') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver al Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">📦 Archivamiento Masivo</h1>
            <p class="text-gray-600 mt-1">Archivar múltiples certificados según filtros</p>
        </div>

        <form action="{{ route('certificacion.archivado.procesar-masivo') }}" method="POST" class="bg-white rounded-lg shadow p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Fecha Inicio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Opcional - Certificados entregados desde esta fecha</p>
                </div>

                <!-- Fecha Fin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Opcional - Certificados entregados hasta esta fecha</p>
                </div>

                <!-- Estado de Certificación -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado de Certificación</label>
                    <select name="estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Todos los Estados --</option>
                        <option value="CERTIFICADO" @selected(request('estado') === 'CERTIFICADO')>CERTIFICADO</option>
                        <option value="VALIDADO" @selected(request('estado') === 'VALIDADO')>VALIDADO</option>
                    </select>
                </div>
            </div>

            <!-- Información -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-sm text-blue-700">
                    <strong>💡 Información:</strong> Se archivará uno o más certificados con los filtros especificados. 
                    Cada archivo incluye: datos del certificado, hash de integridad y metadatos de auditoría.
                </p>
            </div>

            <!-- Botones -->
            <div class="flex gap-3">
                <button type="submit" class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 font-medium">
                    📦 Procesar Archivamiento
                </button>
                <a href="{{ route('certificacion.archivado.dashboard') }}" class="inline-block bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 font-medium">
                    Cancelar
                </a>
            </div>
        </form>

        <!-- Beneficios del Archivamiento -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-gray-900 mb-3">✅ Beneficios de Archivamiento</h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li>✓ Compresión automática de archivos</li>
                    <li>✓ Hash de integridad SHA-256</li>
                    <li>✓ Versionado automático de cambios</li>
                    <li>✓ Metadata de auditoría completa</li>
                    <li>✓ Recuperación facilitada</li>
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-gray-900 mb-3">🔐 Seguridad y Compliance</h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li>✓ Cumplimiento LGPDP</li>
                    <li>✓ Trazabilidad completa</li>
                    <li>✓ Protección de datos sensibles</li>
                    <li>✓ Política de retención automática</li>
                    <li>✓ Auditoría de acceso</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
