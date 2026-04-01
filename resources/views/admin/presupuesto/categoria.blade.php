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
            <li class="breadcrumb-item active" aria-current="page">{{ $categoria->nombre }}</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card shadow">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="m-0 mb-2">{{ $categoria->nombre }}</h2>
                            <p class="text-muted m-0">
                                Ciclo Fiscal {{ $categoria->ciclo->año_fiscal }}
                            </p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-{{ $categoria->getBadgeColor() }} p-2 me-2">
                                {{ round($categoria->getPorcentajeUtilizacion()) }}% Utilizado
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas de la Categoría -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-primary text-uppercase mb-1">
                        <small><strong>Presupuesto Anual</strong></small>
                    </div>
                    <div class="h3 mb-0 text-gray-800">
                        ${{ number_format($categoria->presupuesto_anual, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-success text-uppercase mb-1">
                        <small><strong>Disponible</strong></small>
                    </div>
                    <div class="h3 mb-0 text-gray-800">
                        ${{ number_format($categoria->disponible, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-warning text-uppercase mb-1">
                        <small><strong>Gastado</strong></small>
                    </div>
                    <div class="h3 mb-0 text-gray-800">
                        ${{ number_format((float) $categoria->presupuesto_anual - (float) $categoria->disponible, 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-info text-uppercase mb-1">
                        <small><strong>% Utilización</strong></small>
                    </div>
                    <div class="h3 mb-0 text-gray-800">
                        {{ round($categoria->getPorcentajeUtilizacion(), 2) }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de progreso -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="progress" style="height: 30px;">
                <div
                    class="progress-bar bg-success"
                    role="progressbar"
                    style="width: {{ min($categoria->getPorcentajeUtilizacion(), 100) }}%"
                    aria-valuenow="{{ $categoria->getPorcentajeUtilizacion() }}"
                    aria-valuemin="0"
                    aria-valuemax="100">
                    {{ round($categoria->getPorcentajeUtilizacion(), 1) }}% Utilizado
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Apoyos -->
    <div class="card shadow">
        <div class="card-header py-3 bg-primary text-white">
            <div class="d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold">
                    <i class="fas fa-list me-2"></i>
                    Apoyos Presupuestarios ({{ $apoyos->count() }})
                </h6>
                <span class="badge bg-light text-dark">Total: ${{ number_format($apoyos->sum('costo_estimado'), 2) }}</span>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dt-responsive nowrap" id="apoyosTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Apoyo</th>
                            <th>Estado</th>
                            <th>Costo Estimado</th>
                            <th>Fecha Reserva</th>
                            <th>Fecha Aprobación</th>
                            <th>Directivo Aprobador</th>
                            <th>Movimientos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($apoyos as $index => $apoyo)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $apoyo['apoyo_nombre'] }}</strong>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $apoyo['estado_badge'] }}">
                                        {{ $apoyo['estado'] }}
                                    </span>
                                </td>
                                <td class="text-end font-weight-bold">
                                    {{ $apoyo['costo_formato'] }}
                                </td>
                                <td>
                                    @if ($apoyo['fecha_reserva'])
                                        <small class="text-muted">{{ $apoyo['fecha_reserva'] }}</small>
                                    @else
                                        <small class="text-muted">—</small>
                                    @endif
                                </td>
                                <td>
                                    @if ($apoyo['fecha_aprobacion'])
                                        <small class="text-success fw-bold">{{ $apoyo['fecha_aprobacion'] }}</small>
                                    @else
                                        <small class="text-muted">Pendiente</small>
                                    @endif
                                </td>
                                <td>
                                    {{ $apoyo['directivo_aprobador'] }}
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $apoyo['num_movimientos'] }}</span>
                                </td>
                                <td>
                                    <a href="{{ route('admin.presupuesto.apoyo', $apoyo['id_presupuesto']) }}"
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye me-1"></i>
                                        Ver
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox me-2"></i>
                                    No hay apoyos asignados a esta categoría
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Botones de Navegación -->
    <div class="row mt-4 mb-4">
        <div class="col-md-12">
            <a href="{{ route('admin.presupuesto.dashboard') }}" class="btn btn-lg btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver al Dashboard
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#apoyosTable')) {
            $('#apoyosTable').DataTable().destroy();
        }
        
        $('#apoyosTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            },
            pageLength: 10,
            order: [[5, 'desc']]
        });
    });
</script>
@endpush

@endsection
