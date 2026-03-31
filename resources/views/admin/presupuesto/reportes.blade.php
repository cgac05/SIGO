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
                <a href="{{ route('presupuesto.dashboard') }}">Presupuestación</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Reportes</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="row mb-4">
        <div class="col-md-12">
            <h2 class="mb-3">
                <i class="fas fa-file-pdf text-danger me-2"></i>
                Reportes de Presupuestación
            </h2>
        </div>
    </div>

    @if (!$ciclo)
        <!-- Sin ciclo disponible -->
        <div class="alert alert-warning" role="alert">
            <h6 class="alert-heading">
                <i class="fas fa-exclamation-triangle me-2"></i>
                No hay ciclo fiscal disponible
            </h6>
            <p class="mb-0">
                No se encontró un ciclo fiscal para el año {{ $año }}.
                Por favor, contacte al administrador del sistema.
            </p>
        </div>
    @else
        <!-- Selector de Año -->
        <div class="card shadow mb-4">
            <div class="card-body">
                <form method="GET" action="{{ route('presupuesto.reportes') }}" class="d-flex align-items-end gap-2">
                    <div class="flex-grow-1">
                        <label for="año" class="form-label">Seleccionar Año Fiscal:</label>
                        <input
                            type="number"
                            id="año"
                            name="año"
                            class="form-control"
                            value="{{ $año }}"
                            min="{{ now()->year - 5 }}"
                            max="{{ now()->year + 5 }}">
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-2"></i>
                        Buscar
                    </button>
                </form>
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
                            <small><strong>% Utilización</strong></small>
                        </div>
                        <div class="h3 mb-0 text-gray-800">
                            {{ $resumen['porcentaje_general'] }}%
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráfico de Utilización General -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card shadow">
                    <div class="card-header bg-info text-white">
                        <h6 class="m-0 fw-bold">
                            <i class="fas fa-chart-pie me-2"></i>
                            Utilización General del Presupuesto
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="progress" style="height: 40px;">
                            <div
                                class="progress-bar bg-success"
                                role="progressbar"
                                style="width: {{ min($resumen['porcentaje_general'], 100) }}%"
                                aria-valuenow="{{ $resumen['porcentaje_general'] }}"
                                aria-valuemin="0"
                                aria-valuemax="100">
                                <strong>{{ $resumen['porcentaje_general'] }}% Utilizado</strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Categorías Detallada -->
        <div class="card shadow mb-4">
            <div class="card-header py-3 bg-primary text-white">
                <h6 class="m-0 fw-bold">
                    <i class="fas fa-table me-2"></i>
                    Desglose por Categoría (Detallado)
                </h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover dt-responsive nowrap" id="categoriasReporteTable">
                        <thead class="table-light">
                            <tr>
                                <th>Categoría</th>
                                <th class="text-end">Presupuesto</th>
                                <th class="text-end">Gastado</th>
                                <th class="text-end">Disponible</th>
                                <th class="text-center">% Utilizado</th>
                                <th class="text-center">Apoyos Totales</th>
                                <th class="text-center">Aprobados</th>
                                <th class="text-end">Monto Aprobado</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($categorias as $categoria)
                                <tr>
                                    <td>
                                        <strong>{{ $categoria['nombre'] }}</strong>
                                    </td>
                                    <td class="text-end text-primary fw-bold">
                                        ${{ number_format($categoria['presupuesto'], 2) }}
                                    </td>
                                    <td class="text-end text-warning fw-bold">
                                        ${{ number_format($categoria['gastado'], 2) }}
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        ${{ number_format($categoria['disponible'], 2) }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-{{ $categoria['porcentaje'] >= 85 ? 'danger' : ($categoria['porcentaje'] >= 70 ? 'warning' : 'success') }}">
                                            {{ $categoria['porcentaje'] }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-info">{{ $categoria['num_apoyos'] }}</span>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-success">{{ $categoria['num_aprobados'] }}</span>
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        ${{ number_format($categoria['monto_aprobado'], 2) }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox me-2"></i>
                                        No hay categorías disponibles
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="table-light fw-bold">
                            <tr>
                                <td>TOTAL</td>
                                <td class="text-end">
                                    ${{ number_format($resumen['presupuesto_total'], 2) }}
                                </td>
                                <td class="text-end">
                                    ${{ number_format($resumen['gastado_total'], 2) }}
                                </td>
                                <td class="text-end">
                                    ${{ number_format($resumen['disponible_total'], 2) }}
                                </td>
                                <td class="text-center">
                                    <span class="badge bg-primary">{{ $resumen['porcentaje_general'] }}%</span>
                                </td>
                                <td colspan="3"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Información del Ciclo -->
        <div class="card shadow mb-4">
            <div class="card-header bg-secondary text-white">
                <h6 class="m-0 fw-bold">
                    <i class="fas fa-calendar me-2"></i>
                    Información del Ciclo Fiscal
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p class="mb-1">
                            <strong>Año Fiscal:</strong>
                        </p>
                        <p class="text-muted">{{ $ciclo->año_fiscal }}</p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1">
                            <strong>Estado:</strong>
                        </p>
                        <p>
                            <span class="badge bg-{{ $ciclo->estado === 'ABIERTO' ? 'success' : 'danger' }}">
                                {{ $ciclo->estado }}
                            </span>
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1">
                            <strong>Fecha de Apertura:</strong>
                        </p>
                        <p class="text-muted">
                            @if ($ciclo->fecha_apertura)
                                {{ $ciclo->fecha_apertura->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                    <div class="col-md-3">
                        <p class="mb-1">
                            <strong>Cierre Programado:</strong>
                        </p>
                        <p class="text-muted">
                            @if ($ciclo->fecha_cierre_programado)
                                {{ $ciclo->fecha_cierre_programado->format('d/m/Y') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="row mb-4">
            <div class="col-md-12">
                <a href="{{ route('presupuesto.dashboard') }}" class="btn btn-lg btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>
                    Volver al Dashboard
                </a>
            </div>
        </div>
    @endif

</div>

@push('scripts')
<script>
    $(document).ready(function() {
        if ($.fn.DataTable.isDataTable('#categoriasReporteTable')) {
            $('#categoriasReporteTable').DataTable().destroy();
        }
        
        $('#categoriasReporteTable').DataTable({
            responsive: true,
            language: {
                url: '//cdn.datatables.net/plug-ins/1.10.21/i18n/Spanish.json'
            },
            pageLength: 25,
            order: [[3, 'desc']]
        });
    });
</script>
@endpush

@endsection
