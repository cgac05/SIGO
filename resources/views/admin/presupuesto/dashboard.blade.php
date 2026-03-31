@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h2 class="m-0">
                        <i class="fas fa-chart-bar text-primary me-2"></i>
                        Dashboard de Presupuestación
                    </h2>
                    <small class="text-muted">Ciclo Fiscal {{ $ciclo->año_fiscal }}</small>
                </div>
                <span class="badge bg-{{ $ciclo->estado === 'ABIERTO' ? 'success' : 'danger' }} p-2">
                    Estado: {{ $ciclo->estado }}
                </span>
            </div>
        </div>
    </div>

    <!-- Resumen General -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-primary text-uppercase mb-1">
                        <small><strong>Presupuesto Total</strong></small>
                    </div>
                    <div class="h3 mb-0 text-gray-800">
                        ${{ number_format($resumen['presupuesto_total'], 2) }}
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
                        ${{ number_format($resumen['disponible_total'], 2) }}
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
                        ${{ number_format($resumen['gastado_total'], 2) }}
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-info text-uppercase mb-1">
                        <small><strong>% Utilizado</strong></small>
                    </div>
                    <div class="h3 mb-0 text-gray-800">
                        {{ $resumen['porcentaje_general'] }}%
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Estadísticas de Apoyo -->
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card card-stats">
                <div class="card-header bg-light">
                    <h5 class="card-title m-0">
                        <i class="fas fa-list me-2"></i>
                        Categorías
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <i class="fas fa-folder me-2"></i>
                        Total de Categorías: <strong>{{ $resumen['num_categorias'] }}</strong>
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-stats">
                <div class="card-header bg-light">
                    <h5 class="card-title m-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Apoyos Aprobados
                    </h5>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        <i class="fas fa-handshake me-2"></i>
                        Total Aprobados: <strong>{{ $resumen['num_apoyos_aprobados'] }}</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Categorías -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 bg-primary text-white">
            <h6 class="m-0 fw-bold">
                <i class="fas fa-cubes me-2"></i>
                Desglose por Categoría
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover dt-responsive nowrap" id="categoriasTable">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Categoría</th>
                            <th>Presupuesto</th>
                            <th>Disponible</th>
                            <th>Gastado</th>
                            <th>% Utilizado</th>
                            <th>Estado</th>
                            <th>Apoyos</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categorias as $index => $categoria)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>
                                    <strong>{{ $categoria['nombre'] }}</strong>
                                </td>
                                <td>
                                    ${{ number_format($categoria['presupuesto_total'], 2) }}
                                </td>
                                <td class="text-success">
                                    ${{ number_format($categoria['disponible'], 2) }}
                                </td>
                                <td class="text-warning">
                                    ${{ number_format($categoria['gastado'], 2) }}
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress" style="width: 60px; height: 20px;">
                                            <div
                                                class="progress-bar bg-{{ $categoria['badge_color'] }}"
                                                role="progressbar"
                                                style="width: {{ min($categoria['porcentaje_utilizado'], 100) }}%"
                                                aria-valuenow="{{ $categoria['porcentaje_utilizado'] }}"
                                                aria-valuemin="0"
                                                aria-valuemax="100">
                                            </div>
                                        </div>
                                        <span class="ms-2 fw-bold">
                                            {{ $categoria['porcentaje_utilizado'] }}%
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $categoria['badge_color'] }}">
                                        {{ $categoria['estado_visual'] }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-info">
                                        {{ $categoria['apoyos_aprobados'] }}
                                    </span>
                                </td>
                                <td>
                                    <a href="{{ route('presupuesto.categorias.show', $categoria['id_categoria']) }}"
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
                                    No hay categorías configuradas para este ciclo
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Botones de Acción -->
    <div class="row mb-4">
        <div class="col-md-12">
            <a href="{{ route('presupuesto.reportes') }}" class="btn btn-lg btn-info me-2">
                <i class="fas fa-file-pdf me-2"></i>
                Ver Reportes Completos
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-lg btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver al Panel
            </a>
        </div>
    </div>

</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#categoriasTable')) {
            $('#categoriasTable').DataTable().destroy();
        }
        
        $('#categoriasTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            },
            pageLength: 25,
            order: [[5, 'desc']]
        });
    });
</script>
@endpush

@endsection
