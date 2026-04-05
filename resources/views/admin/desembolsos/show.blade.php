@extends('layouts.app')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Detalles del Desembolso</h1>
            <p class="text-gray-600 mt-1">Información completa del pago registrado</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('desembolsos.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>
    </div>

    <!-- Main Content Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left Column - Payment Details -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Payment Card -->
            <div class="bg-white rounded-lg shadow p-6">
                <h2 class="text-xl font-bold text-gray-900 mb-4">
                    <i class="fas fa-credit-card text-green-600"></i> Información del Pago
                </h2>
                
                <div class="grid grid-cols-2 gap-4 mb-6">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Folio de Solicitud</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $desembolso->fk_folio }}</p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">ID de Histórico</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ $desembolso->id_historico }}</p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Monto Entregado</p>
                        <p class="text-lg font-semibold text-green-600">
                            ${{ number_format($desembolso->monto_entregado, 2) }}
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Estado</p>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium 
                            @if($desembolso->estado_pago === 'COMPLETADO')
                                bg-green-100 text-green-800
                            @elseif($desembolso->estado_pago === 'PENDIENTE')
                                bg-yellow-100 text-yellow-800
                            @else
                                bg-gray-100 text-gray-800
                            @endif">
                            {{ $desembolso->estado_pago ?? 'SIN ESTADO' }}
                        </span>
                    </div>
                </div>

                <hr class="my-4">

                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Fecha de Entrega</p>
                        <p class="text-base font-semibold text-gray-900">
                            {{ $desembolso->fecha_entrega->format('d/m/Y H:i:s') }}
                        </p>
                    </div>
                    
                    <div>
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Autorizado Por</p>
                        <p class="text-base font-semibold text-gray-900">
                                {{ $desembolso->usuario->display_name ?? 'Usuario no identificado' }}
                    <div class="col-span-2">
                        <p class="text-xs text-gray-500 uppercase tracking-wide">Descripción</p>
                        <p class="text-base text-gray-700 mt-1">
                            {{ $desembolso->descripcion ?? 'Sin descripción' }}
                        </p>
                    </div>

                    @if($desembolso->ruta_pdf_final)
                        <div class="col-span-2">
                            <p class="text-xs text-gray-500 uppercase tracking-wide mb-2">Comprobante</p>
                            <a href="{{ $desembolso->ruta_pdf_final }}" target="_blank" 
                                class="inline-flex items-center text-blue-600 hover:text-blue-800">
                                <i class="fas fa-file-pdf text-red-600 mr-2"></i>
                                {{ basename($desembolso->ruta_pdf_final) }}
                            </a>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Solicitud Info -->
            @if($solicitud)
                <div class="bg-white rounded-lg shadow p-6">
                    <h2 class="text-xl font-bold text-gray-900 mb-4">
                        <i class="fas fa-file-alt text-blue-600"></i> Información de la Solicitud
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Folio</p>
                            <p class="text-base font-semibold text-gray-900">{{ $solicitud->folio }}</p>
                        </div>
                        
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Estado</p>
                            <p class="text-base text-gray-900">
                                {{ $solicitud->estado->nombre ?? 'Sin estado' }}
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Presupuesto Asignado</p>
                            <p class="text-base font-semibold text-gray-900">
                                ${{ number_format($solicitud->presupuesto_asignado, 2) }}
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Total Entregado</p>
                            <p class="text-base font-semibold text-green-600">
                                ${{ number_format($solicitud->monto_entregado, 2) }}
                            </p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Porcentaje Entregado</p>
                            @php
                                $porcentaje = $solicitud->presupuesto_asignado > 0 
                                    ? round(($solicitud->monto_entregado / $solicitud->presupuesto_asignado) * 100, 2)
                                    : 0;
                            @endphp
                            <p class="text-base font-semibold text-gray-900">{{ $porcentaje }}%</p>
                        </div>

                        <div>
                            <p class="text-xs text-gray-500 uppercase tracking-wide">Disponible Aún</p>
                            @php
                                $disponible = $solicitud->presupuesto_asignado - ($solicitud->monto_entregado ?? 0);
                            @endphp
                            <p class="text-base font-semibold text-blue-600">
                                ${{ number_format(max(0, $disponible), 2) }}
                            </p>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="mt-4">
                        <p class="text-sm font-medium text-gray-700 mb-2">Ejecución del Presupuesto</p>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-green-600 h-3 rounded-full" style="width: {{ $porcentaje }}%"></div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Snapshot Info -->
            @if($snapshot_antes)
                <div class="bg-yellow-50 rounded-lg shadow p-6 border-l-4 border-yellow-500">
                    <h2 class="text-lg font-bold text-gray-900 mb-4">
                        <i class="fas fa-history text-yellow-600"></i> Estado Anterior al Pago
                    </h2>
                    
                    <div class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <p class="text-gray-500">Presupuesto Asignado (Antes)</p>
                            <p class="font-semibold text-gray-900">
                                ${{ number_format($snapshot_antes['presupuesto_asignado'] ?? 0, 2) }}
                            </p>
                        </div>
                        
                        <div>
                            <p class="text-gray-500">Monto Entregado Anterior</p>
                            <p class="font-semibold text-gray-900">
                                ${{ number_format($snapshot_antes['monto_entregado_anterior'] ?? 0, 2) }}
                            </p>
                        </div>
                        
                        <div class="col-span-2">
                            <p class="text-gray-500">Estado Anterior</p>
                            <p class="font-semibold text-gray-900">
                                {{ $snapshot_antes['estado_anterior'] ?? 'No registrado' }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Right Column - Side Info -->
        <div class="space-y-6">
            <!-- IP Terminal -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-bold text-gray-700 mb-3">
                    <i class="fas fa-globe"></i> Información técnica
                </h3>
                <div class="text-xs space-y-2">
                    <div>
                        <p class="text-gray-500">IP Terminal</p>
                        <p class="font-mono text-gray-900 break-all">{{ $desembolso->ip_terminal }}</p>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-gradient-to-br from-green-50 to-green-100 rounded-lg shadow p-4 border border-green-200">
                <h3 class="text-sm font-bold text-gray-900 mb-3">
                    <i class="fas fa-check-circle text-green-600"></i> Pago Registrado
                </h3>
                <p class="text-2xl font-bold text-green-600">
                    ${{ number_format($desembolso->monto_entregado, 2) }}
                </p>
                <p class="text-xs text-gray-600 mt-2">
                    Desembolsado el {{ $desembolso->fecha_entrega->format('d/m/Y') }}
                </p>
            </div>

            <!-- Indicadores -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-bold text-gray-700 mb-3">
                    <i class="fas fa-flag"></i> Indicadores
                </h3>
                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Comprobante:</span>
                        <span class="inline-flex items-center">
                            @if($desembolso->ruta_pdf_final)
                                <i class="fas fa-check-circle text-green-600"></i>
                            @else
                                <i class="fas fa-times-circle text-red-600"></i>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-600">Descripción:</span>
                        <span class="inline-flex items-center">
                            @if($desembolso->descripcion)
                                <i class="fas fa-check-circle text-green-600"></i>
                            @else
                                <i class="fas fa-info-circle text-gray-400"></i>
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <!-- Related Actions -->
            <div class="bg-white rounded-lg shadow p-4">
                <h3 class="text-sm font-bold text-gray-700 mb-3">
                    <i class="fas fa-cog"></i> Acciones
                </h3>
                <a href="{{ route('desembolsos.index') }}" 
                    class="block w-full text-center bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm">
                    <i class="fas fa-list"></i> Ir a Listado
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
