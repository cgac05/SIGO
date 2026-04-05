@extends('layouts.app')

@section('content')
<div class="min-h-screen bg-gray-50 py-6">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 md:px-8">
        <!-- Encabezado -->
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-900">📦 Resultados de Archivamiento Masivo</h1>
            <p class="text-gray-600 mt-1">{{ $certificados->total() }} certificado(s) procesado(s)</p>
        </div>

        <!-- Estadísticas -->
        @php
            $exitosos = collect($resultados)->filter(fn($r) => $r === true)->count();
            $fallidos = $certificados->total() - $exitosos;
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Total</p>
                <p class="text-2xl font-bold text-blue-600">{{ $certificados->total() }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Exitosos</p>
                <p class="text-2xl font-bold text-green-600">{{ $exitosos }}</p>
            </div>
            <div class="bg-white rounded-lg shadow p-4">
                <p class="text-gray-600 text-sm">Fallidos</p>
                <p class="text-2xl font-bold text-red-600">{{ $fallidos }}</p>
            </div>
        </div>

        <!-- Tabla de Resultados -->
        <div class="bg-white rounded-lg shadow mb-6 overflow-x-auto">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-bold text-gray-900">📋 Resultado de Certificados</h2>
            </div>

            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-200">
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Folio</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Monto</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Beneficiario</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Fecha</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Estado</th>
                        <th class="px-6 py-3 text-left text-sm font-medium text-gray-700">Resultado</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200">
                    @forelse($certificados as $cert)
                    @php
                        $archivado = $resultados[$cert->id_historico] ?? false;
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3 text-sm text-blue-600 font-bold">
                            {{ $cert->fk_folio }}
                        </td>
                        <td class="px-6 py-3 text-sm text-green-600 font-medium">
                            ${{ number_format($cert->monto_entregado, 2) }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-700">
                            {{ $cert->solicitud->beneficiario->display_name ?? 'N/A' }}
                        </td>
                        <td class="px-6 py-3 text-sm text-gray-600">
                            {{ $cert->fecha_entrega->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-3 text-sm">
                            <span class="inline-block px-2 py-1 rounded text-xs font-medium 
                                @if($cert->estado_certificacion === 'VALIDADO')
                                    bg-green-100 text-green-800
                                @elseif($cert->estado_certificacion === 'CERTIFICADO')
                                    bg-blue-100 text-blue-800
                                @else
                                    bg-yellow-100 text-yellow-800
                                @endif
                            ">
                                {{ $cert->estado_certificacion }}
                            </span>
                        </td>
                        <td class="px-6 py-3 text-sm">
                            @if($archivado)
                                <span class="inline-block px-2 py-1 bg-green-100 text-green-800 rounded-full font-medium text-xs">
                                    ✓ EXITOSO
                                </span>
                            @else
                                <span class="inline-block px-2 py-1 bg-red-100 text-red-800 rounded-full font-medium text-xs">
                                    ✗ FALLO
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            Sin certificados procesados
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            @if($certificados->hasPages())
            <div class="px-6 py-4 border-t border-gray-200">
                {{ $certificados->links() }}
            </div>
            @endif
        </div>

        <!-- Resumen -->
        <div class="bg-blue-50 border-l-4 border-blue-500 p-6 rounded">
            <h3 class="font-bold text-blue-900 mb-2">✅ Archivamiento Completado</h3>
            <p class="text-blue-700">Se archivaron exitosamente {{ $exitosos }} de {{ $certificados->total() }} certificados.</p>
            <p class="text-blue-700 mt-2">Los archivos están comprimidos en ZIP con hash de integridad SHA-256.</p>
        </div>

        <!-- Botones -->
        <div class="mt-6 flex gap-3">
            <a href="{{ route('certificacion.archivado.gestor') }}" class="inline-block bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                📦 Ir al Gestor
            </a>
            <a href="{{ route('certificacion.archivado.dashboard') }}" class="inline-block bg-gray-400 text-white px-6 py-2 rounded hover:bg-gray-500">
                ← Volver al Dashboard
            </a>
        </div>
    </div>
</div>
@endsection
