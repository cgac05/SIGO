@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <a href="{{ route('certificacion.verificacion.dashboard') }}" class="text-blue-600 hover:text-blue-800 mb-2 inline-block">
                ← Volver al Dashboard
            </a>
            <h1 class="text-3xl font-bold text-gray-900">🔍 Validación en Lote</h1>
            <p class="text-gray-600 mt-1">Valida múltiples certificados según criterios de búsqueda</p>
        </div>

        <form action="{{ route('certificacion.verificacion.procesar-lote') }}" method="POST" class="bg-white rounded-lg shadow p-6">
            @csrf

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                <!-- Fecha Inicio -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Inicio</label>
                    <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Opcional - Filtra por fecha de entrega mínima</p>
                </div>

                <!-- Fecha Fin -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Fecha Fin</label>
                    <input type="date" name="fecha_fin" value="{{ request('fecha_fin') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    <p class="text-xs text-gray-500 mt-1">Opcional - Filtra por fecha de entrega máxima</p>
                </div>

                <!-- Programa de Apoyo -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Programa de Apoyo</label>
                    <select name="apoyo_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Todos los Apoyos --</option>
                        @foreach($apoyos as $apoyo)
                        <option value="{{ $apoyo->id_apoyo }}" @selected(request('apoyo_id') == $apoyo->id_apoyo)>
                            {{ $apoyo->nombre_apoyo }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <!-- Estado de Certificación -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Estado de Certificación</label>
                    <select name="estado_certificacion" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                        <option value="">-- Todos los Estados --</option>
                        <option value="CERTIFICADO" @selected(request('estado_certificacion') === 'CERTIFICADO')>CERTIFICADO</option>
                        <option value="VALIDADO" @selected(request('estado_certificacion') === 'VALIDADO')>VALIDADO</option>
                    </select>
                </div>
            </div>

            <!-- Información de Búsqueda -->
            <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6">
                <p class="text-sm text-blue-700">
                    <strong>💡 Tip:</strong> Usa los filtros para reducir el número de certificados a validar. 
                    Se validarán hasta 50 certificados por página.
                </p>
            </div>

            <!-- Botón de Búsqueda -->
            <div class="flex gap-3">
                <button type="submit" class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 font-medium">
                    🔍 Buscar y Validar
                </button>
                <a href="{{ route('certificacion.verificacion.formulario-lote') }}" class="inline-block bg-gray-300 text-gray-700 px-6 py-2 rounded hover:bg-gray-400 font-medium">
                    Limpiar Filtros
                </a>
            </div>
        </form>

        <!-- Información Adicional -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-gray-900 mb-3">📋 Criterios de Búsqueda</h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li>✓ Rango de fechas de entrega</li>
                    <li>✓ Programa de apoyo específico</li>
                    <li>✓ Estado de certificación</li>
                    <li>✓ Combinaciones de filtros</li>
                </ul>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <h3 class="font-bold text-gray-900 mb-3">⚡ Beneficios</h3>
                <ul class="text-sm text-gray-600 space-y-2">
                    <li>✓ Validación masiva de integridad</li>
                    <li>✓ Auditoría automática de cada certificado</li>
                    <li>✓ Descargar reportes en ZIP</li>
                    <li>✓ Generación de reportes LGPDP</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
