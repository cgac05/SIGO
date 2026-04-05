@extends('layouts.app')

@section('title', 'Generar Reporte de Certificados')

@section('content')
<div class="container mx-auto p-6">
    <div class="mb-8">
        <a href="{{ route('certificacion.reportes.dashboard') }}" class="text-blue-600 hover:text-blue-800 font-medium">← Volver</a>
        <h1 class="text-3xl font-bold text-gray-800 mt-2">📊 Generar Reporte de Certificados</h1>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Formulario -->
        <div class="lg:col-span-2 bg-white rounded-lg shadow-lg p-8">
            <form action="{{ route('certificacion.exportar.excel') }}" method="GET">
                @csrf

                <!-- Estado -->
                <div class="mb-6">
                    <label for="estado" class="block text-gray-800 font-bold mb-2">Estado de Certificación</label>
                    <select name="estado" id="estado" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los estados</option>
                        <option value="CERTIFICADO">Certificados</option>
                        <option value="VALIDADO">Validados</option>
                    </select>
                </div>

                <!-- Período -->
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <label for="fecha_inicio" class="block text-gray-800 font-bold mb-2">Fecha Inicio</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label for="fecha_fin" class="block text-gray-800 font-bold mb-2">Fecha Fin</label>
                        <input type="date" name="fecha_fin" id="fecha_fin" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <!-- Programa de Apoyo -->
                <div class="mb-8">
                    <label for="apoyo_id" class="block text-gray-800 font-bold mb-2">Programa de Apoyo (Opcional)</label>
                    <select name="apoyo_id" id="apoyo_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos los programas</option>
                        @php
                            // Obtener apoyos únicos de certificados
                            $apoyos = \App\Models\HistoricoCierre::where('estado_certificacion', '!=', 'PENDIENTE')
                                ->with('solicitud.apoyo')
                                ->get()
                                ->pluck('solicitud.apoyo')
                                ->unique('id_apoyo');
                        @endphp
                        @foreach($apoyos as $apoyo)
                            @if($apoyo)
                            <option value="{{ $apoyo->id_apoyo }}">{{ $apoyo->nombre_apoyo }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <!-- Botones -->
                <div class="flex gap-4">
                    <button type="submit" class="flex-1 bg-green-500 hover:bg-green-600 text-white font-bold py-3 px-6 rounded-lg transition flex items-center justify-center">
                        <span class="mr-2">📊</span> Generar y Descargar Excel
                    </button>
                    <a href="{{ route('certificacion.reportes.dashboard') }}" class="flex-1 bg-gray-500 hover:bg-gray-600 text-white font-bold py-3 px-6 rounded-lg transition flex items-center justify-center">
                        <span class="mr-2">❌</span> Cancelar
                    </a>
                </div>
            </form>

            <!-- Notas -->
            <div class="mt-8 p-4 bg-blue-50 rounded-lg border-l-4 border-blue-500">
                <p class="font-bold text-blue-900">ℹ️ Información</p>
                <ul class="text-sm text-blue-800 mt-2 space-y-1">
                    <li>✓ El archivo Excel incluye todos los certificados certificados o validados</li>
                    <li>✓ Se pueden aplicar filtros por período, estado y programa</li>
                    <li>✓ Cada certificado muestra: folio, monto, beneficiario, fecha, estado, hash</li>
                    <li>✓ El archivo es directamente importable a sistemas contables</li>
                </ul>
            </div>
        </div>

        <!-- Sidebar: Información -->
        <div class="bg-white rounded-lg shadow-lg p-6 h-fit border-l-4 border-green-500">
            <h3 class="text-lg font-bold text-gray-800 mb-4">📋 Plantilla Excel</h3>
            
            <p class="text-sm text-gray-600 mb-4">El reporte incluirá las siguientes columnas:</p>
            
            <ul class="space-y-2 text-sm text-gray-700 mb-6">
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Folio:</strong> Identificador del desembolso</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Monto:</strong> Cantidad entregada</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Beneficiario:</strong> Persona que recibió</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Programa:</strong> Apoyo otorgado</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Fecha Entrega:</strong> Cuándo se entregó</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Fecha Certificación:</strong> Cuándo se certificó</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Estado:</strong> CERTIFICADO o VALIDADO</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Usuario Registrador:</strong> Quién registró</span>
                </li>
                <li class="flex items-start">
                    <span class="text-green-500 mr-2">✓</span>
                    <span><strong>Hash (primeros 16):</strong> Identificador único</span>
                </li>
            </ul>

            <div class="p-3 bg-yellow-50 rounded border-l-2 border-yellow-500">
                <p class="text-xs text-yellow-800">
                    <strong>💡 Tip:</strong> Usa Excel para análisis, gráficas y reportes financieros
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
