@extends('layouts.app')

@section('title', 'Dashboard de Reportes')

@section('content')
<div class="container mx-auto p-6">
    <!-- Encabezado -->
    <div class="mb-8">
        <a href="{{ route('certificacion.index') }}" class="text-blue-600 hover:text-blue-800 font-medium">← Volver</a>
        <h1 class="text-4xl font-bold text-gray-800 mt-2">📊 Dashboard de Reportes</h1>
        <p class="text-gray-600 mt-2">Reportes, estadísticas y exportación de certificados digitales</p>
    </div>

    <!-- KPIs Principales -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-lg shadow p-6">
            <p class="text-blue-100 text-sm">Total Certificados</p>
            <p class="text-3xl font-bold mt-2">{{ $estadisticas['total'] ?? 0 }}</p>
        </div>

        <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-lg shadow p-6">
            <p class="text-green-100 text-sm">Certificados</p>
            <p class="text-3xl font-bold mt-2">{{ $estadisticas['certificados'] ?? 0 }}</p>
        </div>

        <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-lg shadow p-6">
            <p class="text-purple-100 text-sm">Validados</p>
            <p class="text-3xl font-bold mt-2">{{ $estadisticas['validados'] ?? 0 }}</p>
        </div>

        <div class="bg-gradient-to-br from-orange-500 to-orange-600 text-white rounded-lg shadow p-6">
            <p class="text-orange-100 text-sm">% Certificación</p>
            <p class="text-3xl font-bold mt-2">{{ $estadisticas['porcentaje_certificacion'] ?? 0 }}%</p>
        </div>

        <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 text-white rounded-lg shadow p-6">
            <p class="text-emerald-100 text-sm">Monto Total</p>
            <p class="text-2xl font-bold mt-2">${{ number_format($estadisticas['monto_total_certificado'] ?? 0, 2) }}</p>
        </div>
    </div>

    <!-- Secciones de Reportes -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
        <!-- Reportes Individuales -->
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-blue-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">📄 Reportes Individuales</h2>
            
            <div class="space-y-3">
                <!-- PDF Individual -->
                <div class="p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition">
                    <p class="font-bold text-blue-900">📋 PDF de Certificado</p>
                    <p class="text-sm text-blue-700 mt-1">Descargar certificado individual con QR embebido</p>
                    <a href="{{ route('certificacion.listado') }}" class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2 inline-block">
                        Ver Certificados →
                    </a>
                </div>

                <!-- Cadena de Custodia -->
                <div class="p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition">
                    <p class="font-bold text-purple-900">🔗 Cadena de Custodia PDF</p>
                    <p class="text-sm text-purple-700 mt-1">Reporte completo con historial de validaciones</p>
                    <p class="text-xs text-purple-600 mt-2">Disponible en vista de certificado</p>
                </div>

                <!-- Reporte de Estadísticas -->
                <form action="{{ route('certificacion.descarga.estadisticas-pdf') }}" method="GET" class="p-4 bg-green-50 rounded-lg hover:bg-green-100 transition">
                    <button type="submit" class="w-full text-left">
                        <p class="font-bold text-green-900">📊 Reporte de Estadísticas</p>
                        <p class="text-sm text-green-700 mt-1">Estadísticas globales de certificación en PDF</p>
                        <p class="text-blue-600 hover:text-blue-800 text-sm font-medium mt-2">Descargar PDF →</p>
                    </button>
                </form>
            </div>
        </div>

        <!-- Exportaciones Masivas -->
        <div class="bg-white rounded-lg shadow-lg p-6 border-l-4 border-green-500">
            <h2 class="text-2xl font-bold text-gray-800 mb-4">📦 Exportaciones Masivas</h2>
            
            <div class="space-y-3">
                <!-- Excel -->
                <a href="{{ route('certificacion.reportes.form-certificados') }}" class="block p-4 bg-emerald-50 rounded-lg hover:bg-emerald-100 transition">
                    <p class="font-bold text-emerald-900">📊 Exportar a Excel</p>
                    <p class="text-sm text-emerald-700 mt-1">Con filtros por período, estado y programa</p>
                    <p class="text-emerald-600 hover:text-emerald-800 text-sm font-medium mt-2">Ir al Formulario →</p>
                </a>

                <!-- ZIP de PDFs -->
                <a href="{{ route('certificacion.reportes.exportacion-masiva') }}" class="block p-4 bg-orange-50 rounded-lg hover:bg-orange-100 transition">
                    <p class="font-bold text-orange-900">📦 Descargar ZIP</p>
                    <p class="text-sm text-orange-700 mt-1">Múltiples PDFs en archivo comprimido</p>
                    <p class="text-orange-600 hover:text-orange-800 text-sm font-medium mt-2">Ver Certificados →</p>
                </a>
            </div>
        </div>
    </div>

    <!-- Últimos Certificados -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden mb-8">
        <div class="bg-gradient-to-r from-blue-500 to-blue-600 text-white px-6 py-4">
            <h2 class="text-xl font-bold">📋 Últimos 10 Certificados Generados</h2>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold">Folio</th>
                        <th class="px-6 py-3 text-right text-sm font-bold">Monto</th>
                        <th class="px-6 py-3 text-center text-sm font-bold">Beneficiario</th>
                        <th class="px-6 py-3 text-center text-sm font-bold">Fecha</th>
                        <th class="px-6 py-3 text-center text-sm font-bold">Estado</th>
                        <th class="px-6 py-3 text-center text-sm font-bold">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($ultimos_certificados as $cert)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-6 py-4 font-mono text-blue-600 font-bold">{{ $cert->fk_folio }}</td>
                        <td class="px-6 py-4 text-right font-bold text-green-600">${{ number_format($cert->monto_entregado, 2) }}</td>
                        <td class="px-6 py-4 text-center text-sm">{{ $cert->solicitud->beneficiario->display_name ?? 'N/A' }}</td>
                        <td class="px-6 py-4 text-center text-sm">{{ $cert->fecha_certificacion->format('d/m/Y') }}</td>
                        <td class="px-6 py-4 text-center">
                            <span class="px-3 py-1 rounded text-sm font-bold
                                @if($cert->estado_certificacion === 'CERTIFICADO') bg-green-100 text-green-800
                                @else bg-blue-100 text-blue-800 @endif">
                                {{ $cert->estado_certificacion }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <a href="{{ route('certificacion.descarga.pdf', $cert->id_historico) }}" 
                               class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                                📥 PDF
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                            Sin certificados aún
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Estadísticas por Período -->
    <div class="bg-white rounded-lg shadow-lg p-6">
        <h2 class="text-xl font-bold text-gray-800 mb-6">📈 Certificados por Mes (Últimos 12 meses)</h2>

        <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            @forelse($por_mes as $mes)
            <div class="text-center p-3 bg-blue-50 rounded-lg">
                <p class="text-xs text-gray-600 font-medium">{{ \Carbon\Carbon::parse($mes->mes)->format('M Y') }}</p>
                <p class="text-2xl font-bold text-blue-600 mt-1">{{ $mes->total }}</p>
            </div>
            @empty
            <p class="text-gray-500 col-span-6">Sin datos disponibles</p>
            @endforelse
        </div>
    </div>
</div>
@endsection
