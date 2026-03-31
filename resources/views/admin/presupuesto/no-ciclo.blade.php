@extends('layouts.app')

@section('content')
<div class="container-fluid">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">
                <a href="{{ route('dashboard') }}">Inicio</a>
            </li>
            <li class="breadcrumb-item active" aria-current="page">Presupuestación</li>
        </ol>
    </nav>

    <!-- Alert -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="alert alert-warning alert-dismissible fade show" role="alert">
                <h4 class="alert-heading">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    No hay ciclo fiscal disponible
                </h4>
                <p>
                    Actualmente no existe un ciclo fiscal <strong>ABIERTO</strong> en el sistema para el año
                    <strong>{{ now()->year }}</strong>.
                </p>
                <hr>
                <p class="mb-0">
                    Por favor, contacte al administrador del sistema para que configure un nuevo ciclo fiscal
                    antes de continuar.
                </p>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
            </div>
        </div>
    </div>

    <!-- Botón de regreso -->
    <div class="row">
        <div class="col-md-12">
            <a href="{{ route('dashboard') }}" class="btn btn-lg btn-secondary">
                <i class="fas fa-arrow-left me-2"></i>
                Volver al Panel de Control
            </a>
        </div>
    </div>

</div>
@endsection
