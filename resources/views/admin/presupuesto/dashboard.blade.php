@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <!-- Header mejorado -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="m-0 fw-bold">
                        <i class="fas fa-chart-pie text-primary me-3"></i>
                        Dashboard de Presupuestación
                    </h1>
                    <p class="text-muted mt-2 mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>Ciclo Fiscal {{ $ciclo->año_fiscal }}
                    </p>
                </div>
                <div>
                    <span class="badge bg-{{ $ciclo->estado === 'ABIERTO' ? 'success' : 'danger' }} p-3 fs-6">
                        <i class="fas fa-circle-notch me-2"></i>{{ $ciclo->estado }}
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 1: Cards de Resumen con iconos grandes -->
    <div class="row mb-5">
        <!-- Presupuesto Total -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 opacity-10" style="width: 100px; height: 100px; background: #0d6efd; border-radius: 50%; transform: translate(30px, -30px);"></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                            <i class="fas fa-money-bill-wave text-primary fa-2x"></i>
                        </div>
                        <small class="text-muted ms-auto fw-bold">PRESUPUESTO TOTAL</small>
                    </div>
                    <h3 class="fw-bold mb-2">${{ number_format($resumen['presupuesto_total'], 0) }}</h3>
                    <small class="text-muted">
                        <i class="fas fa-check-circle text-success me-1"></i>
                        Presupuesto para el año fiscal
                    </small>
                </div>
            </div>
        </div>

        <!-- Gastado -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 opacity-10" style="width: 100px; height: 100px; background: #fd7e14; border-radius: 50%; transform: translate(30px, -30px);"></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-warning bg-opacity-10 p-3">
                            <i class="fas fa-credit-card text-warning fa-2x"></i>
                        </div>
                        <small class="text-muted ms-auto fw-bold">GASTADO</small>
                    </div>
                    <h3 class="fw-bold mb-2">${{ number_format($resumen['gastado_total'], 0) }}</h3>
                    <small class="text-muted">
                        <i class="fas fa-arrow-down text-warning me-1"></i>
                        Repartido entre categorías
                    </small>
                </div>
            </div>
        </div>

        <!-- Disponible -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 opacity-10" style="width: 100px; height: 100px; background: #198754; border-radius: 50%; transform: translate(30px, -30px);"></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3">
                            <i class="fas fa-wallet text-success fa-2x"></i>
                        </div>
                        <small class="text-muted ms-auto fw-bold">DISPONIBLE</small>
                    </div>
                    <h3 class="fw-bold mb-2">${{ number_format($resumen['disponible_total'], 0) }}</h3>
                    <small class="text-muted">
                        <i class="fas fa-arrow-up text-success me-1"></i>
                        Por distribuir
                    </small>
                </div>
            </div>
        </div>

        <!-- Porcentaje Utilizado -->
        <div class="col-md-6 col-lg-3 mb-4">
            <div class="card border-0 shadow-sm h-100 position-relative overflow-hidden">
                <div class="position-absolute top-0 end-0 opacity-10" style="width: 100px; height: 100px; background: #0dcaf0; border-radius: 50%; transform: translate(30px, -30px);"></div>
                <div class="card-body">
                    <div class="d-flex align-items-center mb-3">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3">
                            <i class="fas fa-chart-area text-info fa-2x"></i>
                        </div>
                        <small class="text-muted ms-auto fw-bold">% UTILIZADO</small>
                    </div>
                    <h3 class="fw-bold mb-2">{{ $resumen['porcentaje_general'] }}%</h3>
                    <small class="text-muted">
                        <i class="fas fa-percent text-info me-1"></i>
                        Del presupuesto total
                    </small>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 2: Gráficos principales -->
    <div class="row mb-5">
        <!-- Gráfico Circular (Doughnut) - Distribución del Presupuesto -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title me-2 mb-0">
                        <i class="fas fa-circle-notch text-primary me-2"></i>
                        Distribución del Presupuesto
                    </h5>
                    <small class="text-muted d-block">Presupuesto Total vs Disponible</small>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 350px;">
                    @if($resumen['presupuesto_total'] > 0)
                        <canvas id="chartPresupuesto"></canvas>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-pie fa-3x opacity-50 mb-3 d-block"></i>
                            <p>Sin datos disponibles</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <!-- Gráfico Lineal - Ejecución por Categoría -->
        <div class="col-lg-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title me-2 mb-0">
                        <i class="fas fa-bar-chart text-success me-2"></i>
                        Ejecución Presupuestaria
                    </h5>
                    <small class="text-muted d-block">Porcentaje de utilización</small>
                </div>
                <div class="card-body d-flex align-items-center justify-content-center" style="min-height: 350px;">
                    @if($categorias->count() > 0)
                        <canvas id="chartEjecucion"></canvas>
                    @else
                        <div class="text-center text-muted py-5">
                            <i class="fas fa-chart-bar fa-3x opacity-50 mb-3 d-block"></i>
                            <p>Sin categorías configuradas</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 3: Estadísticas de Categorías y Apoyos -->
    <div class="row mb-5">
        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title me-2 mb-0">
                        <i class="fas fa-folder text-info me-2"></i>
                        Categorías Configuradas
                    </h5>
                    <small class="text-muted d-block">Total de categorías presupuestarias</small>
                </div>
                <div class="card-body text-center py-4">
                    <h2 class="fw-bold text-info mb-2">{{ $resumen['num_categorias'] }}</h2>
                    <p class="text-muted mb-0">
                        @if($resumen['num_categorias'] === 0)
                            <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                            Sin categorías configuradas
                        @else
                            <i class="fas fa-check-circle text-success me-2"></i>
                            Categorías disponibles
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="col-md-6 mb-4">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title me-2 mb-0">
                        <i class="fas fa-handshake text-success me-2"></i>
                        Apoyos Aprobados
                    </h5>
                    <small class="text-muted d-block">Total de apoyos en este ciclo</small>
                </div>
                <div class="card-body text-center py-4">
                    <h2 class="fw-bold text-success mb-2">{{ $resumen['num_apoyos_aprobados'] }}</h2>
                    <p class="text-muted mb-0">
                        @if($resumen['num_apoyos_aprobados'] === 0)
                            <i class="fas fa-info-circle text-info me-2"></i>
                            Sin apoyos aprobados aún
                        @else
                            <i class="fas fa-chart-line text-success me-2"></i>
                            Apoyos en ejecución
                        @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- ROW 4: Tabla de Categorías -->
    @if($categorias->count() > 0)
        <div class="row">
            <div class="col-lg-12">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-primary text-white py-3">
                        <h5 class="card-title me-2 mb-0">
                            <i class="fas fa-cubes me-2"></i>
                            Desglose Detallado por Categoría
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="categoriasTable">
                                <thead class="table-light">
                                    <tr>
                                        <th>#</th>
                                        <th>Categoría</th>
                                        <th>Presupuesto Total</th>
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
                                            <td><strong>{{ $categoria['nombre'] }}</strong></td>
                                            <td>${{ number_format($categoria['presupuesto_total'], 0) }}</td>
                                            <td class="text-success">
                                                <strong>${{ number_format($categoria['disponible'], 0) }}</strong>
                                            </td>
                                            <td class="text-warning">
                                                <strong>${{ number_format($categoria['gastado'], 0) }}</strong>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="progress w-100 me-2" style="height: 10px;">
                                                        <div class="progress-bar bg-{{ $categoria['badge_color'] }}" 
                                                             role="progressbar" 
                                                             style="width: {{ $categoria['porcentaje_utilizado'] }}%">
                                                        </div>
                                                    </div>
                                                    <small class="fw-bold">{{ $categoria['porcentaje_utilizado'] }}%</small>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-{{ $categoria['badge_color'] }}">
                                                    {{ $categoria['estado_visual'] }}
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-info">{{ $categoria['apoyos_aprobados'] }}</span>
                                            </td>
                                            <td>
                                                <a href="{{ route('presupuesto.categorias.show', $categoria['id_categoria']) }}" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye me-1"></i>Ver
                                                </a>
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="9" class="text-center text-muted py-4">
                                                <i class="fas fa-inbox fa-2x opacity-50 mb-2 d-block"></i>
                                                No hay categorías configuradas
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="row">
            <div class="col-lg-12">
                <div class="alert alert-info border-0 shadow-sm" role="alert">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Sin categorías configuradas</strong>
                    <p class="mb-0 mt-2">El administrador debe configurar las categorías presupuestarias para este ciclo fiscal.</p>
                </div>
            </div>
        </div>
    @endif
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
    // Gráfico Circular (Doughnut) - Presupuesto Vs Disponible
    @if($resumen['presupuesto_total'] > 0)
        const presupuestoTotal = {{ $resumen['presupuesto_total'] }};
        const disponibleTotal = {{ $resumen['disponible_total'] }};
        const gastadoTotal = {{ $resumen['gastado_total'] }};

        const ctx1 = document.getElementById('chartPresupuesto').getContext('2d');
        new Chart(ctx1, {
            type: 'doughnut',
            data: {
                labels: ['Gastado', 'Disponible'],
                datasets: [{
                    data: [gastadoTotal, disponibleTotal],
                    backgroundColor: ['#ff9999', '#90EE90'],
                    borderColor: ['#ff6666', '#66BB6A'],
                    borderWidth: 3,
                    hoverOffset: 10
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            font: { size: 14, weight: 'bold' },
                            padding: 20,
                            usePointStyle: true
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = '$' + context.parsed.toString();
                                const percentage = ((context.parsed / presupuestoTotal) * 100).toFixed(1);
                                return label + ': ' + value + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'textCenter',
                beforeDatasetsDraw(chart) {
                    const {ctx, chartArea: {left, top, width, height}} = chart;
                    ctx.save();
                    
                    ctx.font = 'bold 24px Arial';
                    ctx.fillStyle = '#333';
                    ctx.textAlign = 'center';
                    ctx.textBaseline = 'middle';
                    ctx.fillText('$' + presupuestoTotal.toLocaleString('es-MX', {maximumFractionDigits: 0}), left + width / 2, top + height / 2 - 10);
                    
                    ctx.font = '14px Arial';
                    ctx.fillStyle = '#666';
                    ctx.fillText('Presupuesto Total', left + width / 2, top + height / 2 + 15);
                    
                    ctx.restore();
                }
            }]
        });
    @endif

    // Gráfico de Barras - Ejecución por Categoría
    @if($categorias->count() > 0)
        const categoriasData = @json($categorias);
        
        const ctx2 = document.getElementById('chartEjecucion').getContext('2d');
        new Chart(ctx2, {
            type: 'bar',
            data: {
                labels: categoriasData.map(function(c) { return c.nombre; }),
                datasets: [{
                    label: '% Utilizado',
                    data: categoriasData.map(function(c) { return c.porcentaje_utilizado; }),
                    backgroundColor: categoriasData.map(function(c) {
                        const colors = {
                            'success': '#198754',
                            'warning': '#fd7e14',
                            'danger': '#dc3545',
                            'info': '#0dcaf0'
                        };
                        return colors[c.badge_color] || '#0d6efd';
                    }),
                    borderRadius: 5,
                    borderSkipped: false
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return context.parsed.x.toFixed(2) + '%';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: 100,
                        ticks: {
                            callback: function(value) {
                                return value + '%';
                            }
                        }
                    }
                }
            }
        });
    @endif
</script>

@endsection
