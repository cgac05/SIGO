@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}">Inicio</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.presupuesto.dashboard') }}">Presupuestación</a>
            </li>
            <li class="breadcrumb-item">
                <a href="{{ route('admin.presupuesto.categoria', $categoria->id_categoria) }}">
                    {{ $categoria->nombre }}
                </a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Apoyo #{{ $presupuesto->id_presupuesto_apoyo }}</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="m-0 mb-2">{{ $apoyo->nombre_apoyo ?? 'Apoyo N/A' }}</h2>
                            <p class="text-muted m-0">
                                Categoría: <strong>{{ $categoria->nombre }}</strong> | 
                                Ciclo: <strong>{{ $categoria->ciclo->año_fiscal }}</strong>
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $presupuesto->getBadgeColor() }} p-2 fs-6">
                                <i class="fas fa-{{ $presupuesto->getBadgeIcon() }} me-1"></i>
                                {{ $presupuesto->estado }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Información del Presupuesto -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-primary text-uppercase mb-1">
                        <small><strong>Costo Estimado</strong></small>
                    </div>
                    <div class="h3 mb-0 text-gray-800">
                        {{ $presupuesto->getCostoEstimadoFormato() }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-info text-uppercase mb-1">
                        <small><strong>Fecha Reserva</strong></small>
                    </div>
                    <div class="h5 mb-0 text-gray-800">
                        @if ($presupuesto->fecha_reserva)
                            {{ $presupuesto->fecha_reserva->format('d/m/Y H:i') }}
                        @else
                            <span class="text-muted">N/A</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-success text-uppercase mb-1">
                        <small><strong>Fecha Aprobación</strong></small>
                    </div>
                    <div class="h5 mb-0 text-gray-800">
                        @if ($presupuesto->fecha_aprobacion)
                            {{ $presupuesto->fecha_aprobacion->format('d/m/Y H:i') }}
                        @else
                            <span class="text-muted">Pendiente</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-warning text-uppercase mb-1">
                        <small><strong>Directivo Aprobador</strong></small>
                    </div>
                    <div class="h5 mb-0 text-gray-800">
                        {{ $presupuesto->directivoAprobador?->nombre ?? 'Pendiente' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Movimientos -->
    <div class="card shadow">
        <div class="card-header py-3 bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold">
                    <i class="fas fa-history me-2"></i>
                    Historial de Movimientos
                </h6>
                <span class="badge bg-light text-dark">{{ count($movimientos) }} Registro(s)</span>
            </div>
        </div>
        <div class="card-body">
            @if (count($movimientos) > 0)
                <div class="table-responsive">
                    <table class="table table-hover table-striped" id="movimientosTable">
                        <thead class="table-light">
                            <tr>
                                <th class="text-center" style="width: 40px;">#</th>
                                <th>Tipo de Movimiento</th>
                                <th>Monto</th>
                                <th>Usuario Responsable</th>
                                <th>Solicitante</th>
                                <th>Notas</th>
                                <th>IP Origen</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($movimientos as $index => $movimiento)
                                <tr class="align-middle">
                                    <td class="text-center">
                                        <span class="badge bg-secondary">{{ $index + 1 }}</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-{{ $movimiento['tipo_color'] }}">
                                            <i class="fas fa-{{ $movimiento['tipo_icon'] }} me-1"></i>
                                            {{ $movimiento['tipo_label'] }}
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold">
                                        <span class="text-{{ $movimiento['tipo'] === 'CANCELACION' ? 'danger' : 'success' }}">
                                            {{ $movimiento['monto_formato'] }}
                                        </span>
                                    </td>
                                    <td>
                                        <small>{{ $movimiento['usuario'] }}</small>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $movimiento['solicitante'] }}</small>
                                    </td>
                                    <td>
                                        @if ($movimiento['notas'])
                                            <small class="text-muted">
                                                {{ Str::limit($movimiento['notas'], 50) }}
                                            </small>
                                        @else
                                            <small class="text-muted">—</small>
                                        @endif
                                    </td>
                                    <td>
                                        <code class="text-muted">{{ $movimiento['ip_origen'] }}</code>
                                    </td>
                                    <td>
                                        <small class="text-muted">{{ $movimiento['fecha'] }}</small>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="text-center text-muted py-4">
                    <i class="fas fa-inbox me-2 fa-2x mb-3"></i>
                    <p>No hay movimientos registrados para este apoyo presupuestario</p>
                </div>
            @endif
        </div>
    </div>

    <!-- Resumen de Estado -->
    <div class="row mt-4 mb-4">
        <div class="col-md-12">
            <div class="alert alert-info" role="alert">
                <h6 class="alert-heading">
                    <i class="fas fa-info-circle me-2"></i>
                    Información de Auditoría
                </h6>
                <hr>
                <div class="row">
                    <div class="col-md-6">
                        <p class="mb-1">
                            <strong>Estado Actual:</strong>
                            <span class="badge bg-{{ $presupuesto->getBadgeColor() }}">{{ $presupuesto->estado }}</span>
                        </p>
                        <p class="mb-0">
                            <strong>Total de Movimientos:</strong> {{ count($movimientos) }}
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p class="mb-1">
                            <strong>Monto Total Aprobado:</strong>
                            <span class="text-success fw-bold">
                                ${{ number_format($movimientos->where('tipo', 'ASIGNACION_DIRECTIVO')->sum('monto'), 2) }}
                            </span>
                        </p>
                        <p class="mb-0">
                            <strong>Última Actualización:</strong>
                            @php
                                $ultimoMovimiento = collect($movimientos)->sortByDesc('fecha')->first();
                            @endphp
                            @if ($ultimoMovimiento)
                                {{ $ultimoMovimiento['fecha'] }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Botones de Navegación -->
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="{{ route('presupuesto.categorias.show', $categoria->id_categoria) }}"
               class="btn btn-lg btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver a la Categoría
            </a>
            <a href="{{ route('admin.presupuesto.dashboard') }}" class="btn btn-lg btn-outline-secondary">
                <i class="fas fa-home me-2"></i>
                Volver al Dashboard
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#movimientosTable')) {
            $('#movimientosTable').DataTable().destroy();
        }
        
        $('#movimientosTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            },
            pageLength: 10,
            order: [[7, 'desc']]
        });
    });
</script>
@endpush

@endsection
